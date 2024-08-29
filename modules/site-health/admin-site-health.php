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
	 * @var PLL_Admin_Static_Pages|null
	 */
	protected $static_pages;

	/**
	 * PLL_Admin_Site_Health constructor.
	 *
	 * @since 2.8
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->static_pages = &$polylang->static_pages;

		// Information tab.
		add_filter( 'debug_information', array( $this, 'info_options' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_languages' ), 15 );
		add_filter( 'debug_information', array( $this, 'info' ), 15 );
		add_filter( 'debug_information', array( $this, 'info_translations' ), 0 );

		// Tests Tab.
		add_filter( 'site_status_tests', array( $this, 'status_tests' ) );
		add_filter( 'site_status_test_php_modules', array( $this, 'site_status_test_php_modules' ) ); // Require simplexml in Site health.
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
	 * Transforms the option value to readable human sentence.
	 *
	 * @since 3.3
	 *
	 * @param string $key   Option name.
	 * @param mixed  $value Option value.
	 * @return mixed Option value.
	 */
	public function format_value( $key, $value ) {
		switch ( $key ) {
			case 'browser':
				if ( ! $value ) {
					$value = '0: ' . esc_html__( 'Detect browser language deactivated', 'polylang' );
					break;
				}
				$value = '1: ' . esc_html__( 'Detect browser language activated', 'polylang' );
				break;
			case 'rewrite':
				if ( $value ) {
					$value = '1: ' . esc_html__( 'Remove /language/ in pretty permalinks', 'polylang' );
					break;
				}
				$value = '0: ' . esc_html__( 'Keep /language/ in pretty permalinks', 'polylang' );
				break;
			case 'hide_default':
				if ( $value ) {
					$value = '1: ' . esc_html__( 'Hide URL language information for default language', 'polylang' );
					break;
				}
				$value = '0: ' . esc_html__( 'Display URL language information for default language', 'polylang' );
				break;
			case 'force_lang':
				switch ( $value ) {
					case '0':
						$value = '0: ' . esc_html__( 'The language is set from content', 'polylang' );
						break;
					case '1':
						$value = '1: ' . esc_html__( 'The language is set from the directory name in pretty permalinks', 'polylang' );
						break;
					case '2':
						$value = '2: ' . esc_html__( 'The language is set from the subdomain name in pretty permalinks', 'polylang' );
						break;
					case '3':
						$value = '3: ' . esc_html__( 'The language is set from different domains', 'polylang' );
						break;
				}
				break;
			case 'redirect_lang':
				if ( $value ) {
					$value = '1: ' . esc_html__( 'The front page URL contains the language code instead of the page name or page id', 'polylang' );
					break;
				}
				$value = '0: ' . esc_html__( 'The front page URL contains the page name or page id instead of the language code', 'polylang' );

				break;
			case 'media_support':
				if ( ! $value ) {
					$value = '0: ' . esc_html__( 'The media are not translated', 'polylang' );
					break;
				}
				$value = '1: ' . esc_html__( 'The media are translated', 'polylang' );
				break;

			case 'sync':
				if ( empty( $value ) ) {
					$value = '0: ' . esc_html__( 'Synchronization disabled', 'polylang' );
				}
				break;
		}

		return $value;
	}

	/**
	 * Add Polylang Options to Site Health Information tab.
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

			$value = $this->format_value( $key, $value );

			switch ( $key ) {
				case 'domains':
					if ( 3 === $this->model->options['force_lang'] ) {
						$value = is_array( $value ) ? $value : array();
						$value = $this->format_array( $value );

						$fields[ $key ]['label'] = $key;
						$fields[ $key ]['value'] = $value;
					}
					break;

				case 'nav_menus':
					$current_theme = get_stylesheet();
					if ( is_array( $value ) && isset( $value[ $current_theme ] ) ) {
						foreach ( $value[ $current_theme ] as $location => $lang ) {
							$lang = is_array( $lang ) ? $lang : array();

							$fields[ $location ]['label'] = sprintf( 'menu: %s', $location );
							$fields[ $location ]['value'] = $this->format_array( $lang );
						}
					}
					break;

				case 'media':
					$value = is_array( $value ) ? $value : array();
					foreach ( $value as $sub_key => $sub_value ) {
						$fields[ "$key-$sub_key" ]['label'] = "$key $sub_key";
						$fields[ "$key-$sub_key" ]['value'] = $sub_value;
					}
					break;

				case 'post_types':
					$fields[ $key ]['label'] = $key;
					$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_post_types() );
					break;

				case 'taxonomies':
					$fields[ $key ]['label'] = $key;
					$fields[ $key ]['value'] = implode( ', ', $this->model->get_translated_taxonomies() );
					break;

				default:
					$fields[ $key ]['label'] = $key;
					$fields[ $key ]['value'] = empty( $value ) ? '0' : $value;
					break;
			}
		}

		$debug_info['pll_options'] = array(
			/* translators: placeholder is the plugin name */
			'label'  => sprintf( __( '%s options', 'polylang' ), POLYLANG ),
			'fields' => $fields,
		);

		return $debug_info;
	}

	/**
	 * Adds Polylang Languages settings to Site Health Information tab.
	 *
	 * @since 2.8
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info_languages( $debug_info ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			$fields = array();

			foreach ( $language->to_array() as $key => $value ) {
				if ( in_array( $key, $this->exclude_language_keys(), true ) ) {
					continue;
				}

				if ( empty( $value ) ) {
					$value = '0';
				}

				$fields[ $key ]['label'] = $key;

				if ( 'term_props' === $key && is_array( $value ) ) {
					$fields[ $key ]['value'] = $this->get_info_term_props( $value );
				} else {
					$fields[ $key ]['value'] = $value;
				}

				if ( 'term_group' === $key ) {
					$fields[ $key ]['label'] = 'order'; // Changed for readability but not translated as other keys are not.
				}
			}

			$debug_info[ 'pll_language_' . $language->slug ] = array(
				/* translators: placeholder is the language name */
				'label'  => sprintf( __( 'Language: %s', 'polylang' ), $language->name ),
				/* translators: placeholder is the flag image */
				'description' => sprintf( esc_html__( 'Flag used in the language switcher: %s', 'polylang' ), $this->get_flag( $language ) ),
				'fields' => $fields,
			);
		}

		return $debug_info;
	}

	/**
	 * Adds term props data to the info languages array.
	 *
	 * @since 3.4
	 *
	 * @param array $value The term props data.
	 * @return array The term props data formatted for the info languages tab.
	 */
	protected function get_info_term_props( $value ) {
		$return_value = array();

		foreach ( $value as $language_taxonomy => $item ) {
			$language_taxonomy_array = array_fill( 0, count( $item ), $language_taxonomy );

			$keys_with_language_taxonomy = array_map(
				function ( $key, $language_taxonomy ) {
					return "{$language_taxonomy}/{$key}";
				},
				array_keys( $item ),
				$language_taxonomy_array
			);

			$value = array_combine( $keys_with_language_taxonomy, $item );
			if ( is_array( $value ) ) {
				$return_value = array_merge( $return_value, $value );
			}
		}
		return $return_value;
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
	 * Add Polylang Warnings to Site Health Information tab.
	 *
	 * @since 3.1
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info( $debug_info ) {
		$fields = array();

		// Add Post Types without languages.
		$posts_no_lang = $this->get_post_ids_without_lang();

		if ( ! empty( $posts_no_lang ) ) {
			$fields['post-no-lang']['label'] = __( 'Posts without language', 'polylang' );
			$fields['post-no-lang']['value'] = $this->format_array( $posts_no_lang );
		}

		$terms_no_lang = $this->get_term_ids_without_lang();

		if ( ! empty( $terms_no_lang ) ) {
			$fields['term-no-lang']['label'] = __( 'Terms without language', 'polylang' );
			$fields['term-no-lang']['value'] = $this->format_array( $terms_no_lang );
		}

		// Add WPML files.
		$wpml_files = PLL_WPML_Config::instance()->get_files();
		if ( ! empty( $wpml_files ) ) {
			$fields['wpml']['label'] = 'wpml-config.xml files';
			$fields['wpml']['value'] = $wpml_files;

			if ( ! extension_loaded( 'simplexml' ) ) {
				$fields['simplexml']['label'] = __( 'PHP SimpleXML extension', 'polylang' );
				$fields['simplexml']['value'] = __( 'Not loaded. Contact your host provider.', 'polylang' );
			}
		}

		// Create the section.
		if ( ! empty( $fields ) ) {
			$debug_info['pll_warnings'] = array(
				/* translators: placeholder is the plugin name */
				'label'  => sprintf( __( '%s information', 'polylang' ), POLYLANG ),
				'fields' => $fields,
			);
		}

		return $debug_info;
	}

	/**
	 * Display the list of available translation for Core, plugins and theme.
	 *
	 * @since 3.7
	 *
	 * @param array $debug_info The debug information to be added to the core information page.
	 * @return array
	 */
	public function info_translations( $debug_info ) {
		// Translation updates available.
		$translation_updates = wp_get_translation_updates();
		$fields = array();

			$translation_updates_list = $this->get_translations_update_list( $translation_updates );
		if ( ! empty( $translation_updates_list ) ) {
			foreach ( $translation_updates_list as $type => $values ) {
				$type_label = __( 'WordPress', 'polylang' );

				if ( 'plugin' === $type ) {
					$type = 'plugins';
					$type_label = __( 'Plugins', 'polylang' );
				}
				if ( 'theme' === $type ) {
					$type = 'themes';
					$type_label = __( 'Themes', 'polylang' );

				}
				$fields[ 'translation_' . $type ]['label'] = '=== ' . $type_label . ' ===';
				$fields[ 'translation_' . $type ]['value'] = ' '; // needed to avoid a "undefined" when copy to clipboard. Empty string is skipped.
				foreach ( $values as $name => $value ) {
					$fields[ 'translation_' . $name ]['label'] = $name;
					$is_locales_installed = $this->is_wp_language_installed( $value, $type, $name );
					if ( ! $is_locales_installed ) {
						$locales = implode( ', ', $value );
						$fields[ 'translation_' . $name ]['value'] = sprintf(
						/* translators: the placeholder is a WordPress locale */
							__( 'A translation is missing for %s .', 'polylang' ),
							$locales
						);
					} else {
						$locales = implode( ', ', $value );

						$fields[ 'translation_' . $name ]['value'] = sprintf(
						/* translators: the placeholder is a WordPress locale */
							__( 'A translation is updatable for %s .', 'polylang' ),
							$locales
						);
					}
				}
			}
		}

		// Create the section.
		if ( ! empty( $fields ) ) {
			$debug_info['pll_translation'] = array(
				/* translators: placeholder is the plugin name */
				'label'  => sprintf( __( 'Translations information', 'polylang' ), POLYLANG ),
				'fields' => $fields,
			);
		}

		return $debug_info;
	}

	/**
	 * Is the language pack already installed ?
	 *
	 * @since 3.7
	 *
	 * @param array  $locales array of WordPress locales.
	 * @param string $type type of update (may be core, plugin or theme and sometimes core, plugins or themes ).
	 * @param string $name name of the element (plugin/theme) currently updated. In case of Core update, it's "default"
	 * @param array|bool  $already_installed array of already installed language packs.
	 * @param array  $update_list array of wp.org translation pack update
	 * @return boolean|array
	 */
	public function is_wp_language_installed( $locales, $type, $name, $already_installed = array(), $update_list = array() ) {

		$installed_translations = wp_get_installed_translations( $type );
		foreach ( $locales as $locale ) {
			if ( $update_list['plugin'][ $name ][ $locale ] ) {
				break;
			}
			if ( ! empty( $installed_translations[ $name ] ) && $installed_translations[ $name ][ $locale ] ) {
				$already_installed[ $type ][ $name ][ $locale ] = $locale;
			}
		}

		if ( ! empty( $already_installed ) ) {
			return $already_installed;
		}

		return false;
	}

	/**
	 * Returns all available translation updates.
	 *
	 * @since 3.7
	 *
	 * @param array $updates The available updates.
	 * @return array The available translation updates formatted for the Site Health Report.
	 */
	public function get_translations_update_list( array $updates ): array {
		$pll_locales = $this->model->get_languages_list();
		$locales     = array();
		foreach ( $pll_locales as $locale ) {
			$locales[ $locale->locale ] = $locale->locale;
			if ( ! empty( $locale->fallbacks ) ) {
				foreach ( $locale->fallbacks as $fallback ) {
					$locales[ $fallback ] = $fallback;
				}
			}
		}
		$update_list = array();

		foreach ( $updates as $update ) {
			if ( in_array( $update->language, $locales ) ) {
				$update_list[ $update->type ][ $update->slug ][ $update->language ] = $update->language;
			}
		}

		// Premiums
		$activated_plugins = get_option( 'active_plugins' );
		if ( ! empty( $activated_plugins ) ) {
			foreach ( $activated_plugins as $activated_plugin ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $activated_plugin );

				$already_installed = $this->is_wp_language_installed( $locales, 'plugins', strtolower( $plugin_data['Name'] ), $already_installed, $update_list );
				foreach ( $pll_locales as $locale ) {

					if ( ! $update_list['plugin'][ strtolower( $plugin_data['Name'] ) ][ $locale->get_locale() ] ) { // Polylang/Polylang Pro case maybe others.
						$po_path     = trailingslashit( WP_PLUGIN_DIR ) . dirname( $activated_plugin ) . trailingslashit( $plugin_data['DomainPath'] ) . $plugin_data['TextDomain'] . '-' . $locale->get_locale() . '.po';
						$file_exists = file_exists( $po_path );
						if ( ! $file_exists ) {
							$update_list['plugin'][ strtolower( $plugin_data['Name'] ) ][ $locale->get_locale() ] = $locale->get_locale();
						}
					}
				}
			}
		}

		return array_filter( $update_list );
	}

	/**
	 * Get an array with post_type as key and post ids as value.
	 *
	 * @since 3.1
	 *
	 * @param int $limit Max number of posts to show per post type. `-1` to return all of them. Default is 5.
	 * @return int[][] Array containing an array of post ids.
	 *
	 * @phpstan-param -1|positive-int $limit
	 */
	public function get_post_ids_without_lang( $limit = 5 ) {
		$posts = array();

		foreach ( $this->model->get_translated_post_types() as $post_type ) {
			$post_ids_with_no_language = $this->model->get_posts_with_no_lang( $post_type, $limit );

			if ( ! empty( $post_ids_with_no_language ) ) {
				foreach ( $post_ids_with_no_language as $id ) {
					$posts[ $post_type ][] = $id;
				}
			}
		}

		return $posts;
	}

	/**
	 * Get an array with taxonomy as key and term ids as value.
	 *
	 * @since 3.1
	 *
	 * @param int $limit Max number of terms to show per post type. `-1` to return all of them. Default is 5.
	 * @return int[][] Array containing an array of term ids.
	 *
	 * @phpstan-param -1|positive-int $limit
	 */
	public function get_term_ids_without_lang( $limit = 5 ) {
		$terms = array();

		foreach ( $this->model->get_translated_taxonomies() as $taxonomy ) {
			$term_ids_with_no_language = $this->model->get_terms_with_no_lang( $taxonomy, $limit );

			if ( ! empty( $term_ids_with_no_language ) ) {
				foreach ( $term_ids_with_no_language as $id ) {
					$terms[ $taxonomy ][] = $id;
				}
			}
		}

		return $terms;
	}

	/**
	 * Requires the simplexml PHP module when a wpml-config.xml has been found.
	 *
	 * @since 3.1
	 * @since 3.2 Moved from PLL_WPML_Config
	 *
	 * @param array $modules An associative array of modules to test for.
	 * @return array
	 */
	public function site_status_test_php_modules( $modules ) {
		$files = PLL_WPML_Config::instance()->get_files();
		if ( ! empty( $files ) ) {
			$modules['simplexml'] = array(
				'extension' => 'simplexml',
				'required'  => true,
			);
		}
		return $modules;
	}
}
