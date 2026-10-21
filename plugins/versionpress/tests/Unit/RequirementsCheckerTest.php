<?php

namespace VersionPress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../../src/Utils/RequirementsChecker.php');

use VersionPress\Utils\RequirementsChecker;

class RequirementsCheckerTest extends TestCase
{

    #[Test]
    public function gitCheckFailsForGit180()
    {
        $this->assertFalse(RequirementsChecker::gitMatchesMinimumRequiredVersion("1.8.0", "1.9"));
    }

    #[Test]
    public function gitCheckPassesForGit190()
    {
        $this->assertTrue(RequirementsChecker::gitMatchesMinimumRequiredVersion("1.9.0", "1.9"));
    }

    #[Test]
    public function gitCheckPassesForGit220()
    {
        $this->assertTrue(RequirementsChecker::gitMatchesMinimumRequiredVersion("2.2.0", "1.9"));
    }
}
