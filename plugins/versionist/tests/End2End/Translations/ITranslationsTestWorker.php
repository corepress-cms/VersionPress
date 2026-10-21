<?php

namespace Versionist\Tests\End2End\Translations;

use Versionist\Tests\End2End\Utils\ITestWorker;

interface ITranslationsTestWorker extends ITestWorker
{

    public function prepare_switchLanguage();

    public function switchLanguage();
}
