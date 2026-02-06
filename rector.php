<?php

declare(strict_types=1);

use CodedMonkey\Dirigent\Rector\DoctrineAddDeferredExplicitChangeTrackingPolicyRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

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
    )
    ->withRules([
        DoctrineAddDeferredExplicitChangeTrackingPolicyRector::class,
    ])
    ->withSkip([
        // Exclude promotion of properties to the constructor for Doctrine entities
        ClassPropertyAssignToConstructorPromotionRector::class => [__DIR__ . '/src/Doctrine/Entity'],
    ]);
