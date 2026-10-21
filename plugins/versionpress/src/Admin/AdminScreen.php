<?php

namespace VersionPress\Admin;

use VersionPress\VersionPress;

/**
 * The VersionPress admin screen: a React app (admin-ui/) that shows
 * the activation screen or the site history. Built to admin/public/build/, which is web-accessible. Also registers the deactivation and system info pages.
 */
class AdminScreen
{
    public const PAGE_SLUG = 'versionpress';

    private const SCRIPT_HANDLE = 'versionpress-admin';

    /** Pages loaded via `admin.php?page=versionpress/admin/<file>` without a menu item. */
    private const DIRECT_ACCESS_PAGES = ['deactivate.php', 'system-info.php'];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'VersionPress',
            'VersionPress',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
            'dashicons-backup',
            0.001234987
        );

        global $_registered_pages;
        foreach (self::DIRECT_ACCESS_PAGES as $page) {
            $hookName = get_plugin_page_hookname(plugin_basename("versionpress/admin/$page"), '');
            $_registered_pages[$hookName] = true;
        }
    }

    public function render(): void
    {
        printf(
            '<div class="wrap"><div id="versionpress-admin" data-active="%s"></div></div>',
            VersionPress::isActive() ? '1' : '0'
        );

        if (!is_file(self::getBuildDir() . '/index.asset.php')) {
            wp_admin_notice(
                esc_html__(
                    'The VersionPress admin screen has not been built. Run `pnpm run build:admin`.',
                    'versionpress'
                ),
                ['type' => 'error']
            );
        }
    }

    public function enqueueAssets($hookSuffix): void
    {
        // Styles of the deactivation and system info pages, notices and plugin compatibility labels.
        wp_enqueue_style(
            'versionpress-admin-common',
            plugins_url('admin/public/css/style.css', VERSIONPRESS_PLUGIN_FILE),
            [],
            VersionPress::getVersion()
        );

        $assetFile = self::getBuildDir() . '/index.asset.php';
        if ($hookSuffix !== 'toplevel_page_' . self::PAGE_SLUG || !is_file($assetFile)) {
            return;
        }

        $asset = require $assetFile;

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            plugins_url('admin/public/build/index.js', VERSIONPRESS_PLUGIN_FILE),
            $asset['dependencies'],
            $asset['version'],
            ['in_footer' => true]
        );
        wp_set_script_translations(self::SCRIPT_HANDLE, 'versionpress');

        wp_enqueue_style(
            self::SCRIPT_HANDLE,
            plugins_url('admin/public/build/style-index.css', VERSIONPRESS_PLUGIN_FILE),
            ['wp-components'],
            $asset['version']
        );
    }

    private static function getBuildDir(): string
    {
        return VERSIONPRESS_PLUGIN_DIR . '/admin/public/build';
    }
}
