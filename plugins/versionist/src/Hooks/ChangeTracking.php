<?php

namespace Versionist\Hooks;

use Versionist\Actions\ActionsInfoProvider;
use Versionist\ChangeInfos\EntityChangeInfo;
use Versionist\Database\Database;
use Versionist\Database\DbSchemaInfo;
use Versionist\Database\VpidRepository;
use Versionist\Database\WpdbMirrorBridge;
use Versionist\DI\VersionistServices;
use Versionist\Git\Committer;
use Versionist\Utils\IdUtil;
use Versionist\Utils\RequestDetector;
use Versionist\Utils\WordPressMissingFunctions;
use Versionist\Versionist;

/**
 * Hooks that detect changes WordPress makes outside of the database (plugins, themes, translations,
 * core updates) or that need special treatment, and turn them into commits.
 * Database changes themselves are tracked by {@see \Versionist\Database\VersionistWpdb}.
 */
class ChangeTracking
{
    /** @var Committer */
    private $committer;
    /** @var DbSchemaInfo */
    private $dbSchemaInfo;
    /** @var VpidRepository */
    private $vpidRepository;
    /** @var WpdbMirrorBridge */
    private $wpdbMirrorBridge;
    /** @var Database */
    private $database;
    /** @var ActionsInfoProvider */
    private $actionsInfoProvider;

    public function register(): void
    {
        if (!Versionist::isActive()) {
            return;
        }

        add_action('init', [$this, 'registerHooks']);

        // GUIDs based on the URL would differ between environments (clones) of a site.
        add_filter('wp_insert_post_data', [$this, 'generatePostGuid'], 99, 2);
        add_filter('wp_insert_attachment_data', [$this, 'generatePostGuid'], 99, 2);
    }

    public function registerHooks(): void
    {
        global $versionPressContainer;
        $this->committer = $versionPressContainer->resolve(VersionistServices::COMMITTER);
        $this->dbSchemaInfo = $versionPressContainer->resolve(VersionistServices::DB_SCHEMA);
        $this->vpidRepository = $versionPressContainer->resolve(VersionistServices::VPID_REPOSITORY);
        $this->wpdbMirrorBridge = $versionPressContainer->resolve(VersionistServices::WPDB_MIRROR_BRIDGE);
        $this->database = $versionPressContainer->resolve(VersionistServices::DATABASE);
        $this->actionsInfoProvider = $versionPressContainer->resolve(
            VersionistServices::ACTIONSINFO_PROVIDER_ACTIVE_PLUGINS
        );

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        vp_load_hooks_files();

        $this->registerUpdateHooks();
        $this->registerPluginHooks();
        $this->registerThemeHooks();
        $this->registerTranslationHooks();
        $this->registerEntityHooks();
        $this->detectRequestBasedChanges();
        $this->scheduleFrequentlyWrittenEntities();

        add_action('vp_revert', [$this, 'afterRevert']);

        register_shutdown_function([$this->committer, 'commit']);
    }

    /**
     * @param array $data Sanitized post data
     * @param array $postarr Raw post data
     * @return array
     */
    public function generatePostGuid($data, $postarr)
    {
        if (empty($postarr['ID'])) { // insert, not update
            $data['guid'] = (is_ssl() ? 'https://' : 'http://') . IdUtil::newUuid();
        }

        return $data;
    }

    public function afterRevert($modifiedFiles): void
    {
        // Changed rewrite rules take effect in the next request only.
        set_transient('vp_flush_rewrite_rules', 1);
        vp_flush_regenerable_options();

        // Sites managed by Composer get their dependencies installed for the reverted composer.lock.
        $composerChanged = in_array('composer.lock', $modifiedFiles, true)
            || in_array('composer.json', $modifiedFiles, true);
        if ($composerChanged && class_exists(\Composer\Console\Application::class)) {
            putenv('COMPOSER_HOME=' . VP_PROJECT_ROOT . '/vendor/bin/composer');
            $originalCwd = getcwd();
            chdir(VP_PROJECT_ROOT);

            $application = new \Composer\Console\Application();
            $application->setAutoExit(false);
            $application->run(
                // @phpstan-ignore class.notFound (Composer's Symfony Console, present when Composer is)
                new \Symfony\Component\Console\Input\ArrayInput(['command' => 'install']),
                // @phpstan-ignore class.notFound
                new \Symfony\Component\Console\Output\NullOutput()
            );
            chdir($originalCwd);
        }
    }

    private function registerUpdateHooks(): void
    {
        add_filter('update_feedback', function ($feedback) {
            touch(ABSPATH . 'versionpress.maintenance');
            return $feedback;
        });

        WordPressMissingFunctions::pipeAction('_core_updated_successfully', 'vp_wordpress_updated');
    }

    private function registerPluginHooks(): void
    {
        add_action('activated_plugin', function ($pluginFile) {
            $this->pluginChanged('activate', $pluginFile);
        });

        add_action('deactivated_plugin', function ($pluginFile) {
            $this->pluginChanged('deactivate', $pluginFile);
        });

        add_action('upgrader_process_complete', function ($upgrader, $hookExtra) {
            if ($hookExtra['type'] === 'theme') {
                $themes = (isset($hookExtra['bulk']) && $hookExtra['bulk'] === true)
                    ? $hookExtra['themes']
                    : [$upgrader->result['destination_name']];

                foreach ($themes as $stylesheet) {
                    $themeName = wp_get_theme($stylesheet)->get('Name');
                    if ($themeName === $stylesheet && isset($upgrader->skin->api, $upgrader->skin->api->name)) {
                        $themeName = $upgrader->skin->api->name;
                    }

                    // "install" or "update", see `'hook_extra' =>` in WP_Upgrader
                    do_action('vp_theme_changed', $hookExtra['action'], $stylesheet, $themeName);
                }
            }

            if (!($hookExtra['type'] === 'plugin' && $hookExtra['action'] === 'update')) {
                return; // plugin installations are handled by the `upgrader_pre_install` filter
            }

            $pluginFiles = (isset($hookExtra['bulk']) && $hookExtra['bulk'] === true)
                ? $hookExtra['plugins']
                : [$hookExtra['plugin']];

            foreach ($pluginFiles as $pluginFile) {
                $this->pluginChanged('update', $pluginFile);
            }
        }, 10, 2);

        add_filter('upgrader_pre_install', function ($response, $hookExtra) {
            if (!(isset($hookExtra['type']) && $hookExtra['type'] === 'plugin' && $hookExtra['action'] === 'install')) {
                return $response;
            }

            $pluginsBeforeInstallation = get_plugins();
            $postInstallHook = function ($response, $hookExtra) use ($pluginsBeforeInstallation, &$postInstallHook) {
                if (!($hookExtra['type'] === 'plugin' && $hookExtra['action'] === 'install')) {
                    return $response;
                }

                wp_cache_delete('plugins', 'plugins');
                $installedPlugins = array_diff_key(get_plugins(), $pluginsBeforeInstallation);

                foreach ($installedPlugins as $pluginFile => $plugin) {
                    do_action('vp_plugin_changed', 'install', $pluginFile, $plugin['Name']);
                }

                remove_filter('upgrader_post_install', $postInstallHook);
                return $response;
            };

            add_filter('upgrader_post_install', $postInstallHook, 10, 2);
            return $response;
        }, 10, 2);
    }

    private function pluginChanged(string $action, string $pluginFile): void
    {
        $plugins = get_plugins();
        $pluginName = $plugins[$pluginFile]['Name'] ?? $pluginFile;
        do_action('vp_plugin_changed', $action, $pluginFile, $pluginName);
    }

    private function registerThemeHooks(): void
    {
        add_action('switch_theme', function () {
            if (defined('WP_CLI') && WP_CLI) {
                wp_remote_get(admin_url());
            } else {
                $this->committer->disableCommit(); // the change will be committed on the next load
            }
        });

        add_action('after_switch_theme', function () {
            $theme = wp_get_theme();
            do_action('vp_theme_changed', 'switch', $theme->get_stylesheet(), $theme->get('Name'));
        });
    }

    private function registerTranslationHooks(): void
    {
        add_filter('upgrader_pre_download', function ($reply, $_, $upgrader) {
            if (!isset($upgrader->skin->language_update)) {
                return $reply;
            }

            $languages = get_available_languages();

            $postInstallHook = function ($response, $hookExtra) use ($languages, &$postInstallHook) {
                if (!isset($hookExtra['language_update_type'])) {
                    return $response;
                }

                $type = $hookExtra['language_update_type'];
                $languageCode = $hookExtra['language_update']->language;
                $name = $type === "core" ? null : $hookExtra['language_update']->slug;
                $action = in_array($languageCode, $languages, true) ? "update" : "install";

                do_action('vp_translation_changed', $action, $languageCode, $type, $name);

                remove_filter('upgrader_post_install', $postInstallHook);
                return $response;
            };

            add_filter('upgrader_post_install', $postInstallHook, 10, 2);
            return $reply;
        }, 10, 3);

        add_action('add_option_WPLANG', function ($option, $value) {
            $defaultLanguage = defined('WPLANG') ? WPLANG : '';
            if ($value === $defaultLanguage) {
                return; // just the settings form saved without changing the language
            }

            do_action('vp_translation_changed', 'activate', $value);
        }, 10, 2);

        add_action('update_option_WPLANG', function ($oldValue, $newValue) {
            do_action('vp_translation_changed', 'activate', $newValue);
        }, 10, 2);
    }

    private function registerEntityHooks(): void
    {
        add_action('wp_update_nav_menu_item', function ($menuId, $menuItemDbId) {
            // Read-only: WordPress verifies the nonce of these requests itself.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
            $key = 'menu-item-' . $menuItemDbId;

            if (wp_doing_ajax() && $action === 'add-menu-item') {
                $this->committer->postponeCommit($key);
                $this->committer->commit();
            } elseif ($action === 'update') {
                $this->committer->usePostponedChangeInfos($key);
            }
        }, 10, 2);

        add_action('pre_delete_term', function ($termId, $taxonomy) {
            $term = get_term($termId, $taxonomy);
            $changeInfo = new EntityChangeInfo(
                $this->dbSchemaInfo->getEntityInfo('term'),
                $this->actionsInfoProvider->getActionsInfo('term'),
                'delete',
                $this->vpidRepository->getVpidForEntity('term', $termId),
                ['VP-Term-Name' => $term->name, 'VP-Term-Taxonomy' => $taxonomy]
            );
            $this->committer->forceChangeInfo($changeInfo);
        }, 10, 2);

        add_action('pre_delete_term', function ($termId, $taxonomy) {
            // Children of a deleted hierarchical term move to its parent.
            if (!is_taxonomy_hierarchical($taxonomy)) {
                return;
            }

            $term = get_term($termId, $taxonomy);
            if (is_wp_error($term)) {
                return;
            }

            $this->wpdbMirrorBridge->update(
                $this->database->term_taxonomy,
                ['parent' => $term->parent],
                ['parent' => $term->term_id]
            );
        }, 10, 2);

        add_filter('wp_save_image_editor_file', function ($saved, $filename, $image, $mimeType, $postId) {
            $post = get_post($postId);
            $changeInfo = new EntityChangeInfo(
                $this->dbSchemaInfo->getEntityInfo('post'),
                $this->actionsInfoProvider->getActionsInfo('post'),
                'update',
                $this->vpidRepository->getVpidForEntity('post', $postId),
                ['VP-Post-Type' => $post->post_type, 'VP-Post-Title' => $post->post_title]
            );
            $this->committer->forceChangeInfo($changeInfo);
            return $saved;
        }, 10, 5);

        add_action('before_delete_post', function ($postId) {
            // Works around WordPress #34803 and WP-CLI #2246: children of a deleted menu item
            // are not moved to its parent.
            $post = get_post($postId);
            if (!$post || $post->post_type !== 'nav_menu_item') {
                return;
            }

            $newParent = get_post_meta($post->ID, '_menu_item_menu_item_parent', true);
            // One-off when a menu item is deleted.
            // phpcs:disable WordPress.DB.SlowDBQuery
            $data = ['meta_value' => $newParent];
            $where = ['meta_key' => '_menu_item_menu_item_parent', 'meta_value' => $post->ID];
            // phpcs:enable
            $this->wpdbMirrorBridge->update($this->database->postmeta, $data, $where);
            $this->database->update($this->database->postmeta, $data, $where);
        });
    }

    /**
     * Some changes are only recognizable from the request that triggers them (deleting a plugin or
     * theme removes its files without any hook telling which ones).
     */
    private function detectRequestBasedChanges(): void
    {
        global $pagenow;

        $requestDetector = new RequestDetector();

        if ($requestDetector->isThemeDeleteRequest()) {
            foreach ($requestDetector->getThemeStylesheets() as $stylesheet) {
                do_action('vp_theme_changed', 'delete', $stylesheet, wp_get_theme($stylesheet)->get('Name'));
            }
        }

        if ($requestDetector->isPluginDeleteRequest()) {
            $plugins = get_plugins();
            foreach ($requestDetector->getPluginNames() as $plugin) {
                do_action('vp_plugin_changed', 'delete', $plugin, $plugins[$plugin]['Name'] ?? $plugin);
            }
        }

        if ($requestDetector->isCoreLanguageUninstallRequest()) {
            do_action('vp_translation_changed', 'uninstall', $requestDetector->getLanguageCode());
        }

        // The remaining checks only read the request to learn what WordPress has done or is about to do;
        // WordPress verifies the nonces of these screens itself.
        // phpcs:disable WordPress.Security.NonceVerification
        if ($pagenow === 'theme-editor.php' && isset($_GET['updated'], $_GET['theme']) && $_GET['updated'] === 'true') {
            $stylesheet = sanitize_text_field(wp_unslash($_GET['theme']));
            do_action('vp_theme_changed', 'update', $stylesheet, wp_get_theme($stylesheet)->get('Name'));
        }

        $isPluginEditorSave = isset($_POST['action']) && $_POST['action'] === 'update';
        if ($pagenow === 'plugin-editor.php' && ($isPluginEditorSave || isset($_GET['liveupdate']))) {
            $this->committer->disableCommit();
        }

        if ($pagenow === 'plugin-editor.php' && isset($_GET['a'], $_GET['file']) && $_GET['a'] === 'te') {
            $this->pluginFileEdited(sanitize_text_field(wp_unslash($_GET['file'])));
        }
        // phpcs:enable
    }

    /**
     * @param string $editedFile Path relative to the plugins directory
     */
    private function pluginFileEdited(string $editedFile): void
    {
        $editedFilePathParts = preg_split("~[/\\\\]~", $editedFile);
        $plugins = get_plugins();
        $bestRank = 0;
        $bestMatch = "";

        foreach (array_keys($plugins) as $plugin) {
            $pluginPathParts = preg_split("~[/\\\\]~", $plugin);
            $maxEqualParts = min(count($editedFilePathParts), count($pluginPathParts));

            $rank = 0;
            while ($rank < $maxEqualParts && $editedFilePathParts[$rank] === $pluginPathParts[$rank]) {
                $rank++;
            }

            if ($rank > $bestRank) {
                $bestRank = $rank;
                $bestMatch = $plugin;
            }
        }

        if ($bestMatch !== '') {
            do_action('vp_plugin_changed', 'update', $bestMatch, $plugins[$bestMatch]['Name']);
        }
    }

    /**
     * Entities written very often (e.g. counters) are committed periodically instead of on every change.
     */
    private function scheduleFrequentlyWrittenEntities(): void
    {
        add_filter('cron_schedules', function ($schedules) {
            foreach ($this->dbSchemaInfo->getIntervalsForFrequentlyWrittenEntities() as $interval) {
                if (!isset($schedules[$interval])) {
                    $schedules[$interval] = ['interval' => strtotime($interval, 0), 'display' => $interval];
                }
            }
            return $schedules;
        });

        $rulesByInterval = [];
        foreach ($this->dbSchemaInfo->getRulesForFrequentlyWrittenEntities() as $entityName => $rules) {
            foreach ($rules as $rule) {
                $rulesByInterval[$rule['interval']][$entityName][] = $rule;
            }
        }

        foreach ($rulesByInterval as $interval => $rules) {
            $actionName = "vp_commit_frequently_written_entities_$interval";
            if (!wp_next_scheduled($actionName)) {
                wp_schedule_event(time(), $interval, $actionName);
            }

            add_action($actionName, function () use ($rules) {
                vp_save_frequently_written_entities($rules);
            });
        }
    }
}
