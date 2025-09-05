<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Script;

use RuntimeException;
use Composer\Script\Event;

/**
 * Class to hard-code `glob()`s.
 *
 * @since 3.8
 */
class Globber {
	/**
	 * Hard-codes `glob()`s.
	 *
	 * @since 3.8
	 *
	 * @throws RuntimeException If arguments are missing.
	 *
	 * @param Event $event The Composer event. Expects three arguments.
	 *     - Absolute path to the folder containing the files to load.
	 *     - Name to use in the messages and the name of the resulting file (`module` or `integration`).
	 *     - Name of the package.
	 * @return void
	 */
	public static function hard_code( Event $event ): void {
		$args = $event->getArguments();

		if ( empty( $args[0] ) ) {
			throw new RuntimeException( 'No path to folder provided' );
		}
		if ( empty( $args[1] ) ) {
			throw new RuntimeException( 'No type provided' );
		}
		if ( empty( $args[2] ) ) {
			throw new RuntimeException( 'No package name provided' );
		}

		$base_path   = $args[0];
		$type        = $args[1];
		$plugin_name = $args[2];
		$file_paths  = glob( "{$base_path}/*/load.php", \GLOB_NOSORT );

		if ( ! is_array( $file_paths ) ) {
			echo "\e[91mError while retrieving the {$type} files in {$plugin_name}\e[0m\n";
			return;
		}
		if ( empty( $file_paths ) ) {
			echo "\e[91mCould not find {$type} files in {$plugin_name}\e[0m\n";
		}

		sort( $file_paths, \SORT_STRING | \SORT_FLAG_CASE );

		$write = "<?php
/**
 * @package {$plugin_name}
 *
 * /!\ DO NOT DIRECTLY EDIT THIS FILE, THIS FILE IS AUTO-GENERATED AS PART OF THE BUILD PROCESS.
 */

return array(\n";

		foreach ( $file_paths as $path ) {
			$write .= "\t'" . basename( dirname( $path ) ) . "',\n";
		}
		$write .= ");\n";

		if ( false === file_put_contents( "{$base_path}/{$type}-files.php", $write ) ) {
			echo "\e[91mError while writing the data file for {$type}s in {$plugin_name}\e[0m\n";
			return;
		}

		echo "\e[32mGenerated {$type}s data file for {$plugin_name}\e[0m\n";
	}
}
