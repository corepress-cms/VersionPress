<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Versionist\Utils\StringUtils;

class StringUtilsTest extends TestCase
{

    public static function lfDataProvider() {
        return [
            ["\n", "\n"], // no conversion
            ["\r\n", "\n"], // simplest conversion
            ["a\r\nb", "a\nb"], // small real-world example
            ["a\r\nb\r\n", "a\nb\n"], // ... including trailing blank line
            ["a\r\nb\nc", "a\nb\nc"], // mixed line endings
        ];
    }

    #[Test]
    #[DataProvider('lfDataProvider')]
    public function ensureLfWorks($input, $output)
    {
        $this->assertEquals($output, StringUtils::ensureLf($input));
    }

    public static function serializedValueDataProvider()
    {
        return [
            [serialize(['a' => 1]), true],
            [serialize('string'), true],
            ['b:0;', true],
            ['not serialized', false],
            [['already', 'an', 'array'], false],
            [null, false],
            [42, false],
        ];
    }

    #[Test]
    #[DataProvider('serializedValueDataProvider')]
    public function isSerializedValueDetectsSerializedStrings($value, $expected)
    {
        $this->assertSame($expected, StringUtils::isSerializedValue($value));
    }

}
