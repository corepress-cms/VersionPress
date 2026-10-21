<?php

namespace VersionPress;

use VersionPress\Admin\AdminScreen;
use VersionPress\Admin\Notices;
use VersionPress\Admin\PluginCompatibility;
use VersionPress\Api\VersionPressApi;
use VersionPress\DI\VersionPressServices;
use VersionPress\Hooks\ChangeTracking;
use VersionPress\Hooks\Lifecycle;

/**
 * Registers all WordPress hooks of the plugin. Called once from `versionpress.php`.
 */
final class Plugin
{
    public static function boot(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            require_once VERSIONPRESS_PLUGIN_DIR . '/src/Cli/vp.php';
            require_once VERSIONPRESS_PLUGIN_DIR . '/src/Cli/vp-composer.php';
        }

        (new Lifecycle())->register();
        (new ChangeTracking())->register();
        (new AdminScreen())->register();
        (new Notices())->register();
        (new PluginCompatibility())->register();

        add_action('init', [self::class, 'loadTextDomain']);
        add_action('rest_api_init', [self::class, 'registerRestRoutes']);
    }

    public static function loadTextDomain(): void
    {
        load_plugin_textdomain('versionpress', false, dirname(plugin_basename(VERSIONPRESS_PLUGIN_FILE)) . '/languages');
    }

    public static function registerRestRoutes(): void
    {
        global $versionPressContainer;

        $api = new VersionPressApi(
            $versionPressContainer->resolve(VersionPressServices::GIT_REPOSITORY),
            $versionPressContainer->resolve(VersionPressServices::REVERTER),
            $versionPressContainer->resolve(VersionPressServices::SYNCHRONIZATION_PROCESS),
            $versionPressContainer->resolve(VersionPressServices::COMMIT_MESSAGE_PARSER)
        );
        $api->registerRoutes();
    }
}
