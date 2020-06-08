<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * @package Polylang
 */

class PLL_Admin_Site_Health {
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'pll_info_options' ) );
	}

	public function pll_info_options( $debug_info ) {
		$languages = PLL()->model->get_languages_list();
		$options = get_option( 'polylang' );
		$fields = array();
		foreach ( $options as $key => $value ) {
			if ( ! is_array( $value ) ) {
				switch ( $key ){
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
			if ( is_array( $value ) ){
				if ( empty( $value ) ){
					$fields[ $key ]['label']   = __( $key, 'polylang' );
					$fields[ $key ]['value']   = __( 'N/A', 'polylang');
					$fields[ $key ]['private'] = false;
				} else {
					$fields[ $key ]['label']   = __( $key, 'polylang' );
					$fields[ $key ]['value']   = implode( ', ', $value );
					$fields[ $key ]['private'] = false;
				}

			}
		}


		$debug_info['polylang'] = array(
			'label'    => __( 'Polylang Options', 'my-plugin-slug' ),
			'fields' => $fields,
		);

		return $debug_info;
	}

}
