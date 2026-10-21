<?php

namespace Versionist\Database;

class ParsedQueryData
{
    /**
     * Table name parsed from the source sql query. Usually prefixed e.g. `wp_posts`
     *
     * @var string
     */
    public $table;


    /**
     * Versionist entity name resolved from `schema.yml` and $table
     *
     * @var string
     */
    public $entityName;

    /**
     * List of record Ids which are/will be affected by parsed query
     *
     * @var array
     */
    public $ids;

    /**
     * Data statements which will be applied to database when UPDATE or INSERT is performed.
     *
     * Example structure:
     *
     * [
     *      [ column => post_modified, value => NOW() ],
     *      [ column => another_column, value => 123 ]
     * ]
     * @var array;
     */
    public $data;


    /**
     * SELECT query created from provided source query (INSERT, UPDATE, DELETE)
     *
     * @var string
     */
    public $sqlQuery;

    /**
     * Id column name resolved from `schema.yml` and $table
     * @var string[]
     */
    public $idColumnsNames;

    /**
     * @var string Internal enumeration of queries which are currently supoorted by SqlQueryParser
     */
    public $queryType;

    public const UPDATE_QUERY = 'UPDATE';
    public const INSERT_QUERY = 'INSERT';
    public const DELETE_QUERY = 'DELETE';
    public const INSERT_IGNORE_QUERY = 'INSERT_IGNORE';
    public const INSERT_UPDATE_QUERY = 'INSERT_UPDATE';


    public function __construct($queryType)
    {
        $this->queryType = $queryType;
    }
}
