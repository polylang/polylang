<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

use WP_Syntex\Polylang\Capabilities\User\NOOP_User;
use WP_Syntex\Polylang\Capabilities\User\User_Interface;

/**
 * A class allowing to map Polylang's custom user capabilities to WP's native ones.
 *
 * @since 3.8
 */
class Capabilities {
	public const LANGUAGES    = 'manage_languages';
	public const TRANSLATIONS = 'manage_translations';

	/**
	 * The user prototype to be used for capability checks.
	 *
	 * @var User_Interface|null
	 */
	private static ?User_Interface $user_prototype = null;

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

	/**
	 * Returns the user instance to be used for capability checks using prototype pattern.
	 *
	 * @since 3.8
	 *
	 * @return User_Interface
	 */
	public static function get_user(): User_Interface {
		if ( ! self::$user_prototype ) {
			/**
			 * Filters the user prototype to be used for capability checks.
			 *
			 * @since 3.8
			 *
			 * @param User_Interface|null $user_prototype The user prototype to be used for capability checks.
			 */
			self::$user_prototype = apply_filters( 'pll_user_prototype', null );

			if ( ! self::$user_prototype ) {
				self::$user_prototype = new NOOP_User( wp_get_current_user() );
			}
		}

		return self::$user_prototype->clone( wp_get_current_user() );
	}
}
