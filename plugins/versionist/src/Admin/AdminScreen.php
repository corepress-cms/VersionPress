<?php

namespace Versionist\Admin;

use Versionist\Versionist;

/**
 * The Versionist admin screen: a React app (admin-ui/) that shows
 * the activation screen or the site history. Built to admin/public/build/, which is web-accessible. Also registers the deactivation and system info pages.
 */
class AdminScreen
{
    public const PAGE_SLUG = 'versionist';

    private const SCRIPT_HANDLE = 'versionist-admin';

    /** Pages loaded via `admin.php?page=<plugin folder>/admin/<file>` without a menu item. */
    private const DIRECT_ACCESS_PAGES = ['deactivate.php', 'system-info.php'];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            'Versionist',
            'Versionist',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
            'dashicons-backup',
            0.001234987
        );

        global $_registered_pages;
        foreach (self::DIRECT_ACCESS_PAGES as $page) {
            $hookName = get_plugin_page_hookname(self::getDirectAccessPageSlug($page), '');
            $_registered_pages[$hookName] = true;
        }
    }

    public function render(): void
    {
        printf(
            '<div class="wrap"><div id="versionist-admin" data-active="%s"></div></div>',
            Versionist::isActive() ? '1' : '0'
        );

        if (!is_file(self::getBuildDir() . '/index.asset.php')) {
            wp_admin_notice(
                esc_html__(
                    'The Versionist admin screen has not been built. Run `pnpm run build:admin`.',
                    'versionist'
                ),
                ['type' => 'error']
            );
        }
    }

    public function enqueueAssets($hookSuffix): void
    {
        // Styles of the deactivation and system info pages, notices and plugin compatibility labels.
        wp_enqueue_style(
            'versionist-admin-common',
            plugins_url('admin/public/css/style.css', VERSIONIST_PLUGIN_FILE),
            [],
            Versionist::getVersion()
        );

        $assetFile = self::getBuildDir() . '/index.asset.php';
        if ($hookSuffix !== 'toplevel_page_' . self::PAGE_SLUG || !is_file($assetFile)) {
            return;
        }

        $asset = require $assetFile;

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            plugins_url('admin/public/build/index.js', VERSIONIST_PLUGIN_FILE),
            $asset['dependencies'],
            $asset['version'],
            ['in_footer' => true]
        );
        wp_set_script_translations(self::SCRIPT_HANDLE, 'versionist');

        wp_enqueue_style(
            self::SCRIPT_HANDLE,
            plugins_url('admin/public/build/style-index.css', VERSIONIST_PLUGIN_FILE),
            ['wp-components'],
            $asset['version']
        );
    }

    /**
     * Value of the `page` query argument of a page in admin/ without a menu item.
     *
     * @param string $file E.g. `deactivate.php`
     */
    public static function getDirectAccessPageSlug(string $file): string
    {
        return dirname(plugin_basename(VERSIONIST_PLUGIN_FILE)) . '/admin/' . $file;
    }

    private static function getBuildDir(): string
    {
        return VERSIONIST_PLUGIN_DIR . '/admin/public/build';
    }
}
