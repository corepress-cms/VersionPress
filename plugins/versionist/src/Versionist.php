<?php

namespace Versionist;

class Versionist
{
    /**
     * Base URL of the user documentation. Still the documentation of VersionPress, of which Versionist is a fork;
     * point it to Versionist's own documentation once it is published.
     */
    public const DOCS_URL = 'https://docs.versionpress.net/en';

    /**
     * Returns Versionist version as specified in plugin metadata
     *
     * @return string
     */
    public static function getVersion()
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $pluginData = get_plugin_data(VERSIONIST_PLUGIN_FILE, false, false);
        return $pluginData['Version'];
    }

    /**
     * Returns name of current environment (default for original site, clone name for clone).
     *
     * @return string
     */
    public static function getEnvironment()
    {
        return defined('VP_ENVIRONMENT') ? VP_ENVIRONMENT : 'default';
    }

    /**
     * Returns true if Versionist is active. Note that active != activated and being
     * active means that Versionist is tracking changes.
     *
     * @phpstan-impure It changes during (de)activation within the same request.
     * @return bool
     */
    public static function isActive()
    {
        return defined('VERSIONIST_ACTIVATION_FILE') && file_exists(VERSIONIST_ACTIVATION_FILE);
    }
}
