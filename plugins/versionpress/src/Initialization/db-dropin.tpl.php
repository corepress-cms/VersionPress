<?php

/**
 * VersionPress database drop-in
 *
 * Lets VersionPress track database changes. It is installed when VersionPress is activated and
 * removed when it is deactivated. Don't edit this file; it is regenerated as needed.
 */

defined('ABSPATH') || exit;

$vp_bootstrap = (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins')
    . '/{{versionpress-dir}}/bootstrap.php';

// If VersionPress was removed without being deactivated, WordPress falls back to its stock wpdb.
if (file_exists($vp_bootstrap)) {
    require_once $vp_bootstrap;

    if (\VersionPress\VersionPress::isActive()) {
        $wpdb = new \VersionPress\Database\VersionPressWpdb(
            defined('DB_USER') ? DB_USER : '',
            defined('DB_PASSWORD') ? DB_PASSWORD : '',
            defined('DB_NAME') ? DB_NAME : '',
            defined('DB_HOST') ? DB_HOST : ''
        );
    }
}

unset($vp_bootstrap);
