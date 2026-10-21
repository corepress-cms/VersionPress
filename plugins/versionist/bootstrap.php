<?php

use Versionist\DI\DIContainer;

// Absolute path to the directory containing Versionist (wp-content/plugins/versionist).
define('VERSIONIST_PLUGIN_DIR', __DIR__);

// Already defined when loaded through the main plugin file; CLI commands running with SHORTINIT load only this file.
defined('VERSIONIST_PLUGIN_FILE') || define('VERSIONIST_PLUGIN_FILE', __DIR__ . '/versionist.php');

if (!defined('VP_VPDB_DIR')) {
    /**
     * Absolute path to the directory where Versionist saves versioned database data.
     */
    define('VP_VPDB_DIR', WP_CONTENT_DIR . '/vpdb');
}

/**
 * Absolute path to the directory where Versionist saves temporary data, e.g. mutex locks.
 */
define('VERSIONIST_TEMP_DIR', WP_CONTENT_DIR . '/vpcache');

/**
 * Absolute path to the activation file.
 */
define('VERSIONIST_ACTIVATION_FILE', VP_VPDB_DIR . '/.active');

if (!defined('VP_PROJECT_ROOT')) {
    /**
     * Absolute path to the root of project - directory with .git.
     */
    define('VP_PROJECT_ROOT', ABSPATH);
}

if (!defined('VP_GIT_BINARY')) {
    /**
     * Absolute path to the git executable. Useful if it's not in PATH.
     */
    define('VP_GIT_BINARY', 'git');
}

if (!defined('VP_WP_CLI_BINARY')) {
    /**
     * Absolute path to the WP-CLI executable. Useful if it's not in PATH.
     */
    define('VP_WP_CLI_BINARY', 'wp');
}



if (!defined('VERSIONIST_COMMIT_MESSAGE_PREFIX')) {
    /**
     * Prefix added in front of each commit message subject created by Versionist
     */
    define('VERSIONIST_COMMIT_MESSAGE_PREFIX', '[VP] ');
}

require_once(VERSIONIST_PLUGIN_DIR . '/vendor/autoload.php');
require_once(VERSIONIST_PLUGIN_DIR . '/internal-functions.php');

if (defined('DOING_AJAX')) {
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

// Tracy is a dev dependency, so it is only available in development checkouts.
// Logs go to the (web-protected) temp directory; the plugin directory is replaced on every update.
if (defined('VP_DEBUG') && VP_DEBUG && class_exists(\Tracy\Debugger::class)) {
    $vpLogDir = VERSIONIST_TEMP_DIR . '/log';
    if (!is_dir($vpLogDir)) {
        mkdir($vpLogDir, 0750, true);
    }
    \Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, $vpLogDir);
    unset($vpLogDir);
}

global $versionPressContainer;
$versionPressContainer = DIContainer::getConfiguredInstance();
