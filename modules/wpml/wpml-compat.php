<?php
/**
 * @package Polylang
 */

/**
 * WPML Compatibility class
 * Defines some WPML constants
 * Registers strings in a persistent way as done by WPML
 *
 * @since 1.0.2
 */
class PLL_WPML_Compat {
	use PLL_Container_Compat_Trait;

	/**
	 * Stores the strings registered with the WPML API.
	 *
	 * @var array
	 */
	protected $strings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.2
	 * @since 3.3 Changed visibility from protected to public.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->container_identifiers = array(
			'api' => 'wpml_api',
		);

		$this->strings = (array) get_option( 'polylang_wpml_strings', array() );
	}

	/**
	 * Launches hooks.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'pll_language_defined', array( $this, 'define_constants' ) );
		add_action( 'pll_no_language_defined', array( $this, 'define_constants' ) );
		add_filter( 'pll_get_strings', array( $this, 'get_strings' ) );
	}

	/**
	 * Access to the single instance of the class.
	 *
	 * @since 1.7
	 * @since 3.3 Deprecated.
	 * @deprecated
	 *
	 * @return PLL_WPML_Compat
	 */
	public static function instance() {
		_deprecated_function( __FUNCTION__, '3.3', "PLL()->get( 'wpml_compat' )" );

		if ( ! PLL()->has( 'wpml_compat' ) ) {
			PLL()->add_shared( 'wpml_compat', self::class );
		}

		return PLL()->get( 'wpml_compat' );
	}

	/**
	 * Defines two WPML constants once the language has been defined
	 * The compatibility with WPML is not perfect on admin side as the constants are defined
	 * in 'setup_theme' by Polylang ( based on user info ) and 'plugins_loaded' by WPML ( based on cookie )
	 *
	 * @since 0.9.5
	 *
	 * @return void
	 */
	public function define_constants() {
		if ( ! empty( PLL()->curlang ) ) {
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
				define( 'ICL_LANGUAGE_CODE', PLL()->curlang->slug );
			}

			if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
				define( 'ICL_LANGUAGE_NAME', PLL()->curlang->name );
			}
		} elseif ( ! PLL() instanceof PLL_Frontend ) {
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
				define( 'ICL_LANGUAGE_CODE', 'all' );
			}

			if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
				define( 'ICL_LANGUAGE_NAME', '' );
			}
		}
	}

	/**
	 * Unlike pll_register_string, icl_register_string stores the string in database
	 * so we need to do the same as some plugins or themes may expect this
	 * we use a serialized option to do this
	 *
	 * @since 1.0.2
	 *
	 * @param string $context The group in which the string is registered.
	 * @param string $name    A unique name for the string.
	 * @param string $string  The string to register.
	 * @return void
	 */
	public function register_string( $context, $name, $string ) {
		if ( ! $string || ! is_scalar( $string ) ) {
			return;
		}

		// If a string has already been registered with the same name and context, let's replace it.
		$exist_string = $this->get_string_by_context_and_name( $context, $name );
		if ( $exist_string && $exist_string !== $string ) {
			$languages = PLL()->model->get_languages_list();

			// Assign translations of the old string to the new string, except for the default language.
			foreach ( $languages as $language ) {
				if ( pll_default_language() !== $language->slug ) {
					$mo = new PLL_MO();
					$mo->import_from_db( $language );
					$mo->add_entry( $mo->make_entry( $string, $mo->translate( $exist_string ) ) );
					$mo->export_to_db( $language );
				}
			}
			$this->unregister_string( $context, $name );
		}

		// Registers the string if it does not exist yet (multiline as in WPML).
		$to_register = array( 'context' => $context, 'name' => $name, 'string' => $string, 'multiline' => true, 'icl' => true );
		if ( ! in_array( $to_register, $this->strings ) ) {
			$key = md5( "$context | $name" );
			$this->strings[ $key ] = $to_register;
			update_option( 'polylang_wpml_strings', $this->strings );
		}
	}

	/**
	 * Removes a string from the registered strings list
	 *
	 * @since 1.0.2
	 *
	 * @param string $context The group in which the string is registered.
	 * @param string $name    A unique name for the string.
	 * @return void
	 */
	public function unregister_string( $context, $name ) {
		$key = md5( "$context | $name" );
		if ( isset( $this->strings[ $key ] ) ) {
			unset( $this->strings[ $key ] );
			update_option( 'polylang_wpml_strings', $this->strings );
		}
	}

	/**
	 * Adds strings registered by icl_register_string to those registered by pll_register_string
	 *
	 * @since 1.0.2
	 *
	 * @param array $strings existing registered strings
	 * @return array registered strings with added strings through WPML API
	 */
	public function get_strings( $strings ) {
		return empty( $this->strings ) ? $strings : array_merge( $strings, $this->strings );
	}

	/**
	 * Get a registered string by its context and name
	 *
	 * @since 2.0
	 *
	 * @param string $context The group in which the string is registered.
	 * @param string $name    A unique name for the string.
	 * @return bool|string The registered string, false if none was found.
	 */
	public function get_string_by_context_and_name( $context, $name ) {
		$key = md5( "$context | $name" );
		return isset( $this->strings[ $key ] ) ? $this->strings[ $key ]['string'] : false;
	}
}
