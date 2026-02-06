<?php

/**
 * Helper trait for backward compatibility with Polylang /src/ restructuring.
 */
trait PLL_File_Path_Helper_Trait {

	/**
	 * Returns the path to a Polylang file, with backward compatibility for the /src/ restructuring.
	 *
	 * @param string $path Relative path to the file (e.g., 'api.php' or 'settings/languages.php').
	 * @return string Full path to the file.
	 */
	protected static function get_pll_file_path( string $path ): string {
		$path = ltrim( $path, '/' );

		// New structure (Polylang 3.8+): files in /src/ directory.
		if ( file_exists( POLYLANG_DIR . "/src/{$path}" ) ) {
			return POLYLANG_DIR . "/src/{$path}";
		}

		// Backward compatibility with Polylang < 3.8: files in root or /include/ directory.
		if ( false === strpos( $path, '/' ) ) {
			return POLYLANG_DIR . "/include/{$path}";
		}

		return POLYLANG_DIR . "/{$path}";
	}

	/**
	 * Requires the API functions.
	 *
	 * @return void
	 */
	protected static function require_api(): void {
		require_once self::get_pll_file_path( 'api.php' ); // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath
	}
}
