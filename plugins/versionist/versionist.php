<?php

/**
 * Plugin Name:       Versionist
 * Description:       Git-powered version control for WordPress: history, undo, rollback and environment sync. A fork of VersionPress.
 * Version:           DEV
 * Requires at least: 7.1
 * Requires PHP:      8.5
 * Author:            Versionist contributors
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       versionist
 */

defined('ABSPATH') or die("Direct access not allowed");

/** Useful for registering hooks; may already be defined by bootstrap.php, which the db.php drop-in loads earlier. */
defined('VERSIONIST_PLUGIN_FILE') || define('VERSIONIST_PLUGIN_FILE', __FILE__);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    register_activation_hook(__FILE__, 'vp_disable_plugin_activation');

    function vp_disable_plugin_activation()
    {
        wp_die(
            '<h1>' . esc_html__('Versionist could not be activated', 'versionist') . '</h1><p>' .
            sprintf(
                /* translators: %s: URL of the plugin upload screen */
                wp_kses_post(__('This copy of Versionist was not built (it is missing its dependencies). Please <a href="%s">install a release ZIP file</a> instead.', 'versionist')),
                esc_url(admin_url('plugin-install.php?tab=upload'))
            ) . '</p>'
        );
    }

    return;
}

require_once(__DIR__ . '/bootstrap.php');

\Versionist\Plugin::boot();
