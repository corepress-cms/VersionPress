<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Versionist\Utils\FileSystem;
use Versionist\Utils\Mutex;

class MutexTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vp-mutex-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        FileSystem::remove($this->dir);
    }

    #[Test]
    public function lockCreatesMissingLockDirectory()
    {
        $mutex = new Mutex($this->dir . '/vpcache', 'test');

        $mutex->lock();

        $this->assertDirectoryExists($this->dir . '/vpcache/test.lock');
        $mutex->release();
        $this->assertDirectoryDoesNotExist($this->dir . '/vpcache/test.lock');
    }

    #[Test]
    public function staleLockIsBrokenAfterTimeout()
    {
        mkdir($this->dir . '/test.lock', 0777, true); // left behind by a killed process
        sleep(1);

        $mutex = new Mutex($this->dir, 'test', 0);
        $mutex->lock();

        $this->assertDirectoryExists($this->dir . '/test.lock');
        $mutex->release();
    }
}
