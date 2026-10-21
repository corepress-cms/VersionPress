<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Tests\Utils\TestConfig;

class TestConfigTest extends TestCase
{

    /**
     * This is just a quick smoke test.
     */
    #[Test]
    public function yamlParsingWorks()
    {
        $config = new TestConfig(__DIR__ . "/../test-config.yml");
        $this->assertEquals("latest-stable", $config->wpCliVersion);
    }
}
