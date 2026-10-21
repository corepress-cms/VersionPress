<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Utils\ProcessUtils;

class ProcessUtilsTest extends TestCase
{

    #[Test]
    public function windowsEscapingUsesDoubleQuotes()
    {
        $this->assertEquals("\"arg\"", ProcessUtils::escapeshellarg("arg", "windows"));
    }

    #[Test]
    public function linuxEscapingUsesSingleQuotes()
    {
        $this->assertEquals("'arg'", ProcessUtils::escapeshellarg("arg", "linux"));
    }

    #[Test]
    public function linuxEscapingPassesStandardTests()
    {

        // @codingStandardsIgnoreLine
        // see https://github.com/php/php-src/blob/3551083c2c188d3d5de5e58f3bd2624f2abfefc4/ext/standard/tests/general_functions/escapeshellarg_basic.phpt
        $this->assertEquals("'Mr O'\''Neil'", ProcessUtils::escapeshellarg("Mr O'Neil", "linux"));
        $this->assertEquals("'Mr O\'\''Neil'", ProcessUtils::escapeshellarg("Mr O\'Neil", "linux"));
        $this->assertEquals("'%FILENAME'", ProcessUtils::escapeshellarg("%FILENAME", "linux"));
        $this->assertEquals("''", ProcessUtils::escapeshellarg("", "linux"));
    }

    #[Test]
    public function windowsEscapingPassesStandardTests()
    {

        // @codingStandardsIgnoreLine
        // Inspired by https://github.com/php/php-src/blob/3551083c2c188d3d5de5e58f3bd2624f2abfefc4/ext/standard/tests/general_functions/escapeshellarg_basic-win32.phpt
        // but actually updated for the current behavior (for instance, % used to be replaced with a space,
        // now it's doubled up to %%)
        $this->assertEquals("\"Mr O'Neil\"", ProcessUtils::escapeshellarg("Mr O'Neil", "windows"));
        $this->assertEquals("\"Mr O\\\\'Neil\"", ProcessUtils::escapeshellarg("Mr O\'Neil", "windows"));
        $this->assertEquals("\"%%FILENAME\"", ProcessUtils::escapeshellarg("%FILENAME", "windows"));
        $this->assertEquals("\"\"", ProcessUtils::escapeshellarg("", "windows"));
    }
}
