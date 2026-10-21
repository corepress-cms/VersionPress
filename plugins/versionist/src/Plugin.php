<?php

namespace Versionist;

use Versionist\Admin\AdminScreen;
use Versionist\Admin\Notices;
use Versionist\Admin\PluginCompatibility;
use Versionist\Api\VersionistApi;
use Versionist\DI\VersionistServices;
use Versionist\Hooks\ChangeTracking;
use Versionist\Hooks\Lifecycle;

/**
 * Registers all WordPress hooks of the plugin. Called once from `versionist.php`.
 */
final class Plugin
{
    public static function boot(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            require_once VERSIONIST_PLUGIN_DIR . '/src/Cli/vp.php';
            require_once VERSIONIST_PLUGIN_DIR . '/src/Cli/vp-composer.php';
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
        load_plugin_textdomain('versionist', false, dirname(plugin_basename(VERSIONIST_PLUGIN_FILE)) . '/languages');
    }

    public static function registerRestRoutes(): void
    {
        global $versionPressContainer;

        $api = new VersionistApi(
            $versionPressContainer->resolve(VersionistServices::GIT_REPOSITORY),
            $versionPressContainer->resolve(VersionistServices::REVERTER),
            $versionPressContainer->resolve(VersionistServices::SYNCHRONIZATION_PROCESS),
            $versionPressContainer->resolve(VersionistServices::COMMIT_MESSAGE_PARSER)
        );
        $api->registerRoutes();
    }
}
