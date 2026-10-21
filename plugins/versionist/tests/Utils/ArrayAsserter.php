<?php

namespace Versionist\Tests\Utils;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\IsIdentical;

class ArrayAsserter
{
    /**
     * Asserts that one array is identical to another, but the order of keys does not matter.
     *
     * @param $expected
     * @param $actual
     */
    public static function assertSimilar($expected, $actual)
    {
        if (!is_array($expected) || !is_array($actual)) {
            throw new AssertionFailedError('Only arrays can be compared');
        }

        ksort($expected);
        ksort($actual);

        $constraint = new IsIdentical($expected);
        $constraint->evaluate($actual);
    }
}
