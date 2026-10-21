<?php

/**
 * Plugin Name:       VersionPress
 * Plugin URI:        https://versionpress.net/
 * Description:       Git-versioning plugin for WordPress
 * Version:           DEV
 * Requires at least: 7.1
 * Requires PHP:      8.5
 * Author:            VersionPress
 * Author URI:        https://versionpress.net/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       versionpress
 */

defined('ABSPATH') or die("Direct access not allowed");

/** Useful for registering hooks in setup-hooks.php */
define('VERSIONPRESS_PLUGIN_FILE', __FILE__);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    register_activation_hook(__FILE__, 'vp_disable_plugin_activation');

    function vp_disable_plugin_activation()
    {
        wp_die(
            '<h1>' . esc_html__('VersionPress could not be activated', 'versionpress') . '</h1><p>' .
            sprintf(
                /* translators: 1: URL of the releases page, 2: URL of the plugin upload screen */
                wp_kses_post(__('It seems that your copy of VersionPress was not built correctly. Please download the <a href="%1$s">release ZIP file</a> and <a href="%2$s">install it again</a>.', 'versionpress')),
                'https://github.com/versionpress/versionpress/releases',
                esc_url(admin_url('plugin-install.php?tab=upload'))
            ) . '</p>'
        );
    }

    return;
}

require_once(__DIR__ . '/bootstrap.php');

\VersionPress\Plugin::boot();
