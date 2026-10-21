<?php

namespace Versionist\Utils;

/**
 * Inspired by http://stackoverflow.com/a/6967839/1243495
 */
class Mutex
{
    /** @var string Name of the mutex */
    private $name;
    /** @var int Timeout for release (in seconds) */
    private $timeout;
    /** @var bool */
    private $isLockedByThisThread;

    /** @var string directory for creating mutex */
    private $lockDir;

    public function __construct($dir, $name, $timeout = 60)
    {
        $this->name = $name;
        $this->timeout = $timeout;
        $this->lockDir = $dir;
    }

    public function __destruct()
    {
        $this->release();
    }

    /**
     * Locks the mutex. The lock is automatically released after $timeout.
     */
    public function lock()
    {
        // Without its parent directory (e.g. in a fresh clone of the site), the lock could never be created.
        if (!is_dir($this->lockDir)) {
            FileSystem::mkdir($this->lockDir);
        }

        $lockname = $this->getLockName();

        while (!@mkdir($lockname)) {
            clearstatcache();
            $lockCreated = @filectime($lockname);
            if ($lockCreated && (time() - $lockCreated) > $this->timeout) {
                // Stale lock of a process that didn't release it, e.g. because it was killed.
                @rmdir($lockname);
            }
            usleep(rand(50000, 200000)); // wait random time
        }

        $this->isLockedByThisThread = true;
    }

    /**
     * Releases the lock. Returns false on failure.
     * @return bool
     */
    public function release()
    {
        if ($this->isLockedByThisThread) {
            $this->isLockedByThisThread = false;
            return rmdir($this->getLockName());
        }

        return false;
    }

    private function getLockName()
    {
        return $this->lockDir . '/' . $this->name . '.lock';
    }
}
