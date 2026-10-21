<?php
namespace Versionist\Tests\Selenium;

use PHPUnit\Framework\Attributes\Test;
use Exception;
use Versionist\Initialization\InitializationConfig;
use Versionist\Tests\Utils\DBAsserter;
use Versionist\Tests\Utils\TestConfig;
use Versionist\Tests\Utils\TestRunnerOptions;
use Versionist\Tests\Utils\WpVersionComparer;

/**
 * Tests Versionist deactivation / reactivation / uninstallation flow.
 */
class ActivationDeactivationTest // extends SeleniumTestCase // disabled in Docker env
{

    /**
     * We're overriding the default setUpBeforeClass()
     */
    public static function setUpBeforeClass()
    {
        if (TestRunnerOptions::getInstance()->forceSetup == "before-class" || !self::$wpAutomation->isSiteSetUp()) {
            self::$wpAutomation->setUpSite();
        }
    }

    //----------------------------
    // Delete unactivated plugin
    //----------------------------
    #[Test]
    public function completeUninstallationOfUnactivatedPluginRemovesAllFiles()
    {
        self::installVersionist();
        $this->url(self::$wpAdminPath . '/plugins.php');
        $this->byCssSelector('.delete a[href*=\'versionpress\']')->click();
        $this->byCssSelector('.wrap form:nth-of-type(1) input#submit')->click();

        $this->waitForElement('.plugins-php #message.updated');

        $this->assertFileNotExists(self::$wpAutomation->getAbspath() . '/wp-includes/wpdb.php.original');
        $this->assertFileNotExists(self::$wpAutomation->getPluginsDir() . '/versionpress');
        $this->assertFileNotExists(self::$testConfig->testSite->path . '/.git');
    }

    //----------------------------
    // Activation
    //----------------------------
    /**
     * @see vp_gettext_filter_plugin_activated() Nag string is constructed here
     */
    #[Test]
    public function successfulActivationDisplaysInitializationHintInTheActivationMessage()
    {
        self::installVersionist();
        $this->activateVersionist();
        $this->assertContains("Versionist", $this->byCssSelector('#message.updated p')->text());
        $this->assertElementExists('#message.updated p a');
    }

    /**
     * @depends successfulActivationDisplaysInitializationHintInTheActivationMessage
     */
    #[Test]
    public function nagIsDisplayedOnSubsequentRequests()
    {
        $this->url(self::$wpAdminPath . '/index.php');
        $this->assertElementExists('.vp-activation-nag');
    }

    /**
     * @depends nagIsDisplayedOnSubsequentRequests
     */
    #[Test]
    public function visitingVersionistScreenShowsInitializationInformation()
    {
        $updateConfigArgs = [
            'VERSIONIST_GUI',
            'html',
            'require' => self::$wpAutomation->getPluginsDir() . '/versionist/src/Cli/vp-internal.php'
        ];
        self::$wpAutomation->runWpCliCommand('vp-internal', 'update-config', $updateConfigArgs);


        $this->url(self::$wpAdminPath . '/admin.php?page=versionpress/');
        $this->assertElementExists('#activate-versionpress-btn');
    }

    /**
     * @depends visitingVersionistScreenShowsInitializationInformation
     */
    #[Test]
    public function successfulActivationRedirectsToMainVersionistTableAndAltersWpdbClass()
    {
        $wpdbFile = self::$wpAutomation->getAbspath() . '/wp-includes/class-wpdb.php';
        $wpdbOriginalFile = $wpdbFile . '.original';
        $this->assertFileNotExists($wpdbOriginalFile);
        $hashBeforeInit = md5_file($wpdbFile);

        $this->byCssSelector('#activate-versionpress-btn')->click();
        $this->waitAfterRedirect(30000);
        $this->waitForElement('#versionpress-commits-table', InitializationConfig::REDIRECT_AFTER_MS + 3000);

        $hashAfterInit = md5_file($wpdbFile);
        $hashOfOriginal = md5_file($wpdbOriginalFile);
        $this->assertNotEquals($hashBeforeInit, $hashAfterInit);
        $this->assertEquals($hashBeforeInit, $hashOfOriginal);
    }

    /**
     * @depends successfulActivationRedirectsToMainVersionistTableAndAltersWpdbClass
     */
    #[Test]
    public function afterActivationTheFilesystemMatchDatabase()
    {
        DBAsserter::assertFilesEqualDatabase();
    }

    //----------------------------
    // Automatic reactivation
    //----------------------------
    #[Test]
    public function versionPressRestoresMethodsInWpdbAfterReplacingWithOriginal()
    {
        $isReplacedCall = 'echo Versionist\Initialization\WpdbReplacer::isReplaced();';
        $restoreCall = 'Versionist\Initialization\WpdbReplacer::restoreOriginal();';


        $isReplaced = (bool)self::$wpAutomation->runWpCliCommand('eval', null, $isReplacedCall);
        $this->assertTrue($isReplaced);

        $isReplaced = (bool)self::$wpAutomation->runWpCliCommand('eval', null, $restoreCall . $isReplacedCall);
        $this->assertFalse($isReplaced);

        $isReplaced = (bool)self::$wpAutomation->runWpCliCommand('eval', null, $isReplacedCall);
        $this->assertTrue($isReplaced);
    }

    //----------------------------
    // Deactivation
    //----------------------------
    #[Test]
    public function deactivateShowsConfirmationScreen()
    {
        $this->url(self::$wpAdminPath . '/plugins.php');
        $this->byCssSelector('.deactivate a[href*=\'versionpress\']')->click();


        if (WpVersionComparer::compare(TestConfig::createDefaultConfig()->testSite->wpVersion, '4.2-beta1') < 0) {
            $deactivationUrl = 'versionpress/admin/deactivate.php';
        } else {
            $deactivationUrl = 'versionpress%2Fadmin%2Fdeactivate.php';
        }

        $this->assertContains($deactivationUrl, $this->url());
    }

    /**
     * @depends deactivateShowsConfirmationScreen
     */
    #[Test]
    public function cancelDeactivationReturnsToPluginPage()
    {

        $this->byCssSelector('#cancel_deactivation')->click();

        $this->assertContains('wp-admin/plugins.php', $this->url());
        $this->assertFileExists(self::$wpAutomation->getVpdbDir() . '/.active');

    }

    /**
     * @depends cancelDeactivationReturnsToPluginPage
     */
    #[Test]
    public function confirmingDeactivationFullyDeactivatesVersionist()
    {

        $this->byCssSelector('.deactivate a[href*=\'versionpress\']')->click();

        if (WpVersionComparer::compare(TestConfig::createDefaultConfig()->testSite->wpVersion, '4.2-beta1') < 0) {
            $deactivationUrl = 'versionpress/admin/deactivate.php';
        } else {
            $deactivationUrl = 'versionpress%2Fadmin%2Fdeactivate.php';
        }

        $this->assertContains($deactivationUrl, $this->url());

        $this->byCssSelector('#confirm_deactivation')->click();
        $this->assertContains('wp-admin/plugins.php', $this->url());

        $this->assertFileNotExists(self::$wpAutomation->getAbspath() . '/wp-includes/wpdb.php.original');
        $this->assertFileExists(self::$testConfig->testSite->path . '/.git');

    }

    /**
     * @depends confirmingDeactivationFullyDeactivatesVersionist
     */
    #[Test]
    public function deactivateBeforeFullActivationSkipsConfirmation()
    {
        $this->byCssSelector('.activate a[href*=\'versionpress\']')->click();
        $this->byCssSelector('.deactivate a[href*=\'versionpress\']')->click();

        $this->assertContains('wp-admin/plugins.php', $this->url());
        $this->assertElementExists('.activate a[href*=\'versionpress\']');
    }


    /**
     * @depends deactivateBeforeFullActivationSkipsConfirmation
     */
    #[Test]
    public function completeUninstallationRemovesGitRepo()
    {

        $this->byCssSelector('.delete a[href*=\'versionpress\']')->click();
        $this->byCssSelector('.wrap form:nth-of-type(1) input#submit')->click();

        $this->waitForElement('.plugins-php #message.updated');

        $this->assertFileNotExists(self::$wpAutomation->getAbspath() . '/wp-includes/wpdb.php.original');
        $this->assertFileNotExists(self::$wpAutomation->getPluginsDir() . '/versionpress');
        $this->assertFileNotExists(self::$wpAutomation->getVpdbDir());
        $this->assertFileNotExists(self::$testConfig->testSite->path . '/.git');

    }


    //---------------------
    // Helper functions
    //---------------------

    private function activateVersionist()
    {
        $this->url(self::$wpAdminPath . '/plugins.php');
        $this->byCssSelector('.activate a[href*=\'versionpress\']')->click();
        $this->waitAfterRedirect();
    }

    private static function installVersionist()
    {
        try {
            self::$wpAutomation->uninstallVersionist();
        } catch (Exception $e) {
        }

        self::$wpAutomation->copyVersionistFiles();
    }
}
