<?php

namespace Versionist\Tests\End2End\Translations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Versionist\Tests\End2End\Utils\End2EndTestCase;
use Versionist\Tests\Utils\DBAsserter;

class TranslationsTest extends End2EndTestCase
{

    /** @var ITranslationsTestWorker */
    private static $worker;

    #[Test]
    #[TestDox("Switching language creates 'translation/activate' action")]
    public function switchingLanguageCreatesLanguageActivateAction()
    {
        self::$worker->prepare_switchLanguage();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->switchLanguage();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('translation/activate');
        $commitAsserter->assertCommitPath(['A', 'M'], '%vpdb%/options/*');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }
}
