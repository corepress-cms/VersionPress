<?php

/**
 * Uninstallation script for Versionist. Most things already happened in the
 * `Lifecycle::deactivate()`; here, we just move the .git repo.
 *
 * Testing tip: place exit() at the end of the script and then in the browser
 * just go back and try again.
 *
 * @see \Versionist\Hooks\Lifecycle::deactivate()
 */

use Versionist\DI\VersionistServices;
use Versionist\Initialization\DbDropIn;
use Versionist\Initialization\VersionistOptions;
use Versionist\Initialization\WpConfigSplitter;
use Versionist\Utils\FileSystem;
use Versionist\Utils\SecurityUtils;
use Versionist\Utils\UninstallationUtil;

defined('WP_UNINSTALL_PLUGIN') or die('Direct access not allowed');

require_once(dirname(__FILE__) . '/bootstrap.php');

global $versionPressContainer;

/** @var \Versionist\Database\Database $database */
$database = $versionPressContainer->resolve(VersionistServices::DATABASE);


$queries[] = "DROP TABLE IF EXISTS `{$database->vp_id}`";

$vpOptionsReflection = new ReflectionClass(VersionistOptions::class);
$usermetaToDelete = array_values($vpOptionsReflection->getConstants());
$queryRestriction = '"' . join('", "', $usermetaToDelete) . '"';

$queries[] = "DELETE FROM `{$database->usermeta}` WHERE meta_key IN ({$queryRestriction})";

foreach ($queries as $query) {
    $database->query($query);
}

delete_option('vp_rest_api_plugin_version');

FileSystem::remove(VP_VPDB_DIR);
FileSystem::remove(VERSIONIST_TEMP_DIR);
DbDropIn::uninstall(); // normally already removed on deactivation

$wpConfigPath = \Versionist\Utils\WordPressMissingFunctions::getWpConfigPath();
\Versionist\Utils\WpConfigEditor::removeVersionistConstants([
    $wpConfigPath,
    dirname($wpConfigPath) . '/' . WpConfigSplitter::COMMON_CONFIG_NAME,
]);

if (UninstallationUtil::uninstallationShouldRemoveGitRepo()) {
    $backupsDir = WP_CONTENT_DIR . '/vpbackups';
    if (!file_exists($backupsDir)) {
        FileSystem::mkdir($backupsDir);
        file_put_contents($backupsDir . '/.gitignore', 'git-backup-*');
        SecurityUtils::protectDirectory($backupsDir);
    }

    $backupPath = $backupsDir . '/git-backup-' . date("YmdHis");

    FileSystem::rename(VP_PROJECT_ROOT . '/.git', $backupPath, true);

    $productionGitignore = VP_PROJECT_ROOT . '.gitignore';
    $templateGitignore = __DIR__ . '/src/Initialization/.gitignore.tpl';

    if (FileSystem::filesHaveSameContents($productionGitignore, $templateGitignore)) {
        FileSystem::remove($productionGitignore);
    }
}
