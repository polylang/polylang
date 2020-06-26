<?php
/**
 * @package Polylang
 */

/**
 * Container for 3rd party plugins ( and themes ) integrations.
 * This class is available as soon as the plugin is loaded.
 *
 * @since 1.0
 * @since 2.8 Renamed from PLL_Plugins_Compat to PLL_Integrations.
 */
class PLL_Integrations {
	/**
	 * Singleton instance.
	 *
	 * @var PLL_Integrations
	 */
	protected static $instance;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	protected function __construct() {
		// Loads external integrations.
		foreach ( glob( __DIR__ . '/*/load.php', GLOB_NOSORT ) as $load_script ) { // phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.UnusedVariable
			require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}
	}

	/**
	 * Access to the single instance of the class.
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

class_alias( 'PLL_Integrations', 'PLL_Plugins_Compat' ); // For Backward compatibility.
