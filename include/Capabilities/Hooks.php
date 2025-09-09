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
class Hooks {
	/**
	 * Constructor.
	 *
	 * @since 3.8
	 */
	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'map_custom_caps' ), 1, 4 );
	}

	/**
	 * Filters user capabilities to handle PLL's custom capabilities.
	 *
	 * @since 3.8
	 *
	 * @param string[] $caps    Primitive capabilities required by the user.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds context to the capability check, typically
	 *                          starting with an object ID.
	 * @return string[]
	 */
	public function map_custom_caps( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'manage_translations':
				$caps = array( 'manage_options' );
				break;
		}

		/**
		 * Filter the user capabilities to handle PLL's custom capabilities.
		 *
		 * @since 3.8
		 *
		 * @param string[] $caps    Primitive capabilities required by the user.
		 * @param string   $cap     Capability being checked.
		 * @param int      $user_id The user ID.
		 * @param array    $args    Adds context to the capability check, typically
		 *                          starting with an object ID.
		 */
		return apply_filters( 'pll_map_meta_cap', $caps, $cap, $user_id, $args );
	}
}
