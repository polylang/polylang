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
	 * @var PLL_WPML_Config|null
	 */
	protected static $instance;

	/**
	 * The content of all read xml files.
	 *
	 * @var SimpleXMLElement[]|null
	 */
	protected $xmls;

	/**
	 * The list of xml files.
	 *
	 * @var string[]|null
	 */
	protected $files;

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
	 * @return PLL_WPML_Config
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
		$files = $this->get_files();

		if ( ! empty( $files ) ) {

			// Read all files.
			if ( extension_loaded( 'simplexml' ) ) {
				foreach ( $files as $context => $file ) {
					$xml = simplexml_load_file( $file );
					if ( false !== $xml ) {
						$this->xmls[ $context ] = $xml;
					}
				}
			}
		}

		if ( ! empty( $this->xmls ) ) {
			add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ), 20, 2 );
			add_filter( 'pll_copy_term_metas', array( $this, 'copy_term_metas' ), 20, 2 );
			add_filter( 'pll_get_post_types', array( $this, 'translate_types' ), 10, 2 );
			add_filter( 'pll_get_taxonomies', array( $this, 'translate_taxonomies' ), 10, 2 );
			add_filter( 'pll_blocks_xpath_rules', array( $this, 'translate_blocks' ) );
			add_filter( 'pll_blocks_rules_for_attributes', array( $this, 'translate_blocks_attributes' ) );

			foreach ( $this->xmls as $context => $xml ) {
				$keys = $xml->xpath( 'admin-texts/key' );
				if ( is_array( $keys ) ) {
					foreach ( $keys as $key ) {
						$attributes = $key->attributes();
						$name = (string) $attributes['name'];

						if ( false !== strpos( $name, '*' ) ) {
							$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';
							$names = preg_grep( $pattern, array_keys( wp_load_alloptions() ) );

							if ( is_array( $names ) ) {
								foreach ( $names as $_name ) {
									$this->register_or_translate_option( $context, $_name, $key );
								}
							}
						} else {
							$this->register_or_translate_option( $context, $name, $key );
						}
					}
				}
			}
		}
	}

	/**
	 * Get all wpml-config.xml files in plugins, theme, child theme and Polylang custom directory.
	 *
	 * @since 3.1
	 *
	 * @return array
	 */
	public function get_files() {

		if ( ! empty( $this->files ) ) {
			return $this->files;
		}

		$files = array();

		// Plugins
		// Don't forget sitewide active plugins thanks to Reactorshop http://wordpress.org/support/topic/polylang-and-yoast-seo-plugin/page/2?replies=38#post-4801829
		$plugins = ( is_multisite() && $sitewide_plugins = get_site_option( 'active_sitewide_plugins' ) ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
		$plugins = array_merge( $plugins, get_option( 'active_plugins', array() ) );

		foreach ( $plugins as $plugin ) {
			if ( file_exists( $file = WP_PLUGIN_DIR . '/' . dirname( $plugin ) . '/wpml-config.xml' ) ) {
				$files[ dirname( $plugin ) ] = $file;
			}
		}

		// Theme
		if ( file_exists( $file = ( $template = get_template_directory() ) . '/wpml-config.xml' ) ) {
			$files[ get_template() ] = $file;
		}

		// Child theme
		if ( ( $stylesheet = get_stylesheet_directory() ) !== $template && file_exists( $file = $stylesheet . '/wpml-config.xml' ) ) {
			$files[ get_stylesheet() ] = $file;
		}

		// Custom
		if ( file_exists( $file = PLL_LOCAL_DIR . '/wpml-config.xml' ) ) {
			$files['Polylang'] = $file;
		}

		$this->files = $files;

		return $files;
	}

	/**
	 * Adds custom fields to the list of metas to copy when creating a new translation.
	 *
	 * @since 1.0
	 *
	 * @param string[] $metas The list of custom fields to copy or synchronize.
	 * @param bool     $sync  True for sync, false for copy.
	 * @return string[] The list of custom fields to copy or synchronize.
	 */
	public function copy_post_metas( $metas, $sync ) {
		foreach ( $this->xmls as $xml ) {
			$cfs = $xml->xpath( 'custom-fields/custom-field' );
			if ( is_array( $cfs ) ) {
				foreach ( $cfs as $cf ) {
					$attributes = $cf->attributes();
					if ( 'copy' == $attributes['action'] || ( ! $sync && in_array( $attributes['action'], array( 'translate', 'copy-once' ) ) ) ) {
						$metas[] = (string) $cf;
					} else {
						$metas = array_diff( $metas, array( (string) $cf ) );
					}
				}
			}
		}
		return $metas;
	}

	/**
	 * Adds term metas to the list of metas to copy when creating a new translation.
	 *
	 * @since 2.6
	 *
	 * @param string[] $metas The list of term metas to copy or synchronize.
	 * @param bool     $sync  True for sync, false for copy.
	 * @return string[] The list of term metas to copy or synchronize.
	 */
	public function copy_term_metas( $metas, $sync ) {
		foreach ( $this->xmls as $xml ) {
			$cfs = $xml->xpath( 'custom-term-fields/custom-term-field' );
			if ( is_array( $cfs ) ) {
				foreach ( $cfs as $cf ) {
					$attributes = $cf->attributes();
					if ( 'copy' == $attributes['action'] || ( ! $sync && in_array( $attributes['action'], array( 'translate', 'copy-once' ) ) ) ) {
						$metas[] = (string) $cf;
					} else {
						$metas = array_diff( $metas, array( (string) $cf ) );
					}
				}
			}
		}
		return $metas;
	}

	/**
	 * Language and translation management for custom post types.
	 *
	 * @since 1.0
	 *
	 * @param string[] $types The list of post type names for which Polylang manages language and translations.
	 * @param bool     $hide  True when displaying the list in Polylang settings.
	 * @return string[] The list of post type names for which Polylang manages language and translations.
	 */
	public function translate_types( $types, $hide ) {
		foreach ( $this->xmls as $xml ) {
			$pts = $xml->xpath( 'custom-types/custom-type' );
			if ( is_array( $pts ) ) {
				foreach ( $pts as $pt ) {
					$attributes = $pt->attributes();
					if ( 1 == $attributes['translate'] && ! $hide ) {
						$types[ (string) $pt ] = (string) $pt;
					} else {
						unset( $types[ (string) $pt ] ); // The theme/plugin author decided what to do with the post type so don't allow the user to change this
					}
				}
			}
		}
		return $types;
	}

	/**
	 * Language and translation management for custom taxonomies.
	 *
	 * @since 1.0
	 *
	 * @param string[] $taxonomies The list of taxonomy names for which Polylang manages language and translations.
	 * @param bool     $hide       True when displaying the list in Polylang settings.
	 * @return string[] The list of taxonomy names for which Polylang manages language and translations.
	 */
	public function translate_taxonomies( $taxonomies, $hide ) {
		foreach ( $this->xmls as $xml ) {
			$taxos = $xml->xpath( 'taxonomies/taxonomy' );
			if ( is_array( $taxos ) ) {
				foreach ( $taxos as $tax ) {
					$attributes = $tax->attributes();
					if ( 1 == $attributes['translate'] && ! $hide ) {
						$taxonomies[ (string) $tax ] = (string) $tax;
					} else {
						unset( $taxonomies[ (string) $tax ] ); // the theme/plugin author decided what to do with the taxonomy so don't allow the user to change this
					}
				}
			}
		}
		return $taxonomies;
	}

	/**
	 * Translation management for strings in blocks content.
	 *
	 * @since 3.3
	 *
	 * @param array[] $parsing_rules Rules as Xpath expressions to evaluate in the blocks content.
	 * @return array[] Rules completed with ones from wpml-config file.
	 *
	 * @phpstan-param array<string,array<string>> $parsing_rules
	 * @phpstan-return array<string,array<string>> $parsing_rules
	 */
	public function translate_blocks( $parsing_rules ) {
		return $this->extract_blocks_parsing_rules( $parsing_rules, 'xpath' );
	}

	/**
	 * Translation management for blocks attributes.
	 *
	 * @since 3.3
	 *
	 * @param array[] $parsing_rules Rules for block attributes to translate.
	 * @return array[] Rules completed with ones from wpml-config file.
	 *
	 * @phpstan-param array<string,array<string>> $parsing_rules
	 * @phpstan-return array<string,array<string>>
	 */
	public function translate_blocks_attributes( $parsing_rules ) {
		return $this->extract_blocks_parsing_rules( $parsing_rules, 'key', true );
	}

	/**
	 * Extract what kind of string to translate for blocks from WPML config file.
	 *
	 * @since 3.3
	 *
	 * @param array[] $parsing_rules         Rules to complete with ones from wpml-config file..
	 * @param string  $child_tag             Tag name to extract.
	 * @param boolean $is_in_child_attribute Extract tag value in attribute or not. Default false.
	 * @param string  $child_attribute_name  Attribute name where to extract the value. Default 'name'. Used if $is_in_child_attribute is set to true.
	 * @return array[] Rules completed with ones from wpml-config file.
	 */
	protected function extract_blocks_parsing_rules( $parsing_rules, $child_tag, $is_in_child_attribute = false, $child_attribute_name = 'name' ) {
		foreach ( $this->xmls as $xml ) {
			$blocks = $xml->xpath( 'gutenberg-blocks/gutenberg-block' );
			if ( is_array( $blocks ) ) {
				foreach ( $blocks as $block ) {
					$attributes = $block->attributes();
					if ( 1 == $attributes['translate'] ) {
						$block_name = (string) $attributes['type'];
						foreach ( $block->children() as $child ) {
							if ( $child_tag === $child->getName() ) {
								if ( $is_in_child_attribute ) {
									$child_attributes = $child->attributes();
									$rules            = (string) $child_attributes[ $child_attribute_name ];
								} else {
									$rules = (string) $child;
								}
								if ( ! isset( $parsing_rules[ $block_name ] ) || ! in_array( $rules, $parsing_rules[ $block_name ] ) ) {
									$parsing_rules[ $block_name ][] = $rules;
								}
							}
						}
					}
				}
			}
		}
		return $parsing_rules;
	}

	/**
	 * Registers or translates the strings for an option
	 *
	 * @since 2.8
	 *
	 * @param string           $context The group in which the strings will be registered.
	 * @param string           $name    Option name.
	 * @param SimpleXMLElement $key     XML node.
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
	 * @param SimpleXMLElement $key XML node.
	 * @param array            $arr Array of option keys to translate.
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
