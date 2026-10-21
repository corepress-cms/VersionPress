<?php

namespace Versionist\Tests\End2End\Media;

use Versionist\Tests\End2End\Utils\ITestWorker;

interface IMediaTestWorker extends ITestWorker
{

    public function setUploadedFilePath($filePath);

    public function prepare_uploadFile();

    public function uploadFile();

    public function prepare_editFileName();

    public function editFileName();

    public function prepare_deleteFile();

    public function deleteFile();

    public function prepare_editFile();

    public function editFile();
}
