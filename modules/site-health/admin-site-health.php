<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Class PLL_Admin_Site_Health to add debug info in WP Site Health
 *
 * @since 2.8
 */
class PLL_Admin_Site_Health {
	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'pll_info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'pll_info_languages' ), 16 );
	}

	/**
	 * Add Polylang Options to Site Health Informations tab.
	 *
	 * @param array $debug_info array options to display.
	 * @return array
	 * @since   2.8
	 */
	public function pll_info_options( $debug_info ) {
		$options = get_option( 'polylang' );
		$fields = array();
		foreach ( $options as $key => $value ) {
			if ( ! is_array( $value ) ) {
				switch ( $key ) {
					case 'first_activation':
						$fields[ $key ]['label']   = $key;
						$fields[ $key ]['value']   = date_i18n( 'd/m/Y', $value );
						$fields[ $key ]['private'] = false;
						break;
					default:
						$fields[ $key ]['label']   = $key;
						$fields[ $key ]['value']   = $value;
						$fields[ $key ]['private'] = false;
						break;
				}
			}
			if ( is_array( $value ) ) {
				if ( empty( $value ) ) {
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = __( 'N/A', 'polylang' );
					$fields[ $key ]['private'] = false;
				} else {
					$fields[ $key ]['label']   = $key;
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

	/**
	 * Add Polylang Languages settings to Site Health Informations tab.
	 *
	 * @param array $debug_info array options to display.
	 * @return array
	 * @since   2.8
	 */
	public function pll_info_languages( $debug_info ) {
		$languages = PLL()->model->get_languages_list();
		$fields = array();
		foreach ( $languages as $language ) {
			foreach ( $language as $key => $value ) {
				if ( empty( $value ) ) {
					$value = __( 'N/A', 'polylang' );
				}

				$to_be_removed = array(
					'flag' => true, // remove the flag as filter only display plain text
					'host' => true, // Key not used by Polylang yet
				);
				if ( $to_be_removed[ $key ] ) {
					continue;
				}
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = $value;
					$fields[ $key ]['private'] = false;
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
