<?php

/**
 * reads and interprets the file wpml-config.xml
 * see http://wpml.org/documentation/support/language-configuration-files/
 * the language switcher configuration is not interpreted
 * the xml parser has been adapted from http://php.net/manual/en/function.xml-parse-into-struct.php#84261
 * many thanks to wickedfather at hotmail dot com
 *
 * @since 1.0
 */
class PLL_WPML_Config {
	static protected $instance; // for singleton
	protected $values, $index, $strings;
	public $tags;

	/**
	 * constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * access to the single instance of the class
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
	 * parses the wpml-config.xml file
	 *
	 * @since 1.0
	 *
	 * @param string wpml-config.xml file content
	 * @param string $context identifies where the file was found
	 */
	protected function xml_parse( $xml, $context ) {
		$parser = xml_parser_create();
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct( $parser, $xml, $this->values );
		xml_parser_free( $parser );

		$this->index = 0;
		$arr = $this->xml_parse_recursive();
		$arr = $arr['wpml-config'];

		$keys = array(
			array( 'custom-fields', 'custom-field' ),
			array( 'custom-types','custom-type' ),
			array( 'taxonomies','taxonomy' ),
			array( 'admin-texts','key' ),
		);

		foreach ( $keys as $k ) {
			if ( isset( $arr[ $k[0] ] ) ) {
				if ( ! isset( $arr[ $k[0] ][ $k[1] ][0] ) ) {
					$elem = $arr[ $k[0] ][ $k[1] ];
					unset( $arr[ $k[0] ][ $k[1] ] );
					$arr[ $k[0] ][ $k[1] ][0] = $elem;
				}

				$this->tags[ $k[0] ][ $context ] = $arr[ $k[0] ];
			}
		}
	}

	/**
	 * recursively parses the wpml-config.xml file
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function xml_parse_recursive() {
		$found = array();
		$tagCount = array();

		while ( isset( $this->values[ $this->index ] ) ) {
			$tag = $this->values[ $this->index ]['tag'];
			$type = $this->values[ $this->index ]['type'];
			if ( isset( $this->values[ $this->index ]['attributes'] ) ) {
				$attributes = $this->values[ $this->index ]['attributes'];
			}
			if ( isset( $this->values[ $this->index ]['value'] ) ) {
				$value = $this->values[ $this->index ]['value'];
			}

			$this->index++;

			if ( 'close' == $type ) {
				return $found;
			}

			if ( isset( $tagCount[ $tag ] ) ) {
				if ( 1 == $tagCount[ $tag ] ) {
					$found[ $tag ] = array( $found[ $tag ] );
				}

				$tagRef = &$found[ $tag ][ $tagCount[ $tag ] ];
				$tagCount[ $tag ]++;
			}
			else {
				$tagCount[ $tag ] = 1;
				$tagRef = &$found[ $tag ];
			}

			if ( 'open' == $type ) {
				$tagRef = $this->xml_parse_recursive();
				if ( isset( $attributes ) ) {
					$tagRef['attributes'] = $attributes;
				}
			}

			if ( 'complete' == $type ) {
				if ( isset( $attributes ) ) {
					$tagRef['attributes'] = $attributes;
					$tagRef = &$tagRef['value'];
				}
				if ( isset( $value ) ) {
					$tagRef = $value;
				}
			}
		}

		return $found;
	}

	/**
	 * finds the wpml-config.xml files to parse and setup filters
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->tags = array();

		// theme
		if ( file_exists( $file = ( $template = get_template_directory() ) .'/wpml-config.xml' ) ) {
			$this->xml_parse( file_get_contents( $file ), get_template() ); // FIXME fopen + fread + fclose quicker ?
		}

		// child theme
		if ( ( $stylesheet = get_stylesheet_directory() ) !== $template && file_exists( $file = $stylesheet . '/wpml-config.xml' ) ) {
			$this->xml_parse( file_get_contents( $file ), get_stylesheet() );
		}

		// plugins
		// don't forget sitewide active plugins thanks to Reactorshop http://wordpress.org/support/topic/polylang-and-yoast-seo-plugin/page/2?replies=38#post-4801829
		$plugins = ( is_multisite() && $sitewide_plugins = get_site_option( 'active_sitewide_plugins' ) ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
		$plugins = array_merge( $plugins, get_option( 'active_plugins' ) );

		foreach ( $plugins as $plugin ) {
			if ( file_exists( $file = WP_PLUGIN_DIR.'/'.dirname( $plugin ).'/wpml-config.xml' ) ) {
				$this->xml_parse( file_get_contents( $file ), dirname( $plugin ) );
			}
		}

		// custom
		if ( file_exists( $file = PLL_LOCAL_DIR.'/wpml-config.xml' ) ) {
			$this->xml_parse( file_get_contents( $file ), 'Polylang' );
		}

		if ( isset( $this->tags['custom-fields'] ) ) {
			add_filter( 'pll_copy_post_metas', array( &$this, 'copy_post_metas' ), 10, 2 );
		}

		if ( isset( $this->tags['custom-types'] ) ) {
			add_filter( 'pll_get_post_types', array( &$this, 'translate_types' ), 10, 2 );
		}

		if ( isset( $this->tags['taxonomies'] ) ) {
			add_filter( 'pll_get_taxonomies', array( &$this, 'translate_taxonomies' ), 10, 2 );
		}

		if ( ! isset( $this->tags['admin-texts'] ) ) {
			return;
		}

		// get a cleaner array for easy manipulation
		foreach ( $this->tags['admin-texts'] as $context => $arr ) {
			foreach ( $arr as $keys ) {
				$this->strings[ $context ] = $this->admin_texts_recursive( $keys );
			}
		}

		foreach ( $this->strings as $context => $options ) {
			foreach ( $options as $option_name => $value ) {
				if ( PLL_ADMIN ) { // backend
					$option = get_option( $option_name );
					if ( is_string( $option ) && 1 == $value ) {
						pll_register_string( $option_name, $option, $context );
					}
					elseif ( is_array( $option ) && is_array( $value ) ) {
						$this->register_string_recursive( $context, $value, $option ); // for a serialized option
					}
				}
				else {
					add_filter( 'option_'.$option_name, array( &$this, 'translate_strings' ) );
				}
			}
		}
	}

	/**
	 * arranges strings in a cleaner way
	 *
	 * @since 1.0
	 *
	 * @param array $keys
	 * @return array
	 */
	protected function admin_texts_recursive( $keys ) {
		if ( ! isset( $keys[0] ) ) {
			$elem = $keys;
			unset( $keys );
			$keys[0] = $elem;
		}
		foreach ( $keys as $key ) {
			$strings[ $key['attributes']['name'] ] = isset( $key['key'] ) ? $this->admin_texts_recursive( $key['key'] ) : 1;
		}

		return $strings;
	}

	/**
	 * recursively registers strings for a serialized option
	 *
	 * @since 1.0
	 *
	 * @param string $context the group in which the strings will be registered
	 * @param array $strings
	 * @param array $options
	 */
	protected function register_string_recursive( $context, $strings, $options ) {
		foreach ( $options as $name => $value ) {
			if ( isset( $strings[ $name ] ) ) {
				// allow numeric values to be translated
				// https://wordpress.org/support/topic/wpml-configxml-strings-skipped-when-numbers-ids
				if ( ( is_numeric( $value ) || is_string( $value ) ) && 1 == $strings[ $name ] ) {
					pll_register_string( $name, $value, $context );
				}
				elseif ( is_array( $value ) && is_array( $strings[ $name ] ) ) {
					$this->register_string_recursive( $context, $strings[ $name ], $value );
				}
			}
		}
	}

	/**
	 * adds custom fields to the list of metas to copy when creating a new translation
	 *
	 * @since 1.0
	 *
	 * @param array $metas the list of custom fields to copy or synchronize
	 * @param bool $sync true for sync, false for copy
	 * @return array the list of custom fields to copy or synchronize
	 */
	public function copy_post_metas( $metas, $sync ) {
		foreach ( $this->tags['custom-fields'] as $context ) {
			foreach ( $context['custom-field'] as $cf ) {
				// copy => copy and synchronize
				// translate => copy but don't synchronize
				// ignore => don't copy
				// see http://wordpress.org/support/topic/custom-field-values-override-other-translation-values?replies=8#post-4655563
				if ( 'copy' == $cf['attributes']['action'] || ( ! $sync && 'translate' == $cf['attributes']['action'] ) ) {
					$metas[] = $cf['value'];
				}
				else {
					$metas = array_diff( $metas,  array( $cf['value'] ) );
				}
			}
		}
		return $metas;
	}

	/**
	 * language and translation management for custom post types
	 *
	 * @since 1.0
	 *
	 * @param array $types list of post type names for which Polylang manages language and translations
	 * @param bool $hide true when displaying the list in Polylang settings
	 * @return array list of post type names for which Polylang manages language and translations
	 */
	public function translate_types( $types, $hide ) {
		foreach ( $this->tags['custom-types'] as $context ) {
			foreach ( $context['custom-type'] as $pt ) {
				if ( 1 == $pt['attributes']['translate'] && ! $hide ) {
					$types[ $pt['value'] ] = $pt['value'];
				}
				else {
					unset( $types[ $pt['value'] ] ); // the author decided what to do with the post type so don't allow the user to change this
				}
			}
		}
		return $types;
	}

	/**
	 * language and translation management for custom taxonomies
	 *
	 * @since 1.0
	 *
	 * @param array $taxonomies list of taxonomy names for which Polylang manages language and translations
	 * @param bool $hide true when displaying the list in Polylang settings
	 * @return array list of taxonomy names for which Polylang manages language and translations
	 */
	public function translate_taxonomies( $taxonomies, $hide ) {
		foreach ( $this->tags['taxonomies'] as $context ) {
			foreach ( $context['taxonomy'] as $tax ) {
				if ( 1 == $tax['attributes']['translate'] && ! $hide ) {
					$taxonomies[ $tax['value'] ] = $tax['value'];
				}
				else {
					unset( $taxonomies[ $tax['value'] ] ); // the author decided what to do with the taxonomy so don't allow the user to change this
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * translates the strings for an option
	 *
	 * @since 1.0
	 *
	 * @param array|string either a string to translate or a list of strings to translate
	 * @return array|string translated string(s)
	 */
	public function translate_strings( $value ) {
		if ( is_array( $value ) ) {
			$option = substr( current_filter(), 7 );
			foreach ( $this->strings as $context => $options ) {
				if ( array_key_exists( $option, $options ) ) {
					return $this->translate_strings_recursive( $options[ $option ], $value ); // for a serialized option
				}
			}
		}
		return pll__( $value );
	}

	/**
	 * recursively translates strings for a serialized option
	 *
	 * @since 1.0
	 *
	 * @param array $strings
	 * @param array|string $values either a string to translate or a list of strings to translate
	 * @return array|string translated string(s)
	 */
	protected function translate_strings_recursive( $strings, $values ) {
		foreach ( $values as $name => $value ) {
			if ( isset( $strings[ $name ] ) ) {
				// allow numeric values to be translated
				// https://wordpress.org/support/topic/wpml-configxml-strings-skipped-when-numbers-ids
				if ( ( is_numeric( $value ) || is_string( $value ) ) && 1 == $strings[ $name ] ) {
					$values[ $name ] = pll__( $value );
				}
				elseif ( is_array( $value ) && is_array( $strings[ $name ] ) ) {
					$values[ $name ] = $this->translate_strings_recursive( $strings[ $name ], $value );
				}
			}
		}
		return $values;
	}
} // class PLL_WPML_Config
