<?php

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return function ($rectorConfig) {
    $rectorConfig->paths([
        'admin',
        'frontend',
        'include',
        'install',
        'integrations',
        'modules',
        'polylang.php',
        'settings',
        'tests',
        'uninstall.php',
    ]);
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_72,
/*
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
*/
    ]);
    $rectorConfig->skip([
        LongArrayToShortArrayRector::class,
    ]);
};
