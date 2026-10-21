<?php

namespace VersionPress\Hooks;

use VersionPress\Git\MergeDriverInstaller;
use VersionPress\Initialization\DbDropIn;
use VersionPress\Initialization\WpConfigSplitter;
use VersionPress\Utils\FileSystem;
use VersionPress\Utils\SecurityUtils;
use VersionPress\Utils\UninstallationUtil;
use VersionPress\Utils\WordPressMissingFunctions;
use VersionPress\VersionPress;

/**
 * Activation, deactivation and keeping an active installation in a consistent state.
 * Uninstallation is handled in `uninstall.php`.
 */
class Lifecycle
{
    /** Nonce action of the deactivation confirmation; the same WordPress uses for the Deactivate link. */
    public const DEACTIVATION_NONCE = 'deactivate-plugin_versionpress/versionpress.php';

    public function register(): void
    {
        if (defined('VP_MAINTENANCE')) {
            vp_disable_maintenance();
        }

        $this->cleanUpAbortedInitialization();

        register_activation_hook(VERSIONPRESS_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(VERSIONPRESS_PLUGIN_FILE, [$this, 'onDeactivation']);
        add_action('admin_post_cancel_deactivation', [$this, 'cancelDeactivation']);
        add_action('admin_post_confirm_deactivation', [$this, 'confirmDeactivation']);

        // WordPress doesn't auto-update sites under version control; VersionPress tracks the update instead.
        add_filter('automatic_updates_is_vcs_checkout', function () {
            return !VersionPress::isActive();
        });

        if (VersionPress::isActive()) {
            register_shutdown_function([$this, 'repairInstallation']);
            add_action('wp_loaded', [$this, 'flushRewriteRulesAfterSynchronization']);
        }
    }

    /**
     * Activates the plugin from a WordPress point of view. VersionPress starts tracking the site
     * only after it is initialized on its admin screen or by `wp vp activate`.
     */
    public function activate(): void
    {
        WpConfigSplitter::split(WordPressMissingFunctions::getWpConfigPath());
        set_transient('vp_just_activated', '1', 10);
    }

    /**
     * Deactivation of an active VersionPress has a confirmation screen, see `admin/deactivate.php`.
     */
    public function onDeactivation(): void
    {
        if ((defined('WP_CLI') && WP_CLI) || !VersionPress::isActive()) {
            self::deactivate();
            return;
        }

        wp_safe_redirect(admin_url('admin.php?page=versionpress/admin/deactivate.php'));
        exit();
    }

    public function cancelDeactivation(): void
    {
        wp_safe_redirect(admin_url('plugins.php'));
        exit();
    }

    public function confirmDeactivation(): void
    {
        check_admin_referer(self::DEACTIVATION_NONCE);
        if (!current_user_can('activate_plugins')) {
            wp_die(esc_html__('Sorry, you are not allowed to deactivate plugins for this site.', 'versionpress'), 403);
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

        if (file_exists(VERSIONPRESS_ACTIVATION_FILE)) {
            FileSystem::remove(VERSIONPRESS_ACTIVATION_FILE);
        }

        vp_force_action('versionpress', 'deactivate', null, [], [
            ["type" => "path", "path" => VP_VPDB_DIR . "/*"],
            ["type" => "path", "path" => DbDropIn::getPath()],
            ["type" => "path", "path" => ABSPATH . "/.gitattributes"],
        ]);

        MergeDriverInstaller::uninstallMergeDriver(VP_PROJECT_ROOT, VERSIONPRESS_PLUGIN_DIR, VP_VPDB_DIR);

        deactivate_plugins(plugin_basename(VERSIONPRESS_PLUGIN_FILE), true);

        if (defined('WP_ADMIN')) {
            wp_safe_redirect(admin_url('plugins.php'));
            exit();
        }
    }

    /**
     * Restores what an active installation needs if it went missing, typically on the first request
     * after updating from an older VersionPress. Registered before the committer's shutdown function,
     * so the changes are committed in the same request.
     */
    public function repairInstallation(): void
    {
        if (defined('VP_DEACTIVATING') || !VersionPress::isActive()) {
            return;
        }

        if (!DbDropIn::isInstalled() && !DbDropIn::isOccupiedByAnotherDropIn()) {
            // Replaces the class-wpdb.php patch of older versions.
            DbDropIn::install();
            vp_force_action('versionpress', 'update', VersionPress::getVersion(), [], [
                ["type" => "path", "path" => DbDropIn::getPath()],
                ["type" => "path", "path" => ABSPATH . WPINC . "/class-wpdb.php"],
                ["type" => "path", "path" => ABSPATH . WPINC . "/class-wpdb.php.original"],
            ]);
        }

        if (is_dir(VERSIONPRESS_TEMP_DIR) && !file_exists(VERSIONPRESS_TEMP_DIR . '/.htaccess')) {
            SecurityUtils::protectDirectory(VERSIONPRESS_TEMP_DIR);
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
        $marker = VERSIONPRESS_TEMP_DIR . '/.abort-initialization';
        if (VersionPress::isActive() || !is_file($marker)) {
            return;
        }

        if (UninstallationUtil::uninstallationShouldRemoveGitRepo()) {
            FileSystem::remove(VP_PROJECT_ROOT . '/.git');
        }

        FileSystem::remove(VP_VPDB_DIR);
        unlink($marker);
    }
}
