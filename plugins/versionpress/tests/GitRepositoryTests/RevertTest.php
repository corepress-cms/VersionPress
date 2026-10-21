<?php

namespace VersionPress\Tests\GitRepositoryTests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use VersionPress\Actions\ActionsInfoProvider;
use VersionPress\Cli\VPCommandUtils;
use VersionPress\Database\DbSchemaInfo;
use VersionPress\Git\GitRepository;
use VersionPress\Tests\Utils\CommitAsserter;
use VersionPress\Utils\FileSystem;

class RevertTest extends TestCase
{

    private static $repositoryPath;
    private static $tempPath;
    /** @var GitRepository */
    private static $repository;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$repositoryPath = sys_get_temp_dir() . '/vp-repository';
        self::$tempPath = sys_get_temp_dir() . '/vp-temp';
        self::$repository = new GitRepository(self::$repositoryPath, self::$tempPath);
        mkdir(self::$repositoryPath);
        mkdir(self::$tempPath);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        FileSystem::remove(self::$repositoryPath);
        FileSystem::remove(self::$tempPath);
    }

    protected function setUp(): void
    {
        parent::setUp();
        FileSystem::removeContent(self::$repositoryPath);
        FileSystem::removeContent(self::$tempPath);
        self::$repository->init();
        VPCommandUtils::exec('git config user.name test', self::$repositoryPath);
        VPCommandUtils::exec('git config user.email test@example.com', self::$repositoryPath);

        $this->commitFile('initial-file', 'Initial commit');
    }

    #[Test]
    public function revertTakesBackChangesFromLastCommit()
    {
        $this->commitFile('some-file', 'Some commit');
        $hash = self::$repository->getLastCommitHash();

        $commitAsserter = $this->createCommitAsserter();

        $revertResult = self::$repository->revert($hash);
        $this->commit('Revert');

        $this->assertTrue($revertResult);
        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCleanWorkingDirectory();
        $commitAsserter->assertCountOfAffectedFiles(1);
        $commitAsserter->assertCommitPath('D', 'some-file');
    }

    #[Test]
    public function revertTakesBackChangesFromSpecificCommit()
    {
        $this->commitFile('some-file', 'Some commit');
        $hash = self::$repository->getLastCommitHash();
        $this->commitFile('other-file', 'Other commit');


        $commitAsserter = $this->createCommitAsserter();

        $revertResult = self::$repository->revert($hash);
        $this->commit('Revert');

        $this->assertTrue($revertResult);
        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCleanWorkingDirectory();
        $commitAsserter->assertCountOfAffectedFiles(1);
        $commitAsserter->assertCommitPath('D', 'some-file');
    }

    #[Test]
    public function revertCannotTakeBackOverwrittenChanges()
    {
        $this->commitFile('some-file', 'Some commit', 'Some content');
        $hash = self::$repository->getLastCommitHash();
        $this->commitFile('some-file', 'Other commit', 'Other content');


        $commitAsserter = $this->createCommitAsserter();

        $revertResult = self::$repository->revert($hash);
        $this->commit('Revert');

        $this->assertFalse($revertResult);
        $commitAsserter->assertNumCommits(0);
        $commitAsserter->assertCleanWorkingDirectory();
    }

    #[Test]
    public function revertAllTakesBackAllChanges()
    {
        $hash = self::$repository->getLastCommitHash();
        $this->commitFile('some-file', 'Some commit');
        $this->commitFile('other-file', 'Other commit');


        $commitAsserter = $this->createCommitAsserter();

        self::$repository->revertAll($hash);
        $this->commit('Revert all');

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCleanWorkingDirectory();
        $commitAsserter->assertCountOfAffectedFiles(2);
        $commitAsserter->assertCommitPath('D', 'some-file');
        $commitAsserter->assertCommitPath('D', 'other-file');
    }

    #[Test]
    public function revertAllTakesBackChangesFromMultipleBranches()
    {
        $hash = self::$repository->getLastCommitHash();
        VPCommandUtils::exec('git branch test', self::$repositoryPath);
        $this->commitFile('some-file', 'Some commit');
        VPCommandUtils::exec('git checkout test', self::$repositoryPath);
        $this->commitFile('other-file', 'Other commit');
        VPCommandUtils::exec('git checkout master', self::$repositoryPath);
        VPCommandUtils::exec('git merge test', self::$repositoryPath);


        $commitAsserter = $this->createCommitAsserter();

        self::$repository->revertAll($hash);
        $this->commit('Revert all');

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCleanWorkingDirectory();
        $commitAsserter->assertCountOfAffectedFiles(2);
        $commitAsserter->assertCommitPath('D', 'some-file');
        $commitAsserter->assertCommitPath('D', 'other-file');
    }

    #[Test]
    public function revertAllCanRevertToCommitInParallelBranch()
    {
        VPCommandUtils::exec('git branch test', self::$repositoryPath);
        $this->commitFile('some-file', 'Some commit');
        VPCommandUtils::exec('git checkout test', self::$repositoryPath);
        $this->commitFile('other-file', 'Other commit');
        $hash = self::$repository->getLastCommitHash();
        VPCommandUtils::exec('git checkout master', self::$repositoryPath);
        VPCommandUtils::exec('git merge test', self::$repositoryPath);


        $commitAsserter = $this->createCommitAsserter();

        self::$repository->revertAll($hash);
        $this->commit('Revert all');

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCleanWorkingDirectory();
        $commitAsserter->assertCountOfAffectedFiles(1);
        $commitAsserter->assertCommitPath('D', 'some-file');
    }

    private function commit($message)
    {
        self::$repository->commit($message, 'Author name', 'author@example.com');
    }

    private function commitFile($file, $commitMessage, $fileContent = '')
    {
        file_put_contents(self::$repositoryPath . '/' . $file, $fileContent);
        self::$repository->stageAll();
        $this->commit($commitMessage);
    }

    private function createCommitAsserter()
    {
        return new CommitAsserter(self::$repository, $this->createStub(DbSchemaInfo::class), $this->createStub(ActionsInfoProvider::class));
    }
 }
