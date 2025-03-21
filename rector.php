<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php71\Rector\List_\ListToArrayDestructRector;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/src/admin',
			__DIR__ . '/src/frontend',
			__DIR__ . '/src/include',
			__DIR__ . '/src/install',
			__DIR__ . '/src/integrations',
			__DIR__ . '/src/modules',
			__DIR__ . '/src/settings',
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
			__DIR__ . '/src/install/plugin-updater.php',
			StringClassNameToClassConstantRector::class => [ // Ignoring this allows to silence a warning from PHPUnit.
				__DIR__ . '/tests/phpunit/tests/Options/Options/test-OffsetSet.php',
				__DIR__ . '/tests/phpunit/tests/Options/Options/test-OffsetUnset.php',
			],
		]
	);
