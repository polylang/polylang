<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Class PLL_Admin_Site_Health
 *
 * @since
 */
class PLL_Admin_Site_Health {
	/**
	 * PLL_Admin_Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'pll_info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'pll_info_languages' ), 16 );
	}

	/**
	 * @param $debug_info array
	 *
	 * @return mixed
	 * @since
	 */
	public function pll_info_options( $debug_info ) {
		$options = get_option( 'polylang' );
		$fields = array();
		foreach ( $options as $key => $value ) {
			if ( ! is_array( $value ) ) {
				switch ( $key ) {
					case 'first_activation':
						$fields[ $key ]['label']   = __( $key, 'polylang' );
						$fields[ $key ]['value']   = date_i18n( 'd/m/Y', $value );
						$fields[ $key ]['private'] = false;
						break;
					default:
						$fields[ $key ]['label']   = __( $key, 'polylang' );
						$fields[ $key ]['value']   = $value;
						$fields[ $key ]['private'] = false;
						break;
				}
			}
			if ( is_array( $value ) ) {
				if ( empty( $value ) ) {
					$fields[ $key ]['label']   = __( $key, 'polylang' );
					$fields[ $key ]['value']   = __( 'N/A', 'polylang' );
					$fields[ $key ]['private'] = false;
				} else {
					$fields[ $key ]['label']   = __( $key, 'polylang' );
					$fields[ $key ]['value']   = implode( ', ', $value );
					$fields[ $key ]['private'] = false;
				}
			}
		}

		$debug_info['polylang'] = array(
			'label'    => __( 'Polylang Options', 'polylang' ),
			'fields' => $fields,
			'show_count' => true,
		);

		return $debug_info;
	}

	public function pll_info_languages( $debug_info ) {
		$languages = PLL()->model->get_languages_list();
		$fields = array();
		foreach ( $languages as $language ) {
			foreach ( $language as $key => $value ) {
				if ( empty( $value ) ) {
					$value = __( 'N/A', 'polylang' );
				}
				// remove the flag as filter only display plain text
				if ( 'flag' !== $key ) {
					$fields[ $key ]['label']   = __( $key, 'polylang' );
					$fields[ $key ]['value']   = $value;
					$fields[ $key ]['private'] = false;
				}
			}
			if ( empty( $language->flag ) ) {
				$language->flag = __( 'Undefined', 'polylang' );
			}
			$debug_info[ $language->name ] = array(
				'label'  => $language->name,
				// translators: placeholder is the flag image
				'description' => sprintf( __( 'Flag used in the language switcher: %s', 'polylang' ), $language->flag ),
				'fields' => $fields,
				'show_count' => true,
			);
		}
		return $debug_info;
	}
}
