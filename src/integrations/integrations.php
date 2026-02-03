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
#[AllowDynamicProperties]
class PLL_Integrations {
	/**
	 * Singleton instance.
	 *
	 * @var PLL_Integrations|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	protected function __construct() {}

	/**
	 * Returns the single instance of the class.
	 *
	 * @since 1.7
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Requires integrations.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	protected function init(): void {
		$load_scripts = require __DIR__ . '/integration-build.php';

		foreach ( $load_scripts as $load_script ) {
			require_once __DIR__ . "/{$load_script}/load.php";
		}
	}
}

class_alias( 'PLL_Integrations', 'PLL_Plugins_Compat' ); // For Backward compatibility.
