<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Versionist\Database\DbSchemaInfo;
use Versionist\Database\ParsedQueryData;
use Versionist\Database\SqlQueryParser;
use Versionist\Tests\Utils\HookMock;
use Versionist\Tests\Utils\WpdbStub;

class SqlQueryParserTest extends TestCase
{

    /**
     * @var DbSchemaInfo
     */
    private static $dbSchemaInfo;

    /** @var WpdbStub|Stub|MockObject */
    private $database;

    /** @var  SqlQueryParser */
    private $sqlParser;

    public static function setUpBeforeClass(): void
    {
        if (!defined('ARRAY_N')) {
            define('ARRAY_N', 'ARRAY_N');
        }

        HookMock::setUp();
        self::$dbSchemaInfo = new DbSchemaInfo(
            [__DIR__ . '/../../.versionpress/schema.yml'],
            'wp_',
            PHP_INT_MAX
        );
    }

    public static function tearDownAfterClass(): void
    {
        HookMock::tearDown();
    }

    public function setup(): void
    {
        $this->database = $this->createStub(WpdbStub::class);
        $this->sqlParser = new SqlQueryParser(self::$dbSchemaInfo, $this->database);
    }

    /**
     * Replaces the default stub with a mock so that tests can verify the generated SELECT query via `with()`.
     */
    private function useDatabaseMock(): void
    {
        $this->database = $this->createMock(WpdbStub::class);
        $this->sqlParser = new SqlQueryParser(self::$dbSchemaInfo, $this->database);
    }

    /**
     * @param $query
     * @param $expectedSelect
     * @param $expectedData
     */
    #[Test]
    #[DataProvider('updateQueryParseTestDataProvider')]
    public function dataToSetFromUpdate($query, $expectedSelect, $expectedData, $expectedIds)
    {

        $parsedQueryData = $this->sqlParser->parseQuery($query);

        $this->assertEquals($expectedData, $parsedQueryData->data);

    }

    /**
     * @param $query
     * @param $expectedSelectQuery
     * @param $expectedData
     * @param $expectedIds
     */
    #[Test]
    #[DataProvider('updateQueryParseTestDataProvider')]
    public function selectQueryFromUpdate($query, $expectedSelectQuery, $expectedData, $expectedIds)
    {
        $this->useDatabaseMock();
        $this->database->expects($this->any())->method("get_col")
            ->with($expectedSelectQuery)->willReturn($expectedIds);
        $parsedQueryData = $this->sqlParser->parseQuery($query);

        $this->assertEquals($expectedSelectQuery, $parsedQueryData->sqlQuery);
    }

    /**
     * @param $query
     * @param $expectedData
     */
    #[Test]
    #[DataProvider('insertQueryParseTestDataProvider')]
    public function dataFromInsert($query, $expectedData, $expectedQueryType)
    {
        $parsedQueryData = $this->sqlParser->parseQuery($query);
        $parsedData = $parsedQueryData == null ? null : $parsedQueryData->data;

        $this->assertEquals($expectedData, $parsedData);
    }

    /**
     * @param $query
     * @param $expectedData
     * @param $expectedQueryType
     */
    #[Test]
    #[DataProvider('insertQueryParseTestDataProvider')]
    public function detectNonStandardInsert($query, $expectedData, $expectedQueryType)
    {
        $parsedQueryData = $this->sqlParser->parseQuery($query);
        $parsedQeryType = $parsedQueryData == null ? null : $parsedQueryData->queryType;

        $this->assertEquals($expectedQueryType, $parsedQeryType);
    }

    /**
     * @param $query
     * @param $expectedSelectQuery
     */
    #[Test]
    #[DataProvider('deleteQueryParseTestDataProvider')]
    public function selectQueryFromDelete($query, $expectedSelectQuery)
    {
        $this->useDatabaseMock();
        $this->database->expects($this->any())->method("get_results")
            ->with($expectedSelectQuery)->willReturn([]);
        $parsedQueryData = $this->sqlParser->parseQuery($query);

        $this->assertEquals($expectedSelectQuery, $parsedQueryData->sqlQuery);
    }

    public static function deleteQueryParseTestDataProvider()
    {
        return [
            [
                "DELETE o1 FROM `wp_options` AS o1 JOIN `wp_options` AS o2 ON o1.option_name=o2.option_name " .
                "WHERE o2.option_id > o1.option_id",
                "SELECT option_name FROM `wp_options` AS o1 JOIN `wp_options` AS o2 ON o1.option_name=o2.option_name " .
                "WHERE o2.option_id > o1.option_id",
            ],
            [
                "DELETE o1 FROM `wp_options` AS o1 JOIN `wp_options` AS o2 USING (`option_name`) " .
                "WHERE o2.option_id > o1.option_id",
                "SELECT option_name FROM `wp_options` AS o1 JOIN `wp_options` AS o2 ON o1.option_name=o2.option_name " .
                "WHERE o2.option_id > o1.option_id",
            ],
            [
                "DELETE FROM `wp_usermeta` WHERE meta_key IN ('key1', 'key2')",
                "SELECT umeta_id FROM `wp_usermeta` WHERE meta_key IN ('key1', 'key2')",
            ],
            [
                "DELETE FROM `wp_usermeta` WHERE meta_key = 'key1'",
                "SELECT umeta_id FROM `wp_usermeta` WHERE meta_key = 'key1'",
            ],
            [
                "DELETE FROM `wp_term_relationships` WHERE object_id = 2 AND term_taxonomy_id IN ('2')",
                "SELECT object_id, term_taxonomy_id FROM `wp_term_relationships` WHERE object_id = 2 AND term_taxonomy_id IN ('2')",
            ]
        ];
    }

    public static function insertQueryParseTestDataProvider()
    {
        return [
            [
                "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('name', 'value', 1) " .
                "ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), " .
                "`option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)",
                [["option_name" => "name", "option_value" => "value", "autoload" => "1"]],
                ParsedQueryData::INSERT_UPDATE_QUERY
            ],
            [
                "INSERT IGNORE INTO `wp_terms` (term_id, name, slug, term_group) " .
                "VALUES (10, 'term name', 'term-name', 5) , (20, 'term another', 'term-another', 15)",
                null,
                null
            ],
            [
                "INSERT INTO `wp_terms` (term_id, name, slug, term_group) VALUES (10, 'term name', 'term-name', 5)",
                [["term_id" => "10", "name" => "term name", "slug" => "term-name", "term_group" => "5"]],
                ParsedQueryData::INSERT_QUERY
            ],
            [
                "INSERT INTO `wp_terms` (term_id, name, slug, term_group) " .
                "VALUES (10, 'term name', 'term-name', 5) , (20, 'term another', 'term-another', 15)",
                [
                    ["term_id" => "10", "name" => "term name", "slug" => "term-name", "term_group" => "5"],
                    ["term_id" => "20", "name" => "term another", "slug" => "term-another", "term_group" => "15"]
                ],
                ParsedQueryData::INSERT_QUERY
            ],
            [
                "INSERT INTO `wp_terms` (term_id, date) VALUES (10, NOW())",
                [["term_id" => "10", "date" => "NOW()"]],
                ParsedQueryData::INSERT_QUERY
            ],
            [
                "INSERT INTO `wp_terms` (term_id, name) VALUES (10, 'term')",
                [["term_id" => 10, "name" => "term"]],
                ParsedQueryData::INSERT_QUERY
            ],
            [
                "INSERT INTO `wp_terms` (term_id, name) VALUES (10, 'term line 1\\nterm line 2')",
                [["term_id" => 10, "name" => "term line 1\nterm line 2"]],
                ParsedQueryData::INSERT_QUERY
            ]

        ];
    }

    public static function updateQueryParseTestDataProvider()
    {
        $testIds = [1, 3, 15];
        return [
            [
                "UPDATE  `wp_posts` SET post_modified = NOW() WHERE post_author = 'B'",
                "SELECT ID FROM `wp_posts` WHERE post_author = 'B'",
                ["post_modified" => "NOW()"],
                $testIds
            ],
            [
                "UPDATE `wp_posts` SET post_date = post_modified WHERE post_date = '0000-00-00 00:00:00'",
                "SELECT ID FROM `wp_posts` WHERE post_date = '0000-00-00 00:00:00'",
                ["post_date" => "post_modified"],
                $testIds
            ],
            [
                "UPDATE `wp_options` SET option_value=REPLACE(option_value, 'wp-links/links-images/', " .
                "'wp-images/links/') WHERE option_name LIKE '%_' AND option_value LIKE '%s'",
                "SELECT option_name FROM `wp_options` WHERE option_name LIKE '%_' AND option_value LIKE '%s'",
                ["option_value" => "REPLACE(option_value, 'wp-links/links-images/', 'wp-images/links/')"],
                $testIds
            ],
            [
                "UPDATE `wp_options` SET option_value=REPLACE(option_value, 'wp-links/links-images/', " .
                "'wp-images/links/'), option_abc = 'def' WHERE option_name LIKE '%A' AND option_value LIKE '%s'",
                "SELECT option_name FROM `wp_options` WHERE option_name LIKE '%A' AND option_value LIKE '%s'",
                ["option_value" => "REPLACE(option_value, 'wp-links/links-images/', 'wp-images/links/')", 'option_abc' => "'def'"],
                $testIds
            ],
            [
                "UPDATE `wp_posts` SET post_parent = '10', post_type='page' " .
                "WHERE post_type = 'attachment' AND ID IN (" . join(',', $testIds) . ")",
                "SELECT ID FROM `wp_posts` WHERE post_type = 'attachment' AND ID IN (" . join(',', $testIds) . ")",
                ["post_parent" => "'10'", "post_type" => "'page'"],
                $testIds
            ],
            [
                "UPDATE `wp_posts` SET post_date_gmt = DATE_ADD(post_date, INTERVAL '01:20' HOUR_MINUTE)",
                "SELECT ID FROM `wp_posts`",
                ["post_date_gmt" => "DATE_ADD(post_date, INTERVAL '01:20' HOUR_MINUTE)"],
                $testIds
            ],
            [
                "UPDATE  `wp_posts` SET post_author = 'A' WHERE post_author = 'B'",
                "SELECT ID FROM `wp_posts` WHERE post_author = 'B'",
                ["post_author" => "'A'"],
                $testIds
            ],
            [
                "UPDATE  `wp_posts` SET post_author=4 WHERE post_author = 5",
                "SELECT ID FROM `wp_posts` WHERE post_author = 5",
                ["post_author" => 4],
                $testIds
            ],
            [
                "UPDATE `wp_options` SET option_value='line 1\\nline 2'" .
                "WHERE option_name LIKE '%_' AND option_value LIKE '%s'",
                "SELECT option_name FROM `wp_options` WHERE option_name LIKE '%_' AND option_value LIKE '%s'",
                ["option_value" => "'line 1\nline 2'"],
                $testIds
            ],

        ];
    }
}
