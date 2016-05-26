<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

/**
 * WPML Compatibility class
 * Defines some WPML constants
 * Registers strings in a persistent way as done by WPML
 *
 * @since 1.0.2
 */
class PLL_WPML_Compat {
	static protected $instance; // For singleton
	static protected $strings; // Used for cache

	/**
	 * Constructor
	 *
	 * @since 1.0.2
	 */
	protected function __construct() {
		self::$strings = get_option( 'polylang_wpml_strings', array() );

		add_action( 'pll_language_defined', array( $this, 'define_constants' ) );
		add_action( 'pll_get_strings', array( $this, 'get_strings' ) );
	}

	/**
	 * Get the current language for ICL_LANGUAGE_CODE
	 *
	 * @since 2.0
	 *
	 * @return bool|object PLL_Language object, false if no language is defined
	 */
	function get_current_language() {
		// Content edited on backend
		if ( ! PLL() instanceof PLL_Frontend  ) {
			if ( ! empty( $_GET['new_lang'] ) ) {
				return PLL()->model->get_language( $_GET['new_lang'] );
			} elseif ( ! empty( $_GET['post'] ) ) {
				return PLL()->model->post->get_language( (int) $_GET['post'] );
			}	elseif ( ! empty( $_GET['tag_ID'] ) ) {
				return PLL()->model->term->get_language( (int) $_GET['tag_ID'] );
			}
		}

		// Frontend or language filter on backend
		if ( ! empty( PLL()->curlang ) ) {
			return PLL()->curlang;
		}
		return false;
	}

	/**
	 * Defines two WPML constants once the language has been defined
	 * The compatibility with WPML is not perfect on admin side as the constants are defined
	 * in 'setup_theme' by Polylang ( based on user info ) and 'plugins_loaded' by WPML ( based on cookie )
	 *
	 * @since 0.9.5
	 */
	function define_constants() {
		$lang = $this->get_current_language();

		if ( ! empty ( $lang ) ) {
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
				define( 'ICL_LANGUAGE_CODE', $lang->slug );
			}

			if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
				define( 'ICL_LANGUAGE_NAME', $lang->name );
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
	 * Access to the single instance of the class
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	static public function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Unlike pll_register_string, icl_register_string stores the string in database
	 * so we need to do the same as some plugins or themes may expect this
	 * we use a serialized option to do this
	 *
	 * @since 1.0.2
	 *
	 * @param string $context the group in which the string is registered, defaults to 'polylang'
	 * @param string $name    a unique name for the string
	 * @param string $string  the string to register
	 */
	public function register_string( $context, $name, $string ) {
		// Registers the string if it does not exist yet
		$to_register = array( 'context' => $context, 'name' => $name, 'string' => $string, 'multiline' => false, 'icl' => true );
		if ( ! in_array( $to_register, self::$strings ) && $to_register['string'] ) {
			self::$strings[] = $to_register;
			update_option( 'polylang_wpml_strings', self::$strings );
		}
	}

	/**
	 * Removes a string from the registered strings list
	 *
	 * @since 1.0.2
	 *
	 * @param string $context the group in which the string is registered, defaults to 'polylang'
	 * @param string $name    a unique name for the string
	 */
	public function unregister_string( $context, $name ) {
		foreach ( self::$strings as $key => $string ) {
			if ( $string['context'] == $context && $string['name'] == $name ) {
				unset( self::$strings[ $key ] );
				update_option( 'polylang_wpml_strings', self::$strings );
			}
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
		return empty( self::$strings ) ? $strings : array_merge( $strings, self::$strings );
	}
}
