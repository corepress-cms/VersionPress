<?php

namespace VersionPress\Utils;

/**
 * Recognizes requests that change something WordPress has no hook for, e.g. deleting a plugin.
 *
 * It only reads the request to learn what WordPress is about to do; WordPress verifies the nonces
 * of these requests itself before acting, so there is nothing to verify here.
 *
 * phpcs:disable WordPress.Security.NonceVerification
 */
class RequestDetector
{
    private $isWpCli;
    private $wpCliArguments;

    public function __construct()
    {
        $this->isWpCli = defined('WP_CLI') && WP_CLI;
        if ($this->isWpCli) {
            $runner = \WP_CLI::get_runner();
            $this->wpCliArguments = $runner->arguments;
        }
    }

    public function isThemeDeleteRequest()
    {
        if ($this->isWpCli) {
            return $this->isWpCliCommand(['theme', 'delete']);
        }

        if ($this->isAdminAjax()) {
            return $this->postVariableEquals('action', 'delete-theme');
        }

        return $this->isScreen('themes.php') && $this->queryVariableEquals('action', 'delete');
    }

    public function isPluginDeleteRequest()
    {
        if ($this->isWpCli) {
            return $this->isWpCliCommand(['plugin', 'delete']) || $this->isWpCliCommand([
                'plugin',
                'uninstall'
            ]);
        }

        if ($this->isAdminAjax()) {
            return $this->postVariableEquals('action', 'delete-plugin');
        } else {
            $isPluginAction = $this->isScreen('plugins.php');
            $isDeleteAction = $this->queryVariableEquals('action', 'delete-selected') || $this->postVariableEquals('action', 'delete-selected');
            $deleteIsConfirmed = isset($_REQUEST['verify-delete']);

            return $isPluginAction && $isDeleteAction && $deleteIsConfirmed;
        }
    }

    public function isCoreLanguageUninstallRequest()
    {
        return $this->isWpCliCommand(['core', 'language', 'uninstall']);
    }

    /**
     * @param $command
     * @return bool
     */
    private function isWpCliCommand($command)
    {
        foreach ($command as $n => $subcommand) {
            if (!isset($this->wpCliArguments[$n]) || $this->wpCliArguments[$n] !== $subcommand) {
                return false;
            }
        }
        return true;
    }

    private function queryVariableEquals($name, $value)
    {
        return isset($_GET[$name]) && sanitize_key(wp_unslash($_GET[$name])) === $value;
    }

    private function postVariableEquals($name, $value)
    {
        return isset($_POST[$name]) && sanitize_key(wp_unslash($_POST[$name])) === $value;
    }

    public function getPluginNames()
    {
        if (!$this->isWpCli) {
            if ($this->isAdminAjax()) {
                return array_map('sanitize_text_field', wp_unslash((array)($_POST['plugin'] ?? [])));
            }

            return array_map('sanitize_text_field', wp_unslash((array)($_REQUEST['checked'] ?? [])));
        }

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $arguments = $this->wpCliArguments;
        array_shift($arguments); // command (plugin)
        array_shift($arguments); // subcommand (delete)

        $plugins = [];

        foreach ($arguments as $name) {
            // code from WP_CLI\Fetchers\Plugin\get()
            foreach (get_plugins() as $file => $_) {
                if (
                    $file === "$name.php" ||
                    ($name && $file === $name) ||
                    (dirname($file) === $name && $name !== '.')
                ) {
                    $plugins[] = $file;
                }
            }
        }

        return $plugins;
    }

    public function getLanguageCode()
    {
        if (!$this->isWpCli) {
            return null; // Translation cannot be uninstalled using administration
        }

        return $this->wpCliArguments[3]; // core language uninstall <language>
    }

    public function getThemeStylesheets()
    {
        if (!$this->isWpCli) {
            if ($this->isAdminAjax()) {
                return array_map('sanitize_text_field', wp_unslash((array)($_POST['slug'] ?? [])));
            }

            return array_map('sanitize_text_field', wp_unslash((array)($_GET['stylesheet'] ?? [])));
        }

        return array_slice($this->wpCliArguments, 2); // theme delete <stylesheet>
    }

    private function isAdminAjax()
    {
        return wp_doing_ajax();
    }

    private function isScreen($file)
    {
        global $pagenow;
        return $pagenow === $file;
    }
}
