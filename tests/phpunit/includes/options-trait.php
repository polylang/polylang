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
	 * @return Options
	 */
	protected static function create_reset_options(): Options {
		return self::create_options( false );
	}

	/**
	 * Returns a new instance of the options.
	 *
	 * @since 3.7
	 *
	 * @param array|false $options Initial options. Use `false` to delete previous options.
	 * @return Options
	 */
	protected static function create_options( $options = array() ): Options {
		if ( ! is_array( $options ) ) {
			// Reset.
			delete_option( Options::OPTION_NAME );
		} elseif ( ! empty( $options ) ) {
			// Use the given options.
			$prev_options = get_option( Options::OPTION_NAME );
			if ( is_array( $prev_options ) ) {
				$options = array_merge( $prev_options, $options );
			}
			update_option( Options::OPTION_NAME, $options );
		}

		Functions\when( 'pll_is_plugin_active' )->alias(
			function ( $value ) {
				return POLYLANG_BASENAME === $value;
			}
		);
		add_action( 'pll_init_options_for_blog', array( Options_Registry::class, 'register_options' ) );
		return new Options();
	}
}
