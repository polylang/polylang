<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Admin_Site_Health to add debug info in WP Site Health.
 *
 * @see https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/ since WordPress 5.2
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
	 * A reference to the PLL_Admin_Static_Pages instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Admin_Static_Pages
	 */
	protected $static_pages;

	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->static_pages = &$polylang->static_pages;

		// Information tab.
		add_filter( 'debug_information', array( $this, 'info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_languages' ), 15 );
		if ( $this->warning_exists() ) {
			add_filter( 'debug_information', array( $this, 'info_warning' ), 15 );
		}

		// Tests Tab.
		add_filter( 'site_status_tests', array( $this, 'status_tests' ) );
	}

	/**
	 * Returns a list of keys to exclude from the site health information.
	 *
	 * @since 2.8
	 *
	 * @return string[] List of option keys to ignore.
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
	 * @return string[] List of language keys to ignore.
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
	 * Formats an array to display in options information.
	 *
	 * @since 2.8
	 *
	 * @param array $array An array of formatted data.
	 * @return string
	 */
	protected function format_array( $array ) {
		array_walk(
			$array,
			function ( &$value, $key ) {
				if ( is_array( $value ) ) {
					$ids = implode( ' , ', $value );
					$value = "$key => $ids";
				} else {
					$value = "$key => $value";
				}
			}
		);

		return implode( ' | ', $array );
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

			if ( ! is_array( $value ) ) {
				if ( empty( $value ) ) {
					$value = '0';
				}

				$fields[ $key ]['label'] = $key;
				$fields[ $key ]['value'] = $value;
			} elseif ( empty( $value ) ) {
				$fields[ $key ]['label'] = $key;
				$fields[ $key ]['value'] = '0';
			} else {
				switch ( $key ) {
					case 'post_types':
						$fields[ $key ]['label'] = $key;
						$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_post_types() );
						break;
					case 'taxonomies':
						$fields[ $key ]['label'] = $key;
						$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_taxonomies() );
						break;
					case 'domains':
						$fields[ $key ]['label'] = $key;
						$fields[ $key ]['value'] = $this->format_array( $value );
						break;
					case 'nav_menus':
						$current_theme = get_stylesheet();
						if ( isset( $value[ $current_theme ] ) ) {
							foreach ( $value[ $current_theme ] as $location => $lang ) {
								/* translators: placeholder is the menu location name */
								$fields[ $location ]['label'] = sprintf( 'menu: %s', $location );
								$fields[ $location ]['value'] = $this->format_array( $lang );
							}
						}
						break;
					case 'media':
						foreach ( $value as $sub_key => $sub_value ) {
							$fields[ "$key-$sub_key" ]['label'] = "$key $sub_key";
							$fields[ "$key-$sub_key" ]['value'] = $sub_value;
						}
						break;
					default:
						$fields[ $key ]['label'] = $key;
						$fields[ $key ]['value'] = implode( ', ', $value );
						break;
				}
			}
		}
		$debug_info['pll_options'] = array(
			/* translators: placeholder is the plugin name */
			'label'  => sprintf( esc_html__( '%s Options', 'polylang' ), POLYLANG ),
			'fields' => $fields,
		);

		return $debug_info;
	}

	/**
	 * Get an array with post_type as key and post IDs as value
	 *
	 * @since   3.0 initial.
	 * @since   3.1 Use internal method to get languages list.
	 *
	 * @param int $limit Nb of post max to show per post type.
	 *
	 * @return int[][] Array containing an array of post IDs
	 */
	public function get_post_ids_without_lang( $limit = 5 ) {
		$posts        = array();
		$language_ids = $this->get_languages_list( array( 'fields' => 'term_id' ) );

		foreach ( $this->model->get_translated_post_types() as $post_type ) {
			$posts_ids_with_no_language = get_posts(
				array(
					'numberposts' => $limit,
					'post_type'   => $post_type,
					'post_status' => 'any',
					'tax_query'   => array(
						array(
							'taxonomy' => 'language',
							'terms'    => $language_ids,
							'operator' => 'NOT IN',
						),
					),
				)
			);

			if ( ! empty( $posts_ids_with_no_language ) ) {
				foreach ( $posts_ids_with_no_language as $untranslated ) {
					$posts[ $untranslated->post_type ][] = $untranslated->ID;
				}
			}
		}

		return $posts;
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
				'label'  => sprintf( esc_html__( 'Language: %s', 'polylang' ), esc_html( $language->name ) ),
				/* translators: placeholder is the flag image */
				'description' => sprintf( esc_html__( 'Flag used in the language switcher: %s', 'polylang' ), $this->get_flag( $language ) ),
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
	 * @param PLL_Language $language Language object.
	 * @return string
	 */
	protected function get_flag( $language ) {
		$flag = $language->get_display_flag();
		return empty( $flag ) ? '<span>' . esc_html__( 'Undefined', 'polylang' ) . '</span>' : $flag;
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
				'label' => esc_html__( 'Homepage translated', 'polylang' ),
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
			'label'       => esc_html__( 'All languages have a translated homepage', 'polylang' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => POLYLANG,
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'It is mandatory to translate the static front page in all languages.', 'polylang' )
			),
			'actions'     => '',
			'test'        => 'pll_homepage',
		);

		$message = $this->static_pages->get_must_translate_message();

		if ( ! empty( $message ) ) {
			$result['status']      = 'critical';
			$result['label']       = esc_html__( 'The homepage is not translated in all languages', 'polylang' );
			$result['description'] = sprintf( '<p>%s</p>', $message );
		}
		return $result;
	}

	/**
	 * Add Polylang Warnings to Site Health Informations tab.
	 *
	 * @since 3.1
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info_warning( $debug_info ) {
		$post_no_lang = $this->get_post_ids_without_lang();
		$fields       = array();

		if ( ! empty( $post_no_lang ) ) {
			$fields['post-no-lang']['label'] = __( 'Posts without language', 'polylang' );
			$fields['post-no-lang']['value'] = $this->format_array( $post_no_lang );
		}

		$debug_info['pll_warnings'] = array(
			/* translators: placeholder is the plugin name */
			'label'  => sprintf( esc_html__( '%s Warnings', 'polylang' ), POLYLANG ),
			'fields' => $fields,
		);

		return $debug_info;
	}

	/**
	 * Check if a Polylang warning exists.
	 *
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function warning_exists() {
		$post_no_lang = $this->get_post_ids_without_lang();
		if ( ! empty( $post_no_lang ) ) {
			return true;
		}
		return false;
	}
}
