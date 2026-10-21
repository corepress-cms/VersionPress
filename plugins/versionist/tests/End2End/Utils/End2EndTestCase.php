<?php

namespace Versionist\Tests\End2End\Utils;

use PHPUnit\Framework\TestCase;
use Versionist\Actions\ActionsInfoProvider;
use Versionist\Database\DbSchemaInfo;
use Versionist\Git\GitRepository;
use Versionist\Tests\Automation\WpAutomation;
use Versionist\Tests\Utils\CommitAsserter;
use Versionist\Tests\Utils\TestConfig;
use Versionist\Utils\PathUtils;

class End2EndTestCase extends TestCase
{

    /** @var TestConfig */
    protected static $testConfig;

    /** @var WpAutomation */
    protected static $wpAutomation;

    private static $skipAllBecauseOfMissingWorker = false;

    private static $infoForNewCommitAsserter = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$testConfig = TestConfig::createDefaultConfig();

        // Make sure the test site is ready
        self::$wpAutomation = new WpAutomation(self::$testConfig->testSite, self::$testConfig->wpCliVersion);
        self::$wpAutomation->ensureTestSiteIsReady();

        // Prepare some objects for `newCommitAsserter()` once to save resources
        self::$infoForNewCommitAsserter = [
            'gitRepository' => new GitRepository(self::$testConfig->testSite->path),
            'relativePathToVpdb' => PathUtils::getRelativePath(self::$testConfig->testSite->path, $vpdbDir = self::$wpAutomation->getVpdbDir()),
            'relativePathToUploads' => PathUtils::getRelativePath(self::$testConfig->testSite->path, self::$wpAutomation->getUploadsDir()),
            'dbSchema' => new DbSchemaInfo([ self::$wpAutomation->getPluginsDir() . '/versionist/.versionpress/schema.yml'], self::$testConfig->testSite->dbTablePrefix, PHP_INT_MAX),
            'actionsInfoProvider' => new ActionsInfoProvider([ self::$wpAutomation->getPluginsDir() . '/versionist/.versionpress/actions.yml']),
        ];

        // Select a worker
        $class = get_called_class();
        $workerType = implode('', array_map('ucfirst', explode('-', self::$testConfig->end2endTestType)));
        $workerClass = $class . $workerType . 'Worker';

        if (!class_exists($workerClass)) {
            self::$skipAllBecauseOfMissingWorker = true;
            return;
        }

        $worker = new $workerClass(self::$testConfig);

        $propertyReflection = new \ReflectionProperty($class, 'worker');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue(null, $worker);
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$skipAllBecauseOfMissingWorker) {
            $this->markTestSkipped('Missing worker');
        }
    }

    protected function newCommitAsserter()
    {
        return new CommitAsserter(
            self::$infoForNewCommitAsserter['gitRepository'],
            self::$infoForNewCommitAsserter['dbSchema'],
            self::$infoForNewCommitAsserter['actionsInfoProvider'],
            [
                'vpdb' => self::$infoForNewCommitAsserter['relativePathToVpdb'],
                'uploads' => self::$infoForNewCommitAsserter['relativePathToUploads']
            ]
        );
    }
}
