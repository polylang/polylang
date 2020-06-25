<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Admin_Site_Health to add debug info in WP Site Health
 *
 * @since 2.8
 * @link https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
 */
class PLL_Admin_Site_Health {
	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 */
	public function __construct() {
		// Information tab
		add_filter( 'debug_information', array( $this, 'info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_languages' ), 16 );

		// tests Tab
		add_filter( 'site_status_tests', array( $this, 'is_homepage' ) );
	}

	/**
	 * Add Polylang Options to Site Health Informations tab.
	 *
	 * @param array $debug_info array options to display.
	 * @return array
	 * @since   2.8
	 */
	public function info_options( $debug_info ) {
		$options = get_option( 'polylang' );
		$fields = array();
		foreach ( $options as $key => $value ) {
			$to_be_removed = apply_filters(
				'pll_site_heath_ignore_list',
				array(
					'flag'             => false, // remove the flag as filter only display plain text
					'host'             => true, // Key not used by Polylang yet
					'first_activation' => true, // not usefull key for debug purpose
				)
			);
			if ( $to_be_removed[ $key ] ) {
				continue;
			}
			if ( ! is_array( $value ) ) {
				$fields[ $key ]['label']   = $key;
				$fields[ $key ]['value']   = $value;

			} else {
				if ( empty( $value ) ) {
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = __( 'N/A', 'polylang' );
				} else {
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = implode( ', ', $value );
				}
			}
		}

		$debug_info['polylang'] = array(
			'label'    => __( 'Polylang Options', 'polylang' ),
			'fields' => $fields,
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
	public function info_languages( $debug_info ) {
		$languages = PLL()->model->get_languages_list();
		$fields = array();
		foreach ( $languages as $language ) {
			foreach ( $language as $key => $value ) {
				if ( empty( $value ) ) {
					$value = __( 'N/A', 'polylang' );
				}

				if ( in_array( $key, array( 'flag', 'host', 'taxonomy', 'description', 'parent', 'filter' ) ) ) {
					continue;
				}
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = $value;

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

	/**
	 * Add a Site Health test on Home Page translation
	 *
	 * @param array $tests array with tests declaration data
	 * @return array
	 * @since   2.8
	 */
	public function is_homepage( $tests ) {
		// add test only if static page on front page.
		if ( '0' !== get_option( 'page_on_front' ) ) {
			$tests['direct']['pll_hp'] = array(
				'label' => __( 'Home page translated', 'polylang' ),
				'test'  => array( $this, 'homepage_test' ),
			);
		}
		return $tests;
	}

	/**
	 * Test if the home page is translated or not.
	 *
	 * @return array $result array with test results
	 * @since 2.8
	 */
	public function homepage_test() {
		$result = array(
			'label'       => __( 'All languages have a translated home page', 'polylang' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Polylang', 'polylang' ),
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'A website can\'t be displayed without homepage.', 'polylang' )
			),
			'actions'     => '',
			'test'        => 'pll_hp',
		);
		$untranslated = array();
		foreach ( PLL()->model->get_languages_list() as $language ) {
			if ( ! $language->page_on_front ) {
				$untranslated[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( PLL()->links->get_new_post_translation_link( get_option( 'page_on_front' ), $language ) ),
					esc_html( $language->name )
				);
			}
		}
		if ( ! empty( $untranslated ) ) {
			$result['status'] = 'critical';
			$result['label'] = __( 'Translation of Home page missing in one or more languages', 'polylang' );
			$result['description'] = sprintf(
			/* translators: %s is a comma separated list of native language names */
				esc_html__( 'You must translate your static front page in %s.', 'polylang' ),
				implode( ', ', $untranslated ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			$result['actions'] .= '';
			$result['badge']       = array(
				'label' => __( 'Polylang', 'polylang' ),
			);
		}
		return $result;
	}
}
