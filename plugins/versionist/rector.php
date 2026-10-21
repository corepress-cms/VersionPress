<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Disabled since before the modernization; kept as a reference for the Playwright port.
        __DIR__ . '/tests/Selenium',
    ])
    // Unbounded parallelism runs out of memory on many-core machines.
    ->withParallel(maxNumberOfProcess: 4)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSets([PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES])
    ->withComposerBased(phpunit: true);
