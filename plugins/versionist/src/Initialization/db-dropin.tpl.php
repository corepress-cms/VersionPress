<?php

/**
 * Versionist database drop-in
 *
 * Lets Versionist track database changes. It is installed when Versionist is activated and
 * removed when it is deactivated. Don't edit this file; it is regenerated as needed.
 */

defined('ABSPATH') || exit;

$vp_bootstrap = (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins')
    . '/{{plugin-dir}}/bootstrap.php';

// If Versionist was removed without being deactivated, WordPress falls back to its stock wpdb.
if (file_exists($vp_bootstrap)) {
    require_once $vp_bootstrap;

    if (\Versionist\Versionist::isActive()) {
        $wpdb = new \Versionist\Database\VersionistWpdb(
            defined('DB_USER') ? DB_USER : '',
            defined('DB_PASSWORD') ? DB_PASSWORD : '',
            defined('DB_NAME') ? DB_NAME : '',
            defined('DB_HOST') ? DB_HOST : ''
        );
    }
}

unset($vp_bootstrap);
