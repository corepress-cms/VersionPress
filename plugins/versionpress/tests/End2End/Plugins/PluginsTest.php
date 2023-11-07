<?php

namespace VersionPress\Tests\End2End\Plugins;

use VersionPress\Tests\End2End\Utils\End2EndTestCase;
use VersionPress\Tests\End2End\Utils\WpCliWorker;
use VersionPress\Tests\Utils\DBAsserter;
use VersionPress\Utils\Process;
use VersionPress\Utils\ProcessUtils;

class PluginsTest extends End2EndTestCase
{

    /** @var IPluginsTestWorker */
    private static $worker;

    /**
     * @see IPluginsTestWorker::setPluginInfo()
     * @var array
     */
    private static $pluginInfo;

    /**
     * Plugin info for bulk operations
     * @var array
     */
    private static $secondPluginInfo;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        if (self::$testConfig->testSite->installationType !== 'standard') {
            throw new \PHPUnit_Framework_SkippedTestSuiteError();
        }

        $testDataPath = __DIR__ . '/../test-data';
        self::$pluginInfo = [
            'zipfile' => realpath($testDataPath . '/hello-dolly.1.6.zip'),
            'url-fragment' => 'hello-dolly',
            'name' => 'Hello Dolly',
            'affected-path' => 'hello-dolly/*',
        ];

        self::$secondPluginInfo = [
            'zipfile' => realpath($testDataPath . '/hello-dolly.1.6-2.zip'),
            'url-fragment' => 'hello-dolly-2',
            'name' => 'Hello Dolly 2',
            'affected-path' => 'hello-dolly-2/*',
        ];

        self::$worker->setPluginInfo(self::$pluginInfo);
        self::$worker->setSecondPluginInfo(self::$secondPluginInfo);

        // possibly delete single-file Hello dolly
        try {
            self::$wpAutomation->runWpCliCommand('plugin', 'uninstall', ['hello']);
        } catch (\Exception $e) {
        }

        // possibly delete our testing plugins
        try {
            self::$wpAutomation->runWpCliCommand('plugin', 'uninstall', ['hello-dolly']);
            self::$wpAutomation->runWpCliCommand('plugin', 'uninstall', ['hello-dolly-2']);
        } catch (\Exception $e) {
        }

        $process = Process::fromShellCommandline(
            "git add -A && git commit -m " . ProcessUtils::escapeshellarg("Plugin setup"),
            self::$testConfig->testSite->path
        );
        $process->run();
    }

    /**
     * @test
     * @testdox Uploading plugin creates 'plugin/install' action
     */
    public function uploadingPluginCreatesPluginInstallAction()
    {
        self::$worker->prepare_installPlugin();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->installPlugin();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("plugin/install");
        $commitAsserter->assertCommitTag("VP-Plugin-Name", self::$pluginInfo['name']);
        $commitAsserter->assertCommitPath("A", "wp-content/plugins/" . self::$pluginInfo['affected-path']);
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Activating plugin creates 'plugin/activate' action
     * @depends uploadingPluginCreatesPluginInstallAction
     */
    public function activatingPluginCreatesPluginActivateAction()
    {
        self::$worker->prepare_activatePlugin();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->activatePlugin();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("plugin/activate");
        $commitAsserter->assertCommitTag("VP-Plugin-Name", self::$pluginInfo['name']);
        $commitAsserter->assertCommitPath("M", "%vpdb%/options/ac/active_plugins.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Deactivating plugin creates 'plugin/deactivate' action
     * @depends activatingPluginCreatesPluginActivateAction
     */
    public function deactivatingPluginCreatesPluginDeactivateAction()
    {
        self::$worker->prepare_deactivatePlugin();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deactivatePlugin();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("plugin/deactivate");
        $commitAsserter->assertCommitTag("VP-Plugin-Name", self::$pluginInfo['name']);
        $commitAsserter->assertCommitPath("M", "%vpdb%/options/ac/active_plugins.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Deleting plugin creates 'plugin/delete' action
     * @depends deactivatingPluginCreatesPluginDeactivateAction
     */
    public function deletingPluginCreatesPluginDeleteAction()
    {
        self::$worker->prepare_deletePlugin();
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deletePlugin();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("plugin/delete");
        $commitAsserter->assertCommitTag("VP-Plugin-Name", self::$pluginInfo['name']);
        $commitAsserter->assertCommitPath("D", "wp-content/plugins/" . self::$pluginInfo['affected-path']);
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Installing two plugins creates a bulk action
     */
    public function installingTwoPluginsCreatesBulkAction()
    {
        self::$worker->prepare_installTwoPlugins();
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->installTwoPlugins();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertBulkAction('plugin/install', 2);
        $commitAsserter->assertCommitPath("A", "wp-content/plugins/" . self::$pluginInfo['affected-path']);
        $commitAsserter->assertCommitPath("A", "wp-content/plugins/" . self::$secondPluginInfo['affected-path']);
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Activation of two plugins creates a bulk action
     */
    public function activatingTwoPluginsCreatesBulkAction()
    {
        self::$worker->prepare_activateTwoPlugins();
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->activateTwoPlugins();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertBulkAction('plugin/activate', 2);
        $commitAsserter->assertCommitPath("M", "%vpdb%/options/ac/active_plugins.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Deactivation of two plugins creates a bulk action
     */
    public function deactivatingTwoPluginsCreatesBulkAction()
    {
        self::$worker->prepare_deactivateTwoPlugins();
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deactivateTwoPlugins();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertBulkAction('plugin/deactivate', 2);
        $commitAsserter->assertCommitPath("M", "%vpdb%/options/ac/active_plugins.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Uninstalling two plugins creates a bulk action
     */
    public function uninstallingTwoPluginsCreatesBulkAction()
    {
        self::$worker->prepare_uninstallTwoPlugins();
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->uninstallTwoPlugins();

        if (version_compare(self::$testConfig->testSite->wpVersion, '4.6', '<') || self::$worker instanceof WpCliWorker) {
            $commitAsserter->assertNumCommits(1);
            $commitAsserter->assertBulkAction('plugin/delete', 2);
            $commitAsserter->assertCommitPath("D", "wp-content/plugins/" . self::$pluginInfo['affected-path']);
            $commitAsserter->assertCommitPath("D", "wp-content/plugins/" . self::$secondPluginInfo['affected-path']);
        } else {
            $commitAsserter->assertNumCommits(2);
            $commitAsserter->assertCommitAction('plugin/delete', 0);
            $commitAsserter->assertCommitAction('plugin/delete', 1);
            $commitAsserter->assertCommitPath("D", "wp-content/plugins/" . self::$secondPluginInfo['affected-path'], 0);
            $commitAsserter->assertCommitPath("D", "wp-content/plugins/" . self::$pluginInfo['affected-path'], 1);
        }


        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }
}
