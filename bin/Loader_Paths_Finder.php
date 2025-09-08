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
class Loader_Paths_Finder {
	/**
	 * Hard-codes `glob()`s.
	 *
	 * @since 3.8
	 *
	 * @throws RuntimeException If arguments are missing or the operation fails.
	 *
	 * @param Event $event The Composer event. Expects one argument.
	 *     - Absolute path to the folder containing the files to load.
	 * @return void
	 */
	public static function hard_code( Event $event ): void {
		$args = $event->getArguments();

		if ( empty( $args[0] ) ) {
			throw new RuntimeException( 'No path to folder provided' );
		}

		$base_path   = realpath( $args[0] );
		$type        = rtrim( basename( $base_path ), 's' );
		$plugin_name = ucwords( str_replace( '-', ' ', basename( dirname( $base_path ) ) ) );
		$file_paths  = glob( "{$base_path}/*/load.php", \GLOB_NOSORT );

		if ( ! is_array( $file_paths ) || empty( $file_paths ) ) {
			throw new RuntimeException( "Could not retrieve the {$type} files in {$plugin_name}" );
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

		if ( false === file_put_contents( "{$base_path}/{$type}-build.php", $write ) ) {
			throw new RuntimeException( "Error while writing the data file for {$type}s in {$plugin_name}" );
		}

		echo "\e[32mGenerated {$type}s data file for {$plugin_name}\e[0m\n";
	}
}
