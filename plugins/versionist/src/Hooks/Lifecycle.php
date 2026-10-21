<?php

namespace Versionist\Hooks;

use Versionist\Admin\AdminScreen;
use Versionist\Git\MergeDriverInstaller;
use Versionist\Initialization\DbDropIn;
use Versionist\Initialization\WpConfigSplitter;
use Versionist\Utils\FileSystem;
use Versionist\Utils\SecurityUtils;
use Versionist\Utils\UninstallationUtil;
use Versionist\Utils\WordPressMissingFunctions;
use Versionist\Versionist;

/**
 * Activation, deactivation and keeping an active installation in a consistent state.
 * Uninstallation is handled in `uninstall.php`.
 */
class Lifecycle
{
    /**
     * Nonce action of the deactivation confirmation; the same WordPress uses for the Deactivate link.
     */
    public static function getDeactivationNonceAction(): string
    {
        return 'deactivate-plugin_' . plugin_basename(VERSIONIST_PLUGIN_FILE);
    }

    public function register(): void
    {
        if (defined('VP_MAINTENANCE')) {
            vp_disable_maintenance();
        }

        $this->cleanUpAbortedInitialization();

        register_activation_hook(VERSIONIST_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(VERSIONIST_PLUGIN_FILE, [$this, 'onDeactivation']);
        add_action('admin_post_cancel_deactivation', [$this, 'cancelDeactivation']);
        add_action('admin_post_confirm_deactivation', [$this, 'confirmDeactivation']);

        // WordPress doesn't auto-update sites under version control; Versionist tracks the update instead.
        add_filter('automatic_updates_is_vcs_checkout', function () {
            return !Versionist::isActive();
        });

        if (Versionist::isActive()) {
            register_shutdown_function([$this, 'repairInstallation']);
            add_action('wp_loaded', [$this, 'flushRewriteRulesAfterSynchronization']);
        }
    }

    /**
     * Activates the plugin from a WordPress point of view. Versionist starts tracking the site
     * only after it is initialized on its admin screen or by `wp versionist activate`.
     */
    public function activate(): void
    {
        WpConfigSplitter::split(WordPressMissingFunctions::getWpConfigPath());
        set_transient('vp_just_activated', '1', 10);
    }

    /**
     * Deactivation of an active Versionist has a confirmation screen, see `admin/deactivate.php`.
     */
    public function onDeactivation(): void
    {
        if ((defined('WP_CLI') && WP_CLI) || !Versionist::isActive()) {
            self::deactivate();
            return;
        }

        wp_safe_redirect(admin_url('admin.php?page=' . AdminScreen::getDirectAccessPageSlug('deactivate.php')));
        exit();
    }

    public function cancelDeactivation(): void
    {
        wp_safe_redirect(admin_url('plugins.php'));
        exit();
    }

    public function confirmDeactivation(): void
    {
        check_admin_referer(self::getDeactivationNonceAction());
        if (!current_user_can('activate_plugins')) {
            wp_die(esc_html__('Sorry, you are not allowed to deactivate plugins for this site.', 'versionist'), 403);
        }

        self::deactivate();
    }

    /**
     * Stops tracking the site and deactivates the plugin. The Git repository is kept.
     */
    public static function deactivate(): void
    {
        define('VP_DEACTIVATING', true);

        DbDropIn::uninstall();

        if (file_exists(VERSIONIST_ACTIVATION_FILE)) {
            FileSystem::remove(VERSIONIST_ACTIVATION_FILE);
        }

        vp_force_action('versionpress', 'deactivate', null, [], [
            ["type" => "path", "path" => VP_VPDB_DIR . "/*"],
            ["type" => "path", "path" => DbDropIn::getPath()],
            ["type" => "path", "path" => ABSPATH . "/.gitattributes"],
        ]);

        MergeDriverInstaller::uninstallMergeDriver(VP_PROJECT_ROOT, VERSIONIST_PLUGIN_DIR, VP_VPDB_DIR);

        deactivate_plugins(plugin_basename(VERSIONIST_PLUGIN_FILE), true);

        if (defined('WP_ADMIN')) {
            wp_safe_redirect(admin_url('plugins.php'));
            exit();
        }
    }

    /**
     * Restores what an active installation needs if it went missing, typically on the first request
     * after updating from an older Versionist. Registered before the committer's shutdown function,
     * so the changes are committed in the same request.
     */
    public function repairInstallation(): void
    {
        if (defined('VP_DEACTIVATING') || !Versionist::isActive()) {
            return;
        }

        if (!DbDropIn::isInstalled() && !DbDropIn::isOccupiedByAnotherDropIn()) {
            // Replaces the class-wpdb.php patch of older versions.
            DbDropIn::install();
            vp_force_action('versionpress', 'update', Versionist::getVersion(), [], [
                ["type" => "path", "path" => DbDropIn::getPath()],
                ["type" => "path", "path" => ABSPATH . WPINC . "/class-wpdb.php"],
                ["type" => "path", "path" => ABSPATH . WPINC . "/class-wpdb.php.original"],
            ]);
        }

        if (is_dir(VERSIONIST_TEMP_DIR) && !file_exists(VERSIONIST_TEMP_DIR . '/.htaccess')) {
            SecurityUtils::protectDirectory(VERSIONIST_TEMP_DIR);
        }
    }

    /**
     * Rewrite rules changed by undo, rollback, clone or pull only take effect in the next request.
     */
    public function flushRewriteRulesAfterSynchronization(): void
    {
        if (get_transient('vp_flush_rewrite_rules') && !defined('WP_CLI')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            flush_rewrite_rules();
            delete_transient('vp_flush_rewrite_rules');
        }
    }

    /**
     * An initialization stopped because of the time limit leaves a marker; clean up what it created.
     */
    private function cleanUpAbortedInitialization(): void
    {
        $marker = VERSIONIST_TEMP_DIR . '/.abort-initialization';
        if (Versionist::isActive() || !is_file($marker)) {
            return;
        }

        if (UninstallationUtil::uninstallationShouldRemoveGitRepo()) {
            FileSystem::remove(VP_PROJECT_ROOT . '/.git');
        }

        FileSystem::remove(VP_VPDB_DIR);
        unlink($marker);
    }
}
