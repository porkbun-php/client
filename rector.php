<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Skip examples directory and vendor
    $rectorConfig->skip([
        __DIR__ . '/examples',
        __DIR__ . '/vendor',
        // Skip readonly for Config property in Client since Config is mutable
        ReadOnlyPropertyRector::class => [
            __DIR__ . '/src/Client.php',
        ],
        // Laravel container uses ArrayAccess interface at runtime but contracts don't expose it
        // Skip strict array param rules for Laravel integration
        StrictArrayParamDimFetchRector::class => [
            __DIR__ . '/src/Laravel/PorkbunServiceProvider.php',
        ],
        // Semantic names like $type, $recordType, $aRecords are clearer than type-derived names
        RenamePropertyToMatchTypeRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        RenameParamToMatchTypeRector::class,
        // !== null is cleaner than instanceof when return type already narrows to ?T
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Stateless private methods should stay static — static signals "no $this dependency"
        LocallyCalledStaticMethodToNonStaticRector::class,
    ]);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        LevelSetList::UP_TO_PHP_84,
    ]);

    // Import common classes automatically
    $rectorConfig->importNames();

    // Parallel processing for better performance
    $rectorConfig->parallel();
};
