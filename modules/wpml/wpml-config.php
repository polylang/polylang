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
	/**
	 * Singleton instance
	 *
	 * @var PLL_WPML_Config
	 */
	protected static $instance;

	/**
	 * The content of all read xml files.
	 *
	 * @var SimpleXMLElement[]
	 */
	protected $xmls;

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
	 *
	 * @return void
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
	 * @return void
	 */
	protected function register_or_translate_option( $context, $name, $key ) {
		$option_keys = $this->xml_to_array( $key );
		new PLL_Translate_Option( $name, reset( $option_keys ), array( 'context' => $context ) );
	}

	/**
	 * Recursively transforms xml nodes to an array, ready for PLL_Translate_Option.
	 *
	 * @since 2.9
	 *
	 * @param object $key XML node.
	 * @param array  $arr Array of option keys to translate.
	 * @return array
	 */
	protected function xml_to_array( $key, &$arr = array() ) {
		$attributes = $key->attributes();
		$name = (string) $attributes['name'];
		$children = $key->children();

		if ( count( $children ) ) {
			foreach ( $children as $child ) {
				$arr[ $name ] = $this->xml_to_array( $child, $arr[ $name ] );
			}
		} else {
			$arr[ $name ] = true; // Multiline as in WPML.
		}
		return $arr;
	}
}
