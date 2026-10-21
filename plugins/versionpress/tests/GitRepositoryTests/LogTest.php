<?php

namespace VersionPress\Tests\GitRepositoryTests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use VersionPress\Git\CommitMessage;
use VersionPress\Git\GitRepository;
use VersionPress\Utils\FileSystem;

class LogTest extends TestCase
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
    }

    #[Test]
    public function logReturnsEmptyArrayInEmptyRepository()
    {
        $log = self::$repository->log();
        $this->assertEquals([], $log);
    }

    #[Test]
    public function logReturnsOneEntryAfterOneCommit()
    {
        touch(self::$repositoryPath . '/somefile');
        self::$repository->stageAll();
        self::$repository->commit(new CommitMessage("Some commit"), "Author name", "author@example.com");

        $log = self::$repository->log();
        $this->assertEquals(1, count($log));
    }

    #[Test]
    public function commitContainsListOfChangedFiles()
    {
        touch(self::$repositoryPath . '/somefile');
        touch(self::$repositoryPath . '/otherfile');
        self::$repository->stageAll();
        self::$repository->commit(new CommitMessage("Some commit"), "Author name", "author@example.com");

        $log = self::$repository->log();
        $lastCommit = $log[0];

        $expectedChangedFiles = [
            ['status' => 'A', 'path' => 'otherfile'], // the files have to be ordered alphabetically
            ['status' => 'A', 'path' => 'somefile'],
        ];

        $this->assertEquals($expectedChangedFiles, $lastCommit->getChangedFiles());
    }

    #[Test]
    public function allCommitsContainListOfChangedFiles()
    {
        touch(self::$repositoryPath . '/somefile');
        self::$repository->stageAll();
        self::$repository->commit(new CommitMessage("Some commit"), "Author name", "author@example.com");

        touch(self::$repositoryPath . '/otherfile');
        self::$repository->stageAll();
        self::$repository->commit(new CommitMessage("Other commit"), "Author name", "author@example.com");


        $log = self::$repository->log();
        $lastCommit = $log[0];

        $expectedChangedFilesInLastCommit = [
            ['status' => 'A', 'path' => 'otherfile'],
        ];

        $this->assertEquals($expectedChangedFilesInLastCommit, $lastCommit->getChangedFiles());


        $previousCommit = $log[1];
        $expectedChangedFilesInPreviousCommit = [
            ['status' => 'A', 'path' => 'somefile'],
        ];

        $this->assertEquals($expectedChangedFilesInPreviousCommit, $previousCommit->getChangedFiles());
    }
}
