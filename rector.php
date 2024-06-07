<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php71\Rector\List_\ListToArrayDestructRector;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/admin',
			__DIR__ . '/frontend',
			__DIR__ . '/include',
			__DIR__ . '/install',
			__DIR__ . '/integrations',
			__DIR__ . '/modules',
			__DIR__ . '/settings',
			__DIR__ . '/tests',
			__DIR__ . '/polylang.php',
			__DIR__ . '/uninstall.php',
		]
	)
	->withSets(
		[
			LevelSetList::UP_TO_PHP_72,
		]
	)
	->withSkip(
		[
			LongArrayToShortArrayRector::class,
			ListToArrayDestructRector::class,
		]
	);
