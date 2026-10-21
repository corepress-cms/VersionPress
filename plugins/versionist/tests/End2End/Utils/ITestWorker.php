<?php

namespace Versionist\Tests\End2End\Utils;

use Versionist\Tests\Utils\TestConfig;

interface ITestWorker
{
    public function __construct(TestConfig $testConfig);
}
