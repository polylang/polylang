<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Admin_Site_Health to add debug info in WP Site Health.
 *
 * @link https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
 *
 * @since 2.8
 */
class PLL_Admin_Site_Health {
	/**
	 * A reference to the PLL_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * A reference to the PLL_Admin_Links instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Admin_Links
	 */
	protected $links;

	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->links = &$polylang->links;

		// Information tab.
		add_filter( 'debug_information', array( $this, 'info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_languages' ), 15 );

		// Tests Tab.
		add_filter( 'site_status_tests', array( $this, 'status_tests' ) );
	}

	/**
	 * Returns a list of keys to exclude from the site health information.
	 *
	 * @since 2.8
	 *
	 * @return array List of option keys to ignore.
	 */
	protected function exclude_options_keys() {
		return array(
			'uninstall',
			'first_activation',
		);
	}

	/**
	 * Returns a list of keys to exclude from the site health information.
	 *
	 * @since 2.8
	 *
	 * @return array List of language keys to ignore.
	 */
	protected function exclude_language_keys() {
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
	 * @since 2.8
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info_options( $debug_info ) {
		$fields = array();
		foreach ( $this->model->options as $key => $value ) {
			if ( in_array( $key, $this->exclude_options_keys() ) ) {
				continue;
			}

			$fields[ $key ]['label'] = $key;

			if ( ! is_array( $value ) ) {
				if ( false === $value ) {
					$value = '0';
				}

				$fields[ $key ]['value'] = $value;
			} elseif ( empty( $value ) ) {
					$fields[ $key ]['value'] = '0';
			} else {
				switch ( $key ) {
					case 'post_types':
						$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_post_types() );
						break;
					case 'taxonomies':
						$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_taxonomies() );
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
						$fields[ $key ]['value'] = implode( ', ', $value );
						break;
				}
			}
		}

		$debug_info['pll_options'] = array(
			'label'  => __( 'Polylang Options', 'polylang' ),
			'fields' => $fields,
		);

		return $debug_info;
	}

	/**
	 * Add Polylang Languages settings to Site Health Informations tab.
	 *
	 * @since 2.8
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info_languages( $debug_info ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			$fields = array();

			foreach ( $language as $key => $value ) {
				if ( in_array( $key, $this->exclude_language_keys(), true ) ) {
					continue;
				}

				if ( empty( $value ) ) {
					$value = '0';
				}

				$fields[ $key ]['label'] = $key;
				$fields[ $key ]['value'] = $value;

				if ( 'term_group' === $key ) {
					$fields[ $key ]['label'] = 'order'; // Changed for readability but not translated as other keys are not.
				}
			}

			$debug_info[ 'pll_language_' . $language->slug ] = array(
				/* translators: placeholder is the language name */
				'label'  => sprintf( __( 'Language: %s', 'polylang' ), esc_html( $language->name ) ),
				/* translators: placeholder is the flag image */
				'description' => sprintf( __( 'Flag used in the language switcher: %s', 'polylang' ), $this->get_flag( $language ) ),
				'fields' => $fields,
			);
		}

		return $debug_info;
	}

	/**
	 * Returns the flag used in the language switcher.
	 *
	 * @since 2.8
	 *
	 * @param object $language Language object.
	 * @return string
	 */
	protected function get_flag( $language ) {
		if ( ! empty( $language->custom_flag ) ) {
			return $language->custom_flag;
		}

		if ( ! empty( $language->flag ) ) {
			return $language->flag;
		}

		return $flag = '<span>' . __( 'Undefined', 'polylang' ) . '</span>';
	}

	/**
	 * Add a Site Health test on homepage translation.
	 *
	 * @since 2.8
	 *
	 * @param array $tests Array with tests declaration data.
	 * @return array
	 */
	public function status_tests( $tests ) {
		// Add the test only if the homepage displays static page.
		if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
			$tests['direct']['pll_homepage'] = array(
				'label' => __( 'Homepage translated', 'polylang' ),
				'test'  => array( $this, 'homepage_test' ),
			);
		}
		return $tests;
	}

	/**
	 * Test if the home page is translated or not.
	 *
	 * @since 2.8
	 *
	 * @return array $result Array with test results.
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
		foreach ( $this->model->get_languages_list() as $language ) {
			if ( ! $language->page_on_front ) {
				$untranslated[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $this->links->get_new_post_translation_link( get_option( 'page_on_front' ), $language ) ),
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
