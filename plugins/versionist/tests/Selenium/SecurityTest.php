<?php

namespace Versionist\Tests\Selenium;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Tests\Automation\WpAutomation;
use Versionist\Tests\End2End\Utils\HttpStatusCodeUtil;
use Versionist\Tests\Utils\TestConfig;
use Versionist\Utils\PathUtils;

class SecurityTest extends TestCase
{
    /** @var TestConfig */
    private static $testConfig;
    /** @var WpAutomation */
    private static $wpAutomation;

    #[Test]
    public function gitRepositoryDoesntAllowDirectAccess()
    {
        $url = self::$testConfig->testSite->url . "/.git/config";
        $statusCode = HttpStatusCodeUtil::getStatusCode($url);
        $this->assertTrue($statusCode === 403 || $statusCode === 404, "Wrong HTTP status code ($statusCode)");
    }

    #[Test]
    public function vpdbDoesntAllowDirectAccess()
    {
        $vpdbDir = self::$wpAutomation->getVpdbDir();
        $relativePathToVpdb = PathUtils::getRelativePath(self::$wpAutomation->getWebRoot(), $vpdbDir);
        $url = self::$testConfig->testSite->url . '/' . $relativePathToVpdb . "/web.config";
        $statusCode = HttpStatusCodeUtil::getStatusCode($url);
        $this->assertTrue($statusCode === 403 || $statusCode === 404, "Wrong HTTP status code ($statusCode)");
    }

    #[Test]
    public function vpconfigDoesntAllowDirectAccess()
    {
        $pluginsDir = self::$wpAutomation->getPluginsDir();
        $relativePathToPluginsDir = PathUtils::getRelativePath(self::$wpAutomation->getWebRoot(), $pluginsDir);

        $url = self::$testConfig->testSite->url . '/' . $relativePathToPluginsDir . "/versionpress/vpconfig.yml";
        $statusCode = HttpStatusCodeUtil::getStatusCode($url);
        $this->assertTrue($statusCode === 403 || $statusCode === 404, "Wrong HTTP status code ($statusCode)");
    }
}
