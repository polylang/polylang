<?php

/**
 * Creates a list of files to load.
 * This creates a php file that returns an array folder names.
 *
 * @since 3.8
 *
 * @param string $base_path   Absolute path to the folder containing the files to load.
 * @param string $type        Name to use in the messages.
 * @param string $plugin_name Name of the plugin.
 * @return void
 */
function create_load_data_file( string $base_path, string $type, string $plugin_name ): void {
	$file_paths = glob( "{$base_path}/*/load.php", GLOB_NOSORT );

	if ( ! is_array( $file_paths ) ) {
		echo "\e[91mError while retrieving the {$type} files in {$plugin_name}\n";
		return;
	}
	if ( empty( $file_paths ) ) {
		echo "\e[91mCould not find {$type} files in {$plugin_name}\n";
	}

	sort( $file_paths, SORT_STRING | SORT_FLAG_CASE );

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
		echo "\e[91mError while writing the data file for {$type}s in {$plugin_name}\n";
		return;
	}

	echo "\e[32mGenerated {$type}s data file for {$plugin_name}\n";
}

// Create the integrations loader.
create_load_data_file( dirname( __DIR__ ) . '/integrations', 'integration', 'Polylang' );
// Create the modules loader.
create_load_data_file( dirname( __DIR__ ) . '/modules', 'module', 'Polylang' );
