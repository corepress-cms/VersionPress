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
        wp_die('<h1>VersionPress could not be activated</h1>
            <p>
                It seems that your copy of VersionPress was not built correctly.
                Please download <a href="https://github.com/versionpress/versionpress/releases">release ZIP file
                from GitHub</a> and <a href="' . get_admin_url() . 'plugin-install.php?tab=upload">install it again</a>.
            </p>');
    }

    return;
}

require_once(__DIR__ . '/bootstrap.php');
require_once(__DIR__ . '/setup-hooks.php');
