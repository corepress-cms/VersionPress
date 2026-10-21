<?php

namespace VersionPress\Admin;

use VersionPress\Utils\CompatibilityChecker;
use VersionPress\Utils\CompatibilityResult;
use VersionPress\VersionPress;

/**
 * Shows on the Plugins screens whether plugins were tested with VersionPress and asks for
 * confirmation before installing or activating an untested or incompatible one.
 */
class PluginCompatibility
{
    private const DOCS_URL = 'https://docs.versionpress.net/en/integrations/plugins';

    public function register(): void
    {
        add_filter('plugin_install_action_links', [$this, 'addInstallScreenLabel'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'addPluginRowLabel'], 10, 4);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScript']);
    }

    public function addInstallScreenLabel($links, $plugin)
    {
        $label = $this->renderLabel(CompatibilityChecker::testCompatibilityBySlug($plugin['slug']), $plugin['name']);
        if ($label !== null) {
            $links[] = $label;
        }
        return $links;
    }

    public function addPluginRowLabel($pluginMeta, $pluginFile, $pluginData, $status)
    {
        if ($status === 'dropins') {
            return $pluginMeta;
        }

        $label = $this->renderLabel(
            CompatibilityChecker::testCompatibilityByPluginFile($pluginFile),
            $pluginData['Name']
        );
        if ($label !== null) {
            $pluginMeta[] = $label;
        }
        return $pluginMeta;
    }

    public function enqueueScript($hookSuffix): void
    {
        if (!in_array($hookSuffix, ['plugins.php', 'plugin-install.php'], true)) {
            return;
        }

        wp_enqueue_script(
            'versionpress-plugin-compatibility',
            plugins_url('admin/public/js/plugin-compatibility.js', VERSIONPRESS_PLUGIN_FILE),
            ['wp-i18n'],
            VersionPress::getVersion(),
            ['in_footer' => true]
        );
        wp_set_script_translations('versionpress-plugin-compatibility', 'versionpress');
    }

    /**
     * @param string|null $compatibility One of CompatibilityResult constants
     * @param string $pluginName
     * @return string|null
     */
    private function renderLabel($compatibility, $pluginName)
    {
        switch ($compatibility) {
            case CompatibilityResult::COMPATIBLE:
                $cssClass = 'vp-compatible';
                $text = esc_html__('Compatible with VersionPress', 'versionpress');
                break;
            case CompatibilityResult::INCOMPATIBLE:
                $cssClass = 'vp-incompatible';
                $text = sprintf(
                    '<a href="%s" target="_blank" rel="noopener">%s</a>',
                    esc_url(self::DOCS_URL),
                    esc_html__('Incompatible with VersionPress', 'versionpress')
                );
                break;
            case CompatibilityResult::UNTESTED:
                $cssClass = 'vp-untested';
                $text = sprintf(
                    '<a href="%s" target="_blank" rel="noopener">%s</a>',
                    esc_url(self::DOCS_URL),
                    esc_html__('Untested with VersionPress', 'versionpress')
                );
                break;
            default:
                return null;
        }

        return sprintf(
            '<span class="vp-compatibility %s" data-plugin-name="%s"><strong>%s</strong></span>',
            esc_attr($cssClass),
            esc_attr($pluginName),
            $text
        );
    }
}
