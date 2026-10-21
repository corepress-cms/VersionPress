<?php

namespace Versionist\DI;

use Versionist\Actions\ActionsInfoProvider;
use Versionist\Actions\ActionsDefinitionRepository;
use Versionist\Actions\PluginDefinitionDiscovery;
use Versionist\ChangeInfos\ChangeInfoFactory;
use Versionist\ChangeInfos\CommitMessageParser;
use Versionist\Database\Database;
use Versionist\Database\DbSchemaInfo;
use Versionist\Database\ShortcodesInfo;
use Versionist\Database\ShortcodesReplacer;
use Versionist\Database\SqlQueryParser;
use Versionist\Database\TableSchemaStorage;
use Versionist\Database\VpidRepository;
use Versionist\Database\WpdbMirrorBridge;
use Versionist\Git\Committer;
use Versionist\Git\GitRepository;
use Versionist\Git\Reverter;
use Versionist\Initialization\Initializer;
use Versionist\Storages\Mirror;
use Versionist\Storages\StorageFactory;
use Versionist\Synchronizers\SynchronizationProcess;
use Versionist\Synchronizers\SynchronizerFactory;
use Versionist\Utils\AbsoluteUrlReplacer;

class DIContainer
{
    /** @var DIContainer */
    private static $instance;
    private $providers;
    private $services;

    public function register($name, $serviceProvider)
    {
        $this->providers[$name] = $serviceProvider;
    }

    /**
     * @param $name string Service name
     * @return mixed Service instance
     */
    public function resolve($name)
    {
        if (!isset($this->services[$name])) {
            $provider = $this->providers[$name];
            $this->services[$name] = $provider();
        }
        return $this->services[$name];
    }

    /**
     * @return DIContainer
     */
    public static function getConfiguredInstance()
    {
        if (self::$instance != null) {
            return self::$instance;
        }

        self::$instance = $dic = new DIContainer();

        $dic->register(VersionistServices::WPDB, function () {
            global $wpdb;
            return $wpdb;
        });

        $dic->register(VersionistServices::DATABASE, function () use ($dic) {
            return new Database($dic->resolve(VersionistServices::WPDB));
        });

        $dic->register(VersionistServices::STORAGE_FACTORY, function () use ($dic) {
            global $wp_taxonomies;
            return new StorageFactory(
                VP_VPDB_DIR,
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::DATABASE),
                array_keys((array)$wp_taxonomies),
                $dic->resolve(VersionistServices::CHANGEINFO_FACTORY),
                $dic->resolve(VersionistServices::TABLE_SCHEMA_STORAGE)
            );
        });

        $dic->register(VersionistServices::MIRROR, function () use ($dic) {
            return new Mirror(
                $dic->resolve(VersionistServices::STORAGE_FACTORY),
                $dic->resolve(VersionistServices::URL_REPLACER)
            );
        });

        $dic->register(VersionistServices::DB_SCHEMA, function () {
            global $table_prefix, $wp_db_version;
            return new DbSchemaInfo(
                PluginDefinitionDiscovery::getPathsForActivePlugins('schema.yml'),
                $table_prefix,
                $wp_db_version
            );
        });

        $dic->register(VersionistServices::ACTIONS_DEFINITION_REPOSITORY, function () use ($dic) {
            return new ActionsDefinitionRepository(VERSIONIST_TEMP_DIR . '/actions', $dic->resolve(VersionistServices::GIT_REPOSITORY));
        });

        $dic->register(VersionistServices::ACTIONSINFO_PROVIDER_ALL_PLUGINS, function () use ($dic) {
            return new ActionsInfoProvider($dic->resolve(VersionistServices::ACTIONS_DEFINITION_REPOSITORY)->getAllDefinitionFiles());
        });

        $dic->register(VersionistServices::ACTIONSINFO_PROVIDER_ACTIVE_PLUGINS, function () {
            return new ActionsInfoProvider(PluginDefinitionDiscovery::getPathsForActivePlugins('actions.yml'));
        });

        $dic->register(VersionistServices::CHANGEINFO_FACTORY, function () use ($dic) {
            return new ChangeInfoFactory(
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::ACTIONSINFO_PROVIDER_ACTIVE_PLUGINS)
            );
        });

        $dic->register(VersionistServices::COMMIT_MESSAGE_PARSER, function () use ($dic) {
            return new CommitMessageParser(
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::ACTIONSINFO_PROVIDER_ALL_PLUGINS)
            );
        });

        $dic->register(VersionistServices::WPDB_MIRROR_BRIDGE, function () use ($dic) {
            return new WpdbMirrorBridge(
                $dic->resolve(VersionistServices::DATABASE),
                $dic->resolve(VersionistServices::MIRROR),
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::VPID_REPOSITORY),
                $dic->resolve(VersionistServices::SHORTCODES_REPLACER)
            );
        });

        $dic->register(VersionistServices::COMMITTER, function () use ($dic) {
            return new Committer(
                $dic->resolve(VersionistServices::MIRROR),
                $dic->resolve(VersionistServices::GIT_REPOSITORY),
                $dic->resolve(VersionistServices::STORAGE_FACTORY)
            );
        });

        $dic->register(VersionistServices::INITIALIZER, function () use ($dic) {
            return new Initializer(
                $dic->resolve(VersionistServices::DATABASE),
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::STORAGE_FACTORY),
                $dic->resolve(VersionistServices::SYNCHRONIZER_FACTORY),
                $dic->resolve(VersionistServices::GIT_REPOSITORY),
                $dic->resolve(VersionistServices::URL_REPLACER),
                $dic->resolve(VersionistServices::VPID_REPOSITORY),
                $dic->resolve(VersionistServices::SHORTCODES_REPLACER),
                $dic->resolve(VersionistServices::CHANGEINFO_FACTORY),
                $dic->resolve(VersionistServices::ACTIONS_DEFINITION_REPOSITORY)
            );
        });

        $dic->register(VersionistServices::SYNCHRONIZER_FACTORY, function () use ($dic) {
            return new SynchronizerFactory(
                $dic->resolve(VersionistServices::STORAGE_FACTORY),
                $dic->resolve(VersionistServices::DATABASE),
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::VPID_REPOSITORY),
                $dic->resolve(VersionistServices::URL_REPLACER),
                $dic->resolve(VersionistServices::SHORTCODES_REPLACER),
                $dic->resolve(VersionistServices::TABLE_SCHEMA_STORAGE)
            );
        });

        $dic->register(VersionistServices::SYNCHRONIZATION_PROCESS, function () use ($dic) {
            return new SynchronizationProcess($dic->resolve(VersionistServices::SYNCHRONIZER_FACTORY));
        });

        $dic->register(VersionistServices::REVERTER, function () use ($dic) {
            return new Reverter(
                $dic->resolve(VersionistServices::SYNCHRONIZATION_PROCESS),
                $dic->resolve(VersionistServices::DATABASE),
                $dic->resolve(VersionistServices::COMMITTER),
                $dic->resolve(VersionistServices::GIT_REPOSITORY),
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::STORAGE_FACTORY),
                $dic->resolve(VersionistServices::COMMIT_MESSAGE_PARSER)
            );
        });

        $dic->register(VersionistServices::GIT_REPOSITORY, function () {
            return new GitRepository(VP_PROJECT_ROOT, VERSIONIST_TEMP_DIR, VERSIONIST_COMMIT_MESSAGE_PREFIX, VP_GIT_BINARY);
        });

        $dic->register(VersionistServices::VPID_REPOSITORY, function () use ($dic) {
            return new VpidRepository(
                $dic->resolve(VersionistServices::DATABASE),
                $dic->resolve(VersionistServices::DB_SCHEMA)
            );
        });

        $dic->register(VersionistServices::URL_REPLACER, function () {
            return new AbsoluteUrlReplacer(get_home_url());
        });

        $dic->register(VersionistServices::SHORTCODES_REPLACER, function () use ($dic) {
            return new ShortcodesReplacer(
                $dic->resolve(VersionistServices::SHORTCODES_INFO),
                $dic->resolve(VersionistServices::VPID_REPOSITORY)
            );
        });

        $dic->register(VersionistServices::SHORTCODES_INFO, function () {
            return new ShortcodesInfo(PluginDefinitionDiscovery::getPathsForActivePlugins('shortcodes.yml'));
        });

        $dic->register(VersionistServices::SQL_QUERY_PARSER, function () use ($dic) {
            return new SqlQueryParser(
                $dic->resolve(VersionistServices::DB_SCHEMA),
                $dic->resolve(VersionistServices::DATABASE)
            );
        });

        $dic->register(VersionistServices::TABLE_SCHEMA_STORAGE, function () use ($dic) {
            return new TableSchemaStorage($dic->resolve(VersionistServices::DATABASE), VP_VPDB_DIR . '/.schema');
        });

        return self::$instance;
    }
}
