<?php

namespace VersionPress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use VersionPress\Database\EntityInfo;

class EntityInfoTest extends TestCase
{

    private $entitySchema = [
        'some-entity' => [
            'vpid' => 'column',
            'frequently-written' => [
                'some_field: value other_field: a',
                [
                    'query' => 'other_field: value',
                    'interval' => '5 min'
                ]
            ],
            'ignored-entities' => [
                'some_field: value other_field: a',
                'capitalized_value_field: VALUE',
                'other_field: value'
            ],
            'ignored-columns' => [
                'ignored_column',
                ['other_ignored_column' => '@\SomeNamespace\SomeClass::someFunction']
            ]
        ]
    ];

    /** @var EntityInfo */
    private $entityInfo;

    protected function setUp(): void
    {
        $this->entityInfo = new EntityInfo($this->entitySchema);
    }

    #[Test]
    public function rulesAndIntervalsOfFrequentlyWrittenEntitiesEqualEntitySchema()
    {
        $expectedRules = [
            [
                'rule' =>
                    [
                        'some_field' => ['value'],
                        'other_field' => ['a'],
                    ],
                'interval' => 'hourly',
            ],
            [
                'rule' => ['other_field' => ['value']],
                'interval' => '5 min',
            ]
        ];

        $this->assertSame($expectedRules, $this->entityInfo->getRulesAndIntervalsForFrequentlyWrittenEntities());
    }

    #[Test]
    public function rulesOfIgnoredEntitiesEqualEntitySchema()
    {
        $expectedRules = [
            [
                'some_field' => ['value'],
                'other_field' => ['a'],
            ],
            [
                'capitalized_value_field' => ['VALUE'],
            ],
            [
                'other_field' => ['value']
            ],
        ];

        $this->assertSame($expectedRules, $this->entityInfo->getRulesForIgnoredEntities());
    }

    #[Test]
    public function frequentlyWrittenEntityIsCorrectlyIdentified()
    {
        $entity = [
            'some_field' => 'value',
            'other_field' => 'a',
        ];

        $this->assertTrue($this->entityInfo->isFrequentlyWrittenEntity($entity));
    }

    #[Test]
    public function partialMatchIsNotFalselyIdentifiedAsFrequentlyWritten()
    {
        // The rule is `'some_field: value other_field: a'` and the entity only partially matches it:
        $entity = [
            'some_field' => 'value'
        ];

        $this->assertFalse($this->entityInfo->isFrequentlyWrittenEntity($entity));
    }

    #[Test]
    public function ignoredEntityIsCorrectlyIdentified()
    {
        $entity = [
            'some_field' => 'value',
            'other_field' => 'a',
        ];

        $this->assertTrue($this->entityInfo->isIgnoredEntity($entity));
    }

    #[Test]
    public function ignoredEntityCapitalizedIsCorrectlyIdentified()
    {
        $entity = [
            'some_field' => 'b',
            'other_field' => 'a',
            'capitalized_value_field' => 'VaLuE', // comparison must be case insensitive
        ];

        $this->assertTrue($this->entityInfo->isIgnoredEntity($entity));
    }

    #[Test]
    public function partialMatchIsNotFalselyIdentifiedAsIgnored()
    {
        // The rule is `'some_field: value other_field: a'` and the entity must match it entirely.

        $entity = [
            'some_field' => 'value'
        ];

        $this->assertFalse($this->entityInfo->isIgnoredEntity($entity));

        $entity = [
            'some_field' => 'value',
            'other_field' => 'b',
        ];

        $this->assertFalse($this->entityInfo->isIgnoredEntity($entity));
    }

    #[Test]
    public function ignoredColumnsAreIdentifiedCorrectly()
    {
        $this->assertEquals([], array_diff(
            ['ignored_column', 'other_ignored_column'],
            array_keys($this->entityInfo->getIgnoredColumns())
        ));
    }
}
