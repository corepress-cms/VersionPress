<?php

namespace Versionist\Tests\End2End\Utils;

/**
 * @method executeScript($script)
 */
class SeleniumWorkerBasedFakeTestCase extends \PHPUnit_Extensions_Selenium2TestCase
{

    /** @var SeleniumWorker */
    private $worker;

    public function __construct(SeleniumWorker $worker)
    {
        $this->worker = $worker;
    }

    public function __call($command, $arguments)
    {
        return call_user_func_array([$this->worker, $command], $arguments);
    }
}
