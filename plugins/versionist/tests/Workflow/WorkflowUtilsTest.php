<?php

namespace Versionist\Tests\Workflow;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Utils\WorkflowUtils;

class WorkflowUtilsTest extends TestCase
{

    #[Test]
    public function isCloneNameValidReturnsTrueForValidNames()
    {
        $this->assertTrue(WorkflowUtils::isCloneNameValid('a'));
        $this->assertTrue(WorkflowUtils::isCloneNameValid('_'));
        $this->assertTrue(WorkflowUtils::isCloneNameValid('-'));
        $this->assertTrue(WorkflowUtils::isCloneNameValid('1'));
        $this->assertTrue(WorkflowUtils::isCloneNameValid('abc123_-'));
    }

    #[Test]
    public function isCloneNameValidReturnsFalseForInvalidNames()
    {
        $this->assertFalse(WorkflowUtils::isCloneNameValid('**'));
        $this->assertFalse(WorkflowUtils::isCloneNameValid(''));
        $this->assertFalse(WorkflowUtils::isCloneNameValid('abc**'));
    }
}
