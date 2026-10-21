<?php

namespace Versionist\Tests\End2End\Widgets;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Depends;
use Exception;
use Versionist\Tests\End2End\Utils\End2EndTestCase;
use Versionist\Tests\Utils\DBAsserter;
use Versionist\Tests\Utils\WpVersionComparer;

class WidgetsTest extends End2EndTestCase
{

    /** @var IWidgetsTestWorker */
    private static $worker;

    /** Widgets need a classic theme with sidebars; current default themes are block themes. */
    private const CLASSIC_THEME = 'twentytwentyone';

    private static $originalTheme;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$originalTheme = self::$wpAutomation->getCurrentTheme();
        self::$wpAutomation->runWpCliCommand('theme', 'install', [self::CLASSIC_THEME]);
        self::$wpAutomation->switchTheme(self::CLASSIC_THEME);
        self::clearSidebars();
        try {
            self::$wpAutomation->deleteOption('widget_calendar');
        } catch (Exception $e) {
            // It's ok. This option does not exist in the clear WP.
        }
    }

    #[Test]
    #[TestDox('Creating first widget of given type creates new option')]
    public function creatingFirstWidgetOfGivenTypeCreatesOption()
    {
        self::$worker->prepare_createWidget();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createWidget();

        $commitAsserter->assertNumCommits(1);

        if (self::$testConfig->end2endTestType === 'selenium' &&
            WpVersionComparer::compare(self::$testConfig->testSite->wpVersion, '4.4-beta1') >= 0
        ) {
            $commitAsserter->assertCommitAction('option/update');
            $commitAsserter->assertCommitPath('M', '%vpdb%/options/%VPID%.ini');
        } else {
            $commitAsserter->assertCommitAction('option/create');
            $commitAsserter->assertCommitPath('A', '%vpdb%/options/%VPID%.ini');
        }

        $commitAsserter->assertCountOfAffectedFiles(2);
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('creatingFirstWidgetOfGivenTypeCreatesOption')]
    #[TestDox('Creating second widget of given type updates the option')]
    public function creatingSecondWidgetOfGivenTypeUpdatesOption()
    {
        self::$worker->prepare_createWidget();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createWidget();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('option/update');
        $commitAsserter->assertCountOfAffectedFiles(2);
        $commitAsserter->assertCommitPath('M', '%vpdb%/options/%VPID%.ini');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox("Editing widget creates 'option/update' action.")]
    public function editingWidgetCreatesOptionEditAction()
    {
        self::$worker->prepare_editWidget();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->editWidget();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('option/update');
        $commitAsserter->assertCountOfAffectedFiles(1);
        $commitAsserter->assertCommitPath('M', '%vpdb%/options/%VPID%.ini');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox("Deleting widget creates 'option/update' action")]
    public function deletingWidgetCreatesOptionEditAction()
    {
        self::$worker->prepare_deleteWidget();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deleteWidget();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('option/update');
        $commitAsserter->assertCountOfAffectedFiles(2);
        $commitAsserter->assertCommitPath('M', '%vpdb%/options/%VPID%.ini');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    public static function tearDownAfterClass(): void
    {
        self::$wpAutomation->switchTheme(self::$originalTheme);
        parent::tearDownAfterClass();
    }

    private static function clearSidebars()
    {
        $sidebars = self::$wpAutomation->getSidebars();
        if (count($sidebars) == 0) {
            self::fail('Tests can be run only with theme with sidebars');
        }
        foreach ($sidebars as $sidebar) {
            $widgets = self::$wpAutomation->getWidgets($sidebar);
            self::$wpAutomation->deleteWidgets($widgets);
        }
    }
}
