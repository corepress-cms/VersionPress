<?php


namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Tests\Utils\OptionsConventionConverter;

class OptionsConventionConverterTest extends TestCase
{

    #[Test]
    public function convertsFromPropertyConventionToEnvVarConvention()
    {
        $this->assertEquals("VP_OPTION_NAME", OptionsConventionConverter::getEnvVarOptionName("optionName"));
        $this->assertEquals("VP_OPTION", OptionsConventionConverter::getEnvVarOptionName("option"));
    }

    #[Test]
    public function convertsFromPropertyConventionToCliConvention()
    {
        $this->assertEquals("option-name", OptionsConventionConverter::getCliOptionName("optionName"));
        $this->assertEquals("option", OptionsConventionConverter::getCliOptionName("option"));
    }
}
