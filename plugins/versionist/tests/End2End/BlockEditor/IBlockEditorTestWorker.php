<?php

namespace Versionist\Tests\End2End\BlockEditor;

use Versionist\Tests\End2End\Utils\ITestWorker;

interface IBlockEditorTestWorker extends ITestWorker
{
    public function createTemplate();

    public function prepare_updateGlobalStyles();

    public function updateGlobalStyles();

    public function updateRuntimeOptions();

    public function createOembedCache();
}
