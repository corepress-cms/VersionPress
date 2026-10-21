<?php

namespace Versionist\Cli;

use Versionist\Actions\ActionsDefinitionRepository;
use Versionist\Database\Database;
use Versionist\Database\DbSchemaInfo;
use Versionist\Database\VpidRepository;
use Versionist\DI\DIContainer;
use Versionist\DI\VersionistServices;
use Versionist\Git\MergeDriverInstaller;
use Versionist\Initialization\Initializer;
use Versionist\Initialization\WpConfigSplitter;
use Versionist\Synchronizers\SynchronizationProcess;
use Versionist\Utils\WordPressMissingFunctions;
use Versionist\Utils\WpConfigEditor;
use WP_CLI;
use WP_CLI_Command;
use wpdb;

/**
 * Internal Versionist commands.
 *
 * ## USAGE
 *
 * These internal commands are not registered with WP-CLI automatically like the "public"
 * `wp versionist` commands in vp.php. You have to manually require the file, e.g.:
 *
 *     wp --require=wp-content/plugins/versionist/src/Cli/vp-internal.php vp-internal ...
 *
 * These internal commands are mostly used by public `wp vp` commands.
 *
 */
class VPInternalCommand extends WP_CLI_Command
{
    /**
     * Finishes clone operation
     *
     * @subcommand finish-restore-site
     *
     * @when before_wp_load
     *
     */
    public function finishRestore($args, $assoc_args)
    {
        define('SHORTINIT', true);

        $wpConfigPath = \WP_CLI\Utils\locate_wp_config();
        require_once $wpConfigPath;

        require_once ABSPATH . WPINC . '/formatting.php';
        require_once ABSPATH . WPINC . '/link-template.php';
        require_once ABSPATH . WPINC . '/shortcodes.php';
        require_once ABSPATH . WPINC . '/taxonomy.php';

        wp_plugin_directory_constants();

        // The bootstrap of the site being restored; this file may be run from another site's copy of the plugin (clone).
        require_once WP_PLUGIN_DIR . '/' . basename(dirname(__DIR__, 2)) . '/bootstrap.php';

        $versionPressContainer = DIContainer::getConfiguredInstance();

        /** @var ActionsDefinitionRepository $actionsDefinitionRepository */
        $actionsDefinitionRepository = $versionPressContainer->resolve(VersionistServices::ACTIONS_DEFINITION_REPOSITORY);
        $actionsDefinitionRepository->restoreAllActionsFilesFromHistory();

        // Truncate tables
        /** @var Database $database */
        $database = $versionPressContainer->resolve(VersionistServices::DATABASE);
        /** @var DbSchemaInfo $dbSchema */
        $dbSchema = $versionPressContainer->resolve(VersionistServices::DB_SCHEMA);

        $tables = array_map(function ($entityName) use ($dbSchema) {
            return $dbSchema->getPrefixedTableName($entityName);
        }, array_merge($dbSchema->getAllEntityNames(), array_map(function ($referenceDetails) {
            return $referenceDetails['junction-table'];
        }, $dbSchema->getAllMnReferences())));


        $tables = array_filter($tables, function ($table) use ($database) {
            return $table !== $database->options;
        });

        foreach ($tables as $table) {
            $truncateCmd = "TRUNCATE TABLE `$table`";
            @$database->query($truncateCmd); // Intentional @ - not existing table is ok for us but TRUNCATE ends with error
        }

        // Create Versionist tables
        /** @var \Versionist\Initialization\Initializer $initializer */
        $initializer = $versionPressContainer->resolve(VersionistServices::INITIALIZER);
        $initializer->createVersionistTables();

        WP_CLI::success("Versionist tables created");


        // Install Custom merge driver

        MergeDriverInstaller::installMergeDriver(VP_PROJECT_ROOT, VERSIONIST_PLUGIN_DIR, VP_VPDB_DIR);
        WP_CLI::success("Git merge driver added");


        // Run synchronization

        /** @var SynchronizationProcess $syncProcess */
        $syncProcess = $versionPressContainer->resolve(VersionistServices::SYNCHRONIZATION_PROCESS);
        $syncProcess->synchronizeAll();
        WP_CLI::success("Database synchronized");

        VPCommandUtils::runWpCliCommand('vp-internal', 'flush-regenerable-values', ['require' => __FILE__]);
    }

    /**
     * @subcommand flush-regenerable-values
     */
    public function flushRegenerableValues()
    {
        vp_flush_regenerable_options();
        $this->flushRewriteRules();
    }

    /**
     * Finishes the update with new version
     *
     * @subcommand finish-update
     */
    public function finishUpdate($args, $assoc_args)
    {
        global $versionPressContainer;
        activate_plugins(plugin_basename(VERSIONIST_PLUGIN_FILE));
        WP_CLI::success('Re-activated Versionist');

        /** @var ActionsDefinitionRepository $actionsDefinitionRepository */
        $actionsDefinitionRepository = $versionPressContainer->resolve(VersionistServices::ACTIONS_DEFINITION_REPOSITORY);
        $actionsDefinitionRepository->restoreAllActionsFilesFromHistory();

        /** @var Initializer $initializer */
        $initializer = $versionPressContainer->resolve(VersionistServices::INITIALIZER);
        $initializer->initializeVersionist(true);

        WP_CLI::success('Versionist is tracking your site');
    }

    /**
     * Turns on or off the maintenance mode.
     *
     * ## OPTIONS
     *
     * <mode>
     * : Desired state of maintenance mode. Possible values are 'on' or 'off'.
     *
     */
    public function maintenance($args)
    {
        $mode = $args[0];
        if ($mode === 'on') {
            vp_enable_maintenance();
        } else {
            vp_disable_maintenance();
        }
    }

    /**
     * Finishes `vp push`
     *
     * @subcommand finish-push
     *
     */
    public function finishPush($args, $assoc_args)
    {
        global $versionPressContainer;

        // Update working copy
        $resetCommand = "git reset --hard";
        $process = VPCommandUtils::exec($resetCommand);
        if (!$process->isSuccessful()) {
            WP_CLI::error("Working directory couldn't be reset");
        }

        // Install current Composer dependencies
        if (file_exists(VP_PROJECT_ROOT . '/composer.json')) {
            $process = VPCommandUtils::exec('composer install', VP_PROJECT_ROOT);
            if ($process->isSuccessful()) {
                WP_CLI::success('Installed Composer dependencies');
            } else {
                WP_CLI::error('Composer dependencies could not be restored.');
            }
        }

        /** @var ActionsDefinitionRepository $actionsDefinitionRepository */
        $actionsDefinitionRepository = $versionPressContainer->resolve(VersionistServices::ACTIONS_DEFINITION_REPOSITORY);
        $actionsDefinitionRepository->restoreAllActionsFilesFromHistory();

        // Run synchronization
        /** @var SynchronizationProcess $syncProcess */
        $syncProcess = $versionPressContainer->resolve(VersionistServices::SYNCHRONIZATION_PROCESS);
        $syncProcess->synchronizeAll();

        vp_flush_regenerable_options();
        vp_disable_maintenance();
        $this->flushRewriteRules();
        vp_enable_maintenance();
    }

    private function flushRewriteRules()
    {
        set_transient('vp_flush_rewrite_rules', 1);
        /**
         * @see VPCommand::flushRewriteRules
         */
        wp_remote_get(get_home_url());
    }

    /**
     * Gets `id` of an entity from `vp_id` table
     *
     * ## OPTIONS
     *
     * --vpid=<vpid>
     *
     * @subcommand get-entity-id
     *
     *
     */
    public function getEntityId($args = [], $assoc_args = [])
    {
        global $versionPressContainer;
        /** @var Database $database */
        $database = $versionPressContainer->resolve(VersionistServices::DATABASE);
        $sql = "SELECT ID FROM " . $database->vp_id . " WHERE vp_id=UNHEX('" . $assoc_args["vpid"] . "')";
        $newId = $database->get_col($sql);
        if (isset($newId[0])) {
            echo $newId[0];
        }
    }

    /**
     * Gets `vp_id` Guid of an entity from id and entity name
     *
     * ## OPTIONS
     *
     * --id=<id>
     *
     * --name=<name>
     *
     * @subcommand get-entity-vpid
     *
     *
     */
    public function getEntityVpid($args = [], $assoc_args = [])
    {
        global $versionPressContainer;
        /** @var wpdb $wpdb */
        $wpdb = $versionPressContainer->resolve(VersionistServices::WPDB);
        /** @var VpidRepository $vpIdRepository */
        $vpIdRepository = $versionPressContainer->resolve(VersionistServices::VPID_REPOSITORY);
        echo $vpIdRepository->getVpidForEntity($assoc_args["name"], $assoc_args["id"]);
    }

    /**
     * Sets or updates constant or variable in wp-config.php
     *
     * ## OPTIONS
     *
     * <constant>
     * : Name of constant or variable that will be changed.
     *
     * <value>
     * : Desired value. Supported types are: string, int, float and bool.
     *
     * [--plain]
     * : The value will be used as is - without type detection, quoting etc.
     *
     * [--variable]
     * : Will set a variable instead of constant. Useful for $table_prefix.
     *
     * [--common]
     * : The constant / variable will be set in wp-config.common.php.
     *
     * @subcommand update-config
     *
     *
     * @when before_wp_load
     */
    public function updateConfig($args = [], $assoc_args = [])
    {
        require_once __DIR__ . '/VPCommandUtils.php';
        require_once __DIR__ . '/../Initialization/WpConfigSplitter.php';
        require_once __DIR__ . '/../Utils/WpConfigEditor.php';
        require_once __DIR__ . '/../Utils/WordPressMissingFunctions.php';

        $wpConfigPath = WordPressMissingFunctions::getWpConfigPath();
        $updateCommonConfig = isset($assoc_args['common']);

        if ($updateCommonConfig) {
            $wpConfigPath = dirname($wpConfigPath) . '/' . WpConfigSplitter::COMMON_CONFIG_NAME;
        }

        if ($wpConfigPath === false) {
            WP_CLI::error('Config file does not exist. Please run `wp core config` first.');
        }

        $constantOrVariableName = $args[0];
        $isVariable = isset($assoc_args['variable']);
        $usePlainValue = isset($assoc_args['plain']);
        $value = $usePlainValue ? $args[1] : VPCommandUtils::fixTypeOfValue($args[1]);

        $wpConfigEditor = new WpConfigEditor($wpConfigPath, $updateCommonConfig);

        try {
            if ($isVariable) {
                $wpConfigEditor->updateConfigVariable($constantOrVariableName, $value, $usePlainValue);
            } else {
                $wpConfigEditor->updateConfigConstant($constantOrVariableName, $value, $usePlainValue);
            }
        } catch (\Exception $e) {
            WP_CLI::error('Cannot find place for defining the ' . ($isVariable ? 'variable' : 'constant') .
                '. Config was probably edited manually.');
        }
    }

    /**
     * Used before pull
     *
     * @subcommand commit-frequently-written-entities
     */
    public function commitFrequentlyWrittenEntities($args = [], $assoc_args = [])
    {
        vp_commit_all_frequently_written_entities();
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('vp-internal', VPInternalCommand::class);
}
