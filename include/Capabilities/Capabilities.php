<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * A class allowing to map Polylang's custom user capabilities to WP's native ones.
 *
 * @since 3.8
 */
class Capabilities {
	public const LANGUAGES    = 'manage_languages';
	public const TRANSLATIONS = 'manage_translations';

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
		if ( in_array( $cap, array( self::TRANSLATIONS, self::LANGUAGES ), true ) ) {
			$caps   = array_diff( $caps, array( $cap ) );
			$caps[] = 'manage_options';
		}

		return $caps;
	}
}
