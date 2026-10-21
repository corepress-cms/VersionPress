<?php

namespace VersionPress\Tests\End2End\BlockEditor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use VersionPress\Tests\End2End\Utils\End2EndTestCase;
use VersionPress\Tests\Utils\DBAsserter;

/**
 * Entities introduced with the block editor are stored as posts of internal post types.
 */
class BlockEditorTest extends End2EndTestCase
{
    /** @var IBlockEditorTestWorker */
    private static $worker;

    #[Test]
    #[TestDox("Creating template creates 'post/create' action described as template")]
    public function creatingTemplateCreatesPostCreateAction()
    {
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createTemplate();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('post/create');
        $commitAsserter->assertCommitMessageContains("Created template 'VersionPress test template'");
        $commitAsserter->assertCommitPath('A', '%vpdb%/posts/%VPID%.ini');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox("Updating global styles creates 'post/update' action")]
    public function updatingGlobalStylesCreatesPostUpdateAction()
    {
        self::$worker->prepare_updateGlobalStyles();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->updateGlobalStyles();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('post/update');
        $commitAsserter->assertCommitMessageContains('global styles');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox('Runtime options like caches are not versioned')]
    public function runtimeOptionsAreNotVersioned()
    {
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->updateRuntimeOptions();

        $commitAsserter->assertNumCommits(0);
        $commitAsserter->assertCleanWorkingDirectory();
    }

    #[Test]
    #[TestDox('oEmbed cache is not versioned')]
    public function oembedCacheIsNotVersioned()
    {
        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createOembedCache();

        $commitAsserter->assertNumCommits(0);
        $commitAsserter->assertCleanWorkingDirectory();
    }
}
