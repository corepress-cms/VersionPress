<?php

namespace Versionist\Admin;

use Versionist\Initialization\DbDropIn;
use Versionist\Versionist;
use WP_Admin_Bar;

/**
 * Admin notices and the admin bar item.
 */
class Notices
{
    public function register(): void
    {
        // The WordPress update nag has priority 3; finishing the activation is just a step less important.
        add_action('admin_notices', [$this, 'activationNag'], 4);
        add_action('admin_notices', [$this, 'dropInConflictNotice']);
        add_action('after_plugin_row_' . plugin_basename(VERSIONIST_PLUGIN_FILE), [$this, 'pluginRowNotice'], 10, 2);
        add_action('admin_bar_menu', [$this, 'adminBarItem'], 100);

        if (get_transient('vp_just_activated')) {
            add_filter('gettext', [$this, 'replacePluginActivatedMessage'], 10, 3);
        }
    }

    public function activationNag(): void
    {
        $screen = get_current_screen();
        $isVersionistScreen = $screen && str_contains($screen->id, AdminScreen::PAGE_SLUG);

        if (Versionist::isActive() || $isVersionistScreen || get_transient('vp_just_activated')) {
            return;
        }

        wp_admin_notice($this->finishActivationText(), ['type' => 'warning', 'additional_classes' => ['vp-activation-nag']]);
    }

    /**
     * Another plugin's `wp-content/db.php` means that Versionist cannot track database changes.
     */
    public function dropInConflictNotice(): void
    {
        if (!DbDropIn::isOccupiedByAnotherDropIn() || !current_user_can('manage_options')) {
            return;
        }

        wp_admin_notice(
            sprintf(
                /* translators: %s: path to the db.php drop-in file */
                esc_html__(
                    'Versionist is not tracking database changes because %s belongs to another plugin. Remove that drop-in to resume tracking.',
                    'versionist'
                ),
                '<code>' . esc_html(DbDropIn::getPath()) . '</code>'
            ),
            ['type' => 'error']
        );
    }

    public function pluginRowNotice($file, $pluginData): void
    {
        if (Versionist::isActive()) {
            return;
        }

        $listTable = _get_list_table('WP_Plugins_List_Table');
        printf(
            '<tr class="plugin-update-tr vp-plugin-update-tr"><td colspan="%d" class="plugin-update colspanchange">' .
            '<div class="update-message notice inline notice-warning notice-alt"><p>%s</p></div></td></tr>',
            (int)$listTable->get_column_count(),
            wp_kses_post($this->finishActivationText())
        );
    }

    public function adminBarItem(WP_Admin_Bar $adminBar): void
    {
        if (!Versionist::isActive() || !current_user_can('manage_options')) {
            return;
        }

        $adminBar->add_node([
            'id' => 'versionist',
            'parent' => 'top-secondary',
            'title' => esc_html__('Versionist', 'versionist'),
            'href' => menu_page_url(AdminScreen::PAGE_SLUG, false),
            'meta' => [
                'title' => sprintf(
                    /* translators: %s: Versionist version */
                    __('Versionist %s is tracking this site. It is a Developer Preview; keep a backup of the site.', 'versionist'),
                    Versionist::getVersion()
                ),
            ],
        ]);
    }

    /**
     * Points to the Versionist screen from the "Plugin activated" message.
     *
     * @param string $translation
     * @param string $text
     * @param string $domain
     * @return string
     */
    public function replacePluginActivatedMessage($translation, $text, $domain)
    {
        if ($domain !== 'default' || $text !== 'Plugin activated.' || !get_transient('vp_just_activated')) {
            return $translation;
        }

        delete_transient('vp_just_activated');
        return sprintf(
            /* translators: %s: URL of the Versionist screen */
            __('Versionist activated. <a href="%s">Continue here</a> to start tracking the site.', 'versionist'),
            esc_url(menu_page_url(AdminScreen::PAGE_SLUG, false))
        );
    }

    private function finishActivationText(): string
    {
        return sprintf(
            /* translators: %s: URL of the Versionist screen */
            __('Versionist is installed but not yet tracking this site. <a href="%s">Please finish the activation.</a>', 'versionist'),
            esc_url(menu_page_url(AdminScreen::PAGE_SLUG, false))
        );
    }
}
