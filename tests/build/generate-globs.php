<?php

/**
 * Creates a list of files to load.
 *
 * @since 3.8
 *
 * @param string $dirname Name of the folder containing the files to load.
 * @param string $type    Name to use in the messages.
 * @return void
 */
function create_load_data_file( string $dirname, string $type ): void {
	$root_path  = dirname( __DIR__, 2 );
	$file_paths = glob( "{$root_path}/{$dirname}/*/load.php", GLOB_NOSORT );

	if ( ! is_array( $file_paths ) ) {
		echo "\e[91mError while retrieving the {$type} files\n";
		return;
	}
	if ( empty( $file_paths ) ) {
		echo "\e[91mCould not find {$type} files\n";
	}

	sort( $file_paths, SORT_STRING | SORT_FLAG_CASE );

	$write = "<?php
/**
 * @package Polylang
 *
 * /!\ DO NOT DIRECTLY EDIT THIS FILE, THIS FILE IS AUTO-GENERATED AS PART OF THE BUILD PROCESS.
 */

return array(\n";

	foreach ( $file_paths as $path ) {
		$write .= "\t'" . basename( dirname( $path ) ) . "',\n";
	}
	$write .= ");\n";

	if ( false === file_put_contents( "{$root_path}/{$dirname}/{$type}-files.php", $write ) ) {
		echo "\e[91mError while writing the data file for {$type}s\n";
		return;
	}

	echo "\e[32mGenerated {$type}s data file\n";
}

// Create the integrations loader.
create_load_data_file( 'integrations', 'integration' );
// Create the modules loader.
create_load_data_file( 'modules', 'module' );
