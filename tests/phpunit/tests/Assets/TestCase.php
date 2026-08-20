<?php

namespace WP_Syntex\Polylang\Tests\Assets;

use WP_Styles;
use WP_Scripts;
use PLL_UnitTestCase;

abstract class TestCase extends PLL_UnitTestCase {
	/**
	 * Resets WordPress style and script globals.
	 *
	 * @return void
	 */
	protected function reset_asset_globals() {
		$GLOBALS['wp_styles']  = new WP_Styles();
		$GLOBALS['wp_scripts'] = new WP_Scripts();
	}

	/**
	 * Asserts scripts are enqueued or not.
	 *
	 * @param array  $assets   {
	 *   @type string   $key   Whether the assets is enqueued in the header or in the footer. Accepts 'header' or 'footer'.
	 *   @type string[] $value The assets names to test against the given position.
	 * }
	 * @param string $content  The content to look into.
	 * @param string $position The position of the script. Used for more accurate error message.
	 * @return void
	 */
	protected function assert_enqueued_assets( $assets, $content, $position ) {
		$polylang_assets = $this->get_polylang_assets();

		if ( isset( $assets[ $position ] ) ) {
			foreach ( $assets[ $position ] as $script ) {
				$this->assertStringContainsString( $script, $content, "$script script is not enqueued in the $position as it should." );
				$polylang_assets[ $position ] = array_diff( $polylang_assets[ $position ], array( $script ) );
			}
		}

		foreach ( $polylang_assets[ $position ] as $script ) {
			$this->assertStringNotContainsString( $script, $content, "$script script is enqueued in the $position but it should not." );
		}
	}

	/**
	 * Getter for the Polylang scripts and stylesheets.
	 *
	 * @return array
	 */
	abstract protected function get_polylang_assets();
}
