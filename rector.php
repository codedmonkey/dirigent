<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withImportNames(importShortClasses: false)
    ->withPhpSets()
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    )
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
        phpunit: true,
    );
