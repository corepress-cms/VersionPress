<?php

namespace Versionist\DI;

final class VersionistServices
{
    public const STORAGE_FACTORY = 'storage-factory';
    public const MIRROR = 'mirror';
    public const DB_SCHEMA = 'db-schema';
    public const WPDB_MIRROR_BRIDGE = 'mirror-bridge';
    public const COMMITTER = 'committer';
    public const INITIALIZER = 'initializer';
    public const SYNCHRONIZATION_PROCESS = 'synchronization-process';
    public const SYNCHRONIZER_FACTORY = 'synchronizer-factory';
    public const REVERTER = 'reverter';
    public const GIT_REPOSITORY = 'git-repository';
    public const WPDB = 'wpdb';
    public const DATABASE = 'database';
    public const VPID_REPOSITORY = 'vpid-repository';
    public const URL_REPLACER = 'url-replacer';
    public const SHORTCODES_REPLACER = 'shortcode-replacer';
    public const SHORTCODES_INFO = 'shortcodes-info';
    public const SQL_QUERY_PARSER = 'sql-query-parser';
    public const ACTIONSINFO_PROVIDER_ACTIVE_PLUGINS = 'actionsinfo-provider-active-plugins';
    public const CHANGEINFO_FACTORY = 'changeinfo-factory';
    public const ACTIONSINFO_PROVIDER_ALL_PLUGINS = 'actionsinfo-provider-all-plugins';
    public const ACTIONS_DEFINITION_REPOSITORY = 'actions-definition-repository';
    public const COMMIT_MESSAGE_PARSER = 'commit-message-parser';
    public const TABLE_SCHEMA_STORAGE = 'table-schema-storage';

    private function __construct()
    {
    }
}
