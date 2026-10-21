<?php

namespace VersionPress\Utils;

/**
 * Symfony\Process implementation that adds the `getConsoleOutput()` method and increases the time limit.
 *
 * @package VersionPress\Utils
 */
class Process extends \Symfony\Component\Process\Process
{
    private $consoleOutput;

    public function __construct(
        array $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 3600
    ) {
        parent::__construct($command, $cwd, $env, $input, $timeout);
    }

    public static function fromShellCommandline(
        string $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 3600
    ): static {
        return parent::fromShellCommandline($command, $cwd, $env, $input, $timeout);
    }

    /**
     * Returns output as it would appear in the console and doesn't care whether it comes
     * from STDOUT or STDERR (or both).
     *
     * This is useful in client code that just wants to show the user whatever the command printed. The programs
     * will sometimes use STDOUT, sometimes STDERR, sometimes mistakenly STDOUT instead of STDERR etc. but it often
     * doesn't matter, we just want to pass the output on to the user.
     *
     * @return string
     */
    public function getConsoleOutput()
    {
        return $this->consoleOutput;
    }


    public function start(?callable $callback = null, array $env = []): void
    {
        $this->consoleOutput = '';
        parent::start($callback, $env);
    }

    public function addOutput(string $line): void
    {
        parent::addOutput($line);
        $this->consoleOutput .= $line;
    }

    public function addErrorOutput(string $line): void
    {
        parent::addErrorOutput($line);
        $this->consoleOutput .= $line;
    }
}
