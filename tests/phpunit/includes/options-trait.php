<?php

use Brain\Monkey\Functions;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry as Options_Registry;

/**
 * A trait to create new instances of the options.
 */
trait PLL_Options_Trait {
	/**
	 * Creates a new instance of the options, resets the values, and returns the instance.
	 *
	 * @since 3.7
	 *
	 * @param array $options Initial options.
	 * @return Options|array An instance of `Options` for PLL 3.7+, an array otherwise.
	 */
	protected static function create_options( array $options = array() ) {
		if ( ! class_exists( Options::class ) ) {
			// Backward compatibility with Polylang < 3.7. Required for PLLWC.
			return array_merge( PLL_Install::get_default_options(), $options );
		}

		if ( ! empty( $options ) ) {
			update_option( Options::OPTION_NAME, $options );
		} else {
			delete_option( Options::OPTION_NAME );
		}

		Functions\when( 'pll_is_plugin_active' )->alias(
			function ( $value ) {
				return POLYLANG_BASENAME === $value;
			}
		);
		add_action( 'pll_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		return new Options();
	}
}
