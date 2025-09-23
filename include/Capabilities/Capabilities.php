<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * A class allowing to determine if a user can translate content.
 *
 * @since 3.8
 */
class Capabilities {
	/**
	 * Constructor.
	 *
	 * @since 3.8
	 */
	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'map_custom_caps' ), 1, 2 );
	}

	/**
	 * Filters user capabilities to handle PLL's custom capabilities.
	 *
	 * @since 3.8
	 *
	 * @param string[] $caps Primitive capabilities required by the user.
	 * @param string   $cap  Capability being checked.
	 * @return string[]
	 */
	public function map_custom_caps( $caps, $cap ) {
		if ( 'manage_translations' === $cap ) {
			return array( 'manage_options' );
		}

		return $caps;
	}
}
