<?php
/**
 * @package Polylang
 */

/**
 * Reads and interprets the file wpml-config.xml
 * See http://wpml.org/documentation/support/language-configuration-files/
 * The language switcher configuration is not interpreted
 *
 * @since 1.0
 */
class PLL_WPML_Config {
	protected static $instance; // For singleton
	protected $xmls, $options;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {
		if ( extension_loaded( 'simplexml' ) ) {
			$this->init();
		}
	}

	/**
	 * Access to the single instance of the class
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Finds the wpml-config.xml files to parse and setup filters
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->xmls = array();

		// Plugins
		// Don't forget sitewide active plugins thanks to Reactorshop http://wordpress.org/support/topic/polylang-and-yoast-seo-plugin/page/2?replies=38#post-4801829
		$plugins = ( is_multisite() && $sitewide_plugins = get_site_option( 'active_sitewide_plugins' ) ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
		$plugins = array_merge( $plugins, get_option( 'active_plugins', array() ) );

		foreach ( $plugins as $plugin ) {
			if ( file_exists( $file = WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/wpml-config.xml' ) && false !== $xml = simplexml_load_file( $file ) ) {
				$this->xmls[ dirname( $plugin ) ] = $xml;
			}
		}

		// Theme
		if ( file_exists( $file = ( $template = get_template_directory() ) . '/wpml-config.xml' ) && false !== $xml = simplexml_load_file( $file ) ) {
			$this->xmls[ get_template() ] = $xml;
		}

		// Child theme
		if ( ( $stylesheet = get_stylesheet_directory() ) !== $template && file_exists( $file = $stylesheet . '/wpml-config.xml' ) && false !== $xml = simplexml_load_file( $file ) ) {
			$this->xmls[ get_stylesheet() ] = $xml;
		}

		// Custom
		if ( file_exists( $file = PLL_LOCAL_DIR . '/wpml-config.xml' ) && false !== $xml = simplexml_load_file( $file ) ) {
			$this->xmls['Polylang'] = $xml;
		}

		if ( ! empty( $this->xmls ) ) {
			add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ), 20, 2 );
			add_filter( 'pll_copy_term_metas', array( $this, 'copy_term_metas' ), 20, 2 );
			add_filter( 'pll_get_post_types', array( $this, 'translate_types' ), 10, 2 );
			add_filter( 'pll_get_taxonomies', array( $this, 'translate_taxonomies' ), 10, 2 );

			foreach ( $this->xmls as $context => $xml ) {
				foreach ( $xml->xpath( 'admin-texts/key' ) as $key ) {
					$attributes = $key->attributes();
					$name = (string) $attributes['name'];

					if ( false !== strpos( $name, '*' ) ) {
						$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';
						$names = preg_grep( $pattern, array_keys( wp_load_alloptions() ) );

						foreach ( $names as $_name ) {
							$this->register_or_translate_option( $context, $_name, $key );
						}
					} else {
						$this->register_or_translate_option( $context, $name, $key );
					}
				}
			}
		}
	}

	/**
	 * Adds custom fields to the list of metas to copy when creating a new translation
	 *
	 * @since 1.0
	 *
	 * @param array $metas the list of custom fields to copy or synchronize
	 * @param bool  $sync  true for sync, false for copy
	 * @return array the list of custom fields to copy or synchronize
	 */
	public function copy_post_metas( $metas, $sync ) {
		foreach ( $this->xmls as $xml ) {
			foreach ( $xml->xpath( 'custom-fields/custom-field' ) as $cf ) {
				$attributes = $cf->attributes();
				if ( 'copy' == $attributes['action'] || ( ! $sync && in_array( $attributes['action'], array( 'translate', 'copy-once' ) ) ) ) {
					$metas[] = (string) $cf;
				} else {
					$metas = array_diff( $metas, array( (string) $cf ) );
				}
			}
		}
		return $metas;
	}

	/**
	 * Adds term metas to the list of metas to copy when creating a new translation
	 *
	 * @since 2.6
	 *
	 * @param array $metas The list of term metas to copy or synchronize.
	 * @param bool  $sync  True for sync, false for copy.
	 * @return array The list of term metas to copy or synchronize.
	 */
	public function copy_term_metas( $metas, $sync ) {
		foreach ( $this->xmls as $xml ) {
			foreach ( $xml->xpath( 'custom-term-fields/custom-term-field' ) as $cf ) {
				$attributes = $cf->attributes();
				if ( 'copy' == $attributes['action'] || ( ! $sync && in_array( $attributes['action'], array( 'translate', 'copy-once' ) ) ) ) {
					$metas[] = (string) $cf;
				} else {
					$metas = array_diff( $metas, array( (string) $cf ) );
				}
			}
		}
		return $metas;
	}

	/**
	 * Language and translation management for custom post types
	 *
	 * @since 1.0
	 *
	 * @param array $types list of post type names for which Polylang manages language and translations
	 * @param bool  $hide  true when displaying the list in Polylang settings
	 * @return array list of post type names for which Polylang manages language and translations
	 */
	public function translate_types( $types, $hide ) {
		foreach ( $this->xmls as $xml ) {
			foreach ( $xml->xpath( 'custom-types/custom-type' ) as $pt ) {
				$attributes = $pt->attributes();
				if ( 1 == $attributes['translate'] && ! $hide ) {
					$types[ (string) $pt ] = (string) $pt;
				} else {
					unset( $types[ (string) $pt ] ); // The theme/plugin author decided what to do with the post type so don't allow the user to change this
				}
			}
		}
		return $types;
	}

	/**
	 * Language and translation management for custom taxonomies
	 *
	 * @since 1.0
	 *
	 * @param array $taxonomies list of taxonomy names for which Polylang manages language and translations
	 * @param bool  $hide       true when displaying the list in Polylang settings
	 * @return array list of taxonomy names for which Polylang manages language and translations
	 */
	public function translate_taxonomies( $taxonomies, $hide ) {
		foreach ( $this->xmls as $xml ) {
			foreach ( $xml->xpath( 'taxonomies/taxonomy' ) as $tax ) {
				$attributes = $tax->attributes();
				if ( 1 == $attributes['translate'] && ! $hide ) {
					$taxonomies[ (string) $tax ] = (string) $tax;
				} else {
					unset( $taxonomies[ (string) $tax ] ); // the theme/plugin author decided what to do with the taxonomy so don't allow the user to change this
				}
			}
		}
		return $taxonomies;
	}

	/**
	 * Registers or translates the strings for an option
	 *
	 * @since 2.8
	 *
	 * @param string $context The group in which the strings will be registered.
	 * @param string $name    Option name.
	 * @param object $key     XML node.
	 */
	protected function register_or_translate_option( $context, $name, $key ) {
		if ( PLL() instanceof PLL_Frontend ) {
			$this->options[ $name ] = $key;
			add_filter( 'option_' . $name, array( $this, 'translate_strings' ) );
		} else {
			$this->register_string_recursive( $context, $name, get_option( $name ), $key );
		}
	}

	/**
	 * Translates the strings for an option
	 *
	 * @since 1.0
	 *
	 * @param array|string $value Either a string to translate or a list of strings to translate
	 * @return array|string translated string(s)
	 */
	public function translate_strings( $value ) {
		$option = substr( current_filter(), 7 );
		return $this->translate_strings_recursive( $value, $this->options[ $option ] );
	}

	/**
	 * Recursively registers strings for a serialized option
	 *
	 * @since 1.0
	 * @since 2.7 Signature modified
	 *
	 * @param string $context The group in which the strings will be registered.
	 * @param string $option  Option name.
	 * @param array  $values  Option value.
	 * @param object $key     XML node.
	 */
	protected function register_string_recursive( $context, $option, $values, $key ) {
		if ( is_object( $values ) ) {
			$values = (array) $values;
		}

		$children = $key->children();

		if ( is_array( $values ) ) {
			if ( count( $children ) ) {
				foreach ( $children as $child ) {
					$attributes = $child->attributes();
					$name = (string) $attributes['name'];

					if ( isset( $values[ $name ] ) ) {
						$this->register_string_recursive( $context, $name, $values[ $name ], $child );
						continue;
					}

					$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';

					foreach ( $values as $n => $value ) {
						// The first case could be handled by the next one, but we avoid calls to preg_match here.
						if ( '*' === $name || ( false !== strpos( $name, '*' ) && preg_match( $pattern, $n ) ) ) {
							$this->register_string_recursive( $context, $n, $value, $child );
						}
					}
				}
			} else {
				foreach ( $values as $n => $value ) {
					// Parent key is a wildcard and no sub-key has been whitelisted.
					$this->register_string_recursive( $context, $n, $value, $key );
				}
			}
		} else {
			pll_register_string( $option, $values, $context, true );  // Multiline as in WPML.
		}
	}

	/**
	 * Recursively translates strings for a serialized option
	 *
	 * @since 1.0
	 *
	 * @param array|string $values Either a string to translate or a list of strings to translate.
	 * @param object       $key     XML node.
	 * @return array|string Translated string(s)
	 */
	protected function translate_strings_recursive( $values, $key ) {
		$children = $key->children();

		if ( is_array( $values ) || is_object( $values ) ) {
			if ( count( $children ) ) {
				foreach ( $children as $child ) {
					$attributes = $child->attributes();
					$name = (string) $attributes['name'];

					if ( is_array( $values ) && isset( $values[ $name ] ) ) {
						$values[ $name ] = $this->translate_strings_recursive( $values[ $name ], $child );
						continue;
					}

					if ( is_object( $values ) && isset( $values->$name ) ) {
						$values->$name = $this->translate_strings_recursive( $values->$name, $child );
						continue;
					}

					$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';

					foreach ( $values as $n => &$value ) {
						// The first case could be handled by the next one, but we avoid calls to preg_match here.
						if ( '*' === $name || ( false !== strpos( $name, '*' ) && preg_match( $pattern, $n ) ) ) {
							$value = $this->translate_strings_recursive( $value, $child );
						}
					}
				}
			} else {
				// Parent key is a wildcard and no sub-key has been whitelisted.
				foreach ( $values as &$value ) {
					$value = $this->translate_strings_recursive( $value, $key );
				}
			}
		} else {
			$values = pll__( $values );
		}

		return $values;
	}
}
