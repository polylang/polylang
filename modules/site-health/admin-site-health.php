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

	public $model;

	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 */
	public function __construct( &$polylang ) {
		// Information tab
		add_filter( 'debug_information', array( $this, 'info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_languages' ), 15 );

		// tests Tab
		add_filter( 'site_status_tests', array( $this, 'is_homepage' ) );

		$this->model = &$polylang->model;
	}

	/**
	 * Return a list of key to exclude from site health informations
	 *
	 * @return array list of option key to ignore
	 * @since   2.8
	 */
	protected function exclude_options_key() {
		return array(
			'uninstall',
			'first_activation',
		);
	}

	/**
	 * Return a list of key to exclude from site health informations.
	 *
	 * @return array list of language key to ignore
	 * @since   2.8
	 */
	protected function exclude_lang_key() {
		return array(
			'flag',
			'host',
			'taxonomy',
			'description',
			'parent',
			'filter',
			'custom_flag',
		);
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
			if ( in_array(
				$key,
				$this->exclude_options_key()
			)
				) {
				continue;
			}
			if ( ! is_array( $value ) ) {
				if ( false === $value ) {
					$value = '0';
				}
				$fields[ $key ]['label']   = $key;
				$fields[ $key ]['value']   = $value;
			} else {
				if ( empty( $value ) ) {
					$fields[ $key ]['label']   = $key;
					$fields[ $key ]['value']   = '0';
				} else {
					switch ( $key ) {
						case 'post_types':
							$fields[ $key ]['label'] = $key;
							$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_post_types() );
							break;
						case 'taxonomies':
							$fields[ $key ]['label'] = $key;
							$fields[ $key ]['value'] = implode(
								', ',
								$this->model->get_translated_taxonomies()
							);
							break;
						case 'nav_menus':
							$current_theme = get_stylesheet();
							foreach ( $value[ $current_theme ] as $location => $lang ) {
								// translators: placeholder is the menu location name
								$fields[ $location ]['label'] = sprintf( __( 'Menu: %s', 'polylang' ), $location );
								array_walk(
									$lang,
									function ( &$value, $key ) {
										$value = "$key:$value";
									}
									);
								$fields[ $location ]['value'] = implode( ' | ', $lang );

							}
							break;
						case 'media':
							array_walk(
								$value,
								function ( &$value, $key ) {
									$value = "$key: $value";
								}
							);
							$fields[ $key ]['label'] = '';
							if ( ! empty( $fields[ $key ]['value'] ) ) {
								$fields[ $key ]['value'] = implode( ',', $value );
							}
							break;
						default:
							$fields[ $key ]['label']   = $key;
							$fields[ $key ]['value']   = implode( ', ', $value );
							break;
					}
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
					$value = '0';
				}

				if ( in_array(
					$key,
					$this->exclude_lang_key()
				)
				) {
					continue;
				}

				$fields[ $key ]['label'] = $key;
				$fields[ $key ]['value'] = $value;

				if ( 'term_group' === $key ) {
					$fields[ $key ]['label'] = _x( 'order', 'Order of the language in the language switcher', 'polylang' );
				}
			}
			$flag = $this->get_flag( $language );

			$lang_name = sanitize_title( $language->name );
			$debug_info[ $lang_name ] = array(
				// translators: placeholder is the language name
				'label'  => sprintf( __( 'Language: %s', 'polylang' ), $language->name ),
				// translators: placeholder is the flag image
				'description' => sprintf( __( 'Flag used in the language switcher: %s', 'polylang' ), $flag ),
				'fields' => $fields,
			);
		}
		return $debug_info;
	}

	/**
	 * Return the flag used in the language switcher
	 *
	 * @param object $language language object
	 * @return mixed
	 * @since   2.8
	 */
	public function get_flag( $language ) {
		if ( empty( $language->flag ) ) {
			return $flag = '<span class="no-flag">' . __( 'Undefined', 'polylang' ) . '</span>';
		}
		if ( ! empty( $language->custom_flag_url ) ) {
			return $flag = '<img src="' . $language->custom_flag_url . '" height="11">';
		} else {
			return $flag = $language->flag;
		}
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
			$tests['direct']['pll_homepage'] = array(
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
			'test'        => 'pll_homepage',
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
				'color' => 'blue',
			);
		}
		return $result;
	}
}
