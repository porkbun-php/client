<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Skip examples directory and vendor
    $rectorConfig->skip([
        __DIR__ . '/examples',
        __DIR__ . '/vendor',
    ]);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        LevelSetList::UP_TO_PHP_83,
    ]);

    // Import common classes automatically
    $rectorConfig->importNames();

    // Parallel processing for better performance
    $rectorConfig->parallel();
};
