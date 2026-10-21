<?php

namespace Versionist\Tests\End2End\Options;

use Versionist\Tests\End2End\Utils\ITestWorker;

interface IOptionsTestWorker extends ITestWorker
{

    public function prepare_changeOption();

    public function changeOption();

    public function prepare_changeTwoOptions();

    public function changeTwoOptions();
}
