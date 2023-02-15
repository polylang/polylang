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
	 * @var SimpleXMLElement[]
	 */
	protected $xmls = array();

	/**
	 * The list of xml file paths.
	 *
	 * @var string[]|null
	 *
	 * @phpstan-var array<string, string>|null
	 */
	protected $files;

	/**
	 * List of rules to extract strings to translate from blocks.
	 *
	 * @var string[][][]|null
	 */
	protected $parsing_rules = null;

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
		$files      = $this->get_files();

		if ( empty( $files ) ) {
			return;
		}

		if ( ! extension_loaded( 'simplexml' ) ) {
			return;
		}

		// Read all files.
		foreach ( $files as $context => $file ) {
			$xml = simplexml_load_file( $file );
			if ( false !== $xml ) {
				$this->xmls[ $context ] = $xml;
			}
		}

		if ( empty( $this->xmls ) ) {
			return;
		}

		add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ), 20, 2 );
		add_filter( 'pll_copy_term_metas', array( $this, 'copy_term_metas' ), 20, 2 );
		add_filter( 'pll_get_post_types', array( $this, 'translate_types' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( $this, 'translate_taxonomies' ), 10, 2 );
		add_filter( 'pll_blocks_xpath_rules', array( $this, 'translate_blocks' ) );
		add_filter( 'pll_blocks_rules_for_attributes', array( $this, 'translate_blocks_attributes' ) );

		// Export.
		add_filter( 'pll_post_metas_to_export', array( $this, 'post_metas_to_export' ) );
		add_filter( 'pll_term_metas_to_export', array( $this, 'term_metas_to_export' ) );

		foreach ( $this->xmls as $context => $xml ) {
			$keys = $xml->xpath( 'admin-texts/key' );

			if ( ! is_array( $keys ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				$name = $this->get_field_attribute( $key, 'name' );

				if ( false === strpos( $name, '*' ) ) {
					$this->register_or_translate_option( $context, $name, $key );
					continue;
				}

				$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';
				$names = preg_grep( $pattern, array_keys( wp_load_alloptions() ) );

				if ( ! is_array( $names ) ) {
					continue;
				}

				foreach ( $names as $_name ) {
					$this->register_or_translate_option( $context, $_name, $key );
				}
			}
		}
	}

	/**
	 * Returns all wpml-config.xml files in MU plugins, plugins, theme, child theme, and Polylang custom directory.
	 *
	 * @since 3.1
	 *
	 * @return string[] A context identifier as array key, a file path as array value.
	 *
	 * @phpstan-return array<string, string>
	 */
	public function get_files() {
		if ( is_array( $this->files ) ) {
			return $this->files;
		}

		$this->files = array_merge(
			// Plugins.
			$this->get_plugin_files(),
			// Theme and child theme.
			$this->get_theme_files(),
			// MU Plugins.
			$this->get_mu_plugin_files(),
			// Custom.
			$this->get_custom_files()
		);

		return $this->files;
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

			if ( ! is_array( $cfs ) ) {
				continue;
			}

			foreach ( $cfs as $cf ) {
				$action = $this->get_field_attribute( $cf, 'action' );

				if ( 'copy' === $action || ( ! $sync && in_array( $action, array( 'translate', 'copy-once' ), true ) ) ) {
					$metas[] = (string) $cf;
				} else {
					$metas = array_diff( $metas, array( (string) $cf ) );
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

			if ( ! is_array( $cfs ) ) {
				continue;
			}

			foreach ( $cfs as $cf ) {
				$action = $this->get_field_attribute( $cf, 'action' );

				if ( 'copy' === $action || ( ! $sync && in_array( $action, array( 'translate', 'copy-once' ), true ) ) ) {
					$metas[] = (string) $cf;
				} else {
					$metas = array_diff( $metas, array( (string) $cf ) );
				}
			}
		}

		return $metas;
	}

	/**
	 * Adds post meta keys to export.
	 *
	 * @since 3.3
	 * @see   PLL_Export_Metas
	 *
	 * @param  array $keys {
	 *     A recursive array containing nested meta sub-keys to translate.
	 *     Ex: array(
	 *      'meta_to_translate_1' => 1,
	 *      'meta_to_translate_2' => 1,
	 *      'meta_to_translate_3' => array(
	 *        'sub_key_to_translate_1' => 1,
	 *        'sub_key_to_translate_2' => array(
	 *             'sub_sub_key_to_translate_1' => 1,
	 *         ),
	 *      ),
	 *    )
	 * }
	 * @return array
	 *
	 * @phpstan-param array<string, mixed> $keys
	 * @phpstan-return array<string, mixed>
	 */
	public function post_metas_to_export( $keys ) {
		// Add keys that have the `action` attribute set to `translate`.
		foreach ( $this->xmls as $xml ) {
			$fields = $xml->xpath( 'custom-fields/custom-field' );

			if ( ! is_array( $fields ) ) {
				// No custom fields.
				continue;
			}

			foreach ( $fields as $field ) {
				$action = $this->get_field_attribute( $field, 'action' );

				if ( 'translate' !== $action ) {
					continue;
				}

				$keys[ (string) $field ] = 1;
			}
		}

		// Deal with sub-field translations.
		foreach ( $this->xmls as $xml ) {
			$fields = $xml->xpath( 'custom-fields-texts/key' );

			if ( ! is_array( $fields ) ) {
				// No 'custom-fields-texts' nodes.
				continue;
			}

			foreach ( $fields as $field ) {
				$name = $this->get_field_attribute( $field, 'name' );

				if ( '' === $name ) {
					// Wrong configuration: empty `name` attribute (meta name).
					continue;
				}

				if ( ! array_key_exists( $name, $keys ) ) {
					// Wrong configuration: the field is not in `custom-fields/custom-field`.
					continue;
				}

				$keys = $this->xml_to_array( $field, $keys, 1 );
			}
		}

		return $keys;
	}

	/**
	 * Adds term meta keys to export.
	 * Note: sub-key translations are not currently supported by WPML.
	 *
	 * @since 3.3
	 * @see   PLL_Export_Metas
	 *
	 * @param  array $keys {
	 *     An array containing meta keys to translate.
	 *     Ex: array(
	 *      'meta_to_translate_1' => 1,
	 *      'meta_to_translate_2' => 1,
	 *      'meta_to_translate_3' => 1,
	 *    )
	 * }
	 * @return array
	 *
	 * @phpstan-param array<string, mixed> $keys
	 * @phpstan-return array<string, mixed>
	 */
	public function term_metas_to_export( $keys ) {
		// Add keys that have the `action` attribute set to `translate`.
		foreach ( $this->xmls as $xml ) {
			$fields = $xml->xpath( 'custom-term-fields/custom-term-field' );

			if ( ! is_array( $fields ) ) {
				// No custom fields.
				continue;
			}

			foreach ( $fields as $field ) {
				$action = $this->get_field_attribute( $field, 'action' );

				if ( 'translate' !== $action ) {
					continue;
				}

				$keys[ (string) $field ] = 1;
			}
		}

		return $keys;
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

			if ( ! is_array( $pts ) ) {
				continue;
			}

			foreach ( $pts as $pt ) {
				$translate = $this->get_field_attribute( $pt, 'translate' );

				if ( '1' === $translate && ! $hide ) {
					$types[ (string) $pt ] = (string) $pt;
				} else {
					unset( $types[ (string) $pt ] ); // The theme/plugin author decided what to do with the post type so don't allow the user to change this
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

			if ( ! is_array( $taxos ) ) {
				continue;
			}

			foreach ( $taxos as $tax ) {
				$translate = $this->get_field_attribute( $tax, 'translate' );

				if ( '1' === $translate && ! $hide ) {
					$taxonomies[ (string) $tax ] = (string) $tax;
				} else {
					unset( $taxonomies[ (string) $tax ] ); // the theme/plugin author decided what to do with the taxonomy so don't allow the user to change this
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
	 * @param string[][] $parsing_rules Rules as Xpath expressions to evaluate in the blocks content.
	 * @return string[][] Rules completed with ones from wpml-config file.
	 *
	 * @phpstan-param array<string,array<string>> $parsing_rules
	 * @phpstan-return array<string,array<string>>
	 */
	public function translate_blocks( $parsing_rules ) {
		return array_merge( $parsing_rules, $this->get_blocks_parsing_rules( 'xpath' ) );
	}

	/**
	 * Translation management for blocks attributes.
	 *
	 * @since 3.3
	 *
	 * @param string[][] $parsing_rules Rules for blocks attributes to translate.
	 * @return string[][] Rules completed with ones from wpml-config file.
	 *
	 * @phpstan-param array<string,array<string>> $parsing_rules
	 * @phpstan-return array<string,array<string>>
	 */
	public function translate_blocks_attributes( $parsing_rules ) {
		return array_merge( $parsing_rules, $this->get_blocks_parsing_rules( 'key' ) );
	}

	/**
	 * Returns rules to extract translatable strings from blocks.
	 *
	 * @since 3.3
	 *
	 * @param string $rule_tag Tag name to extract.
	 * @return string[][] The rules.
	 */
	protected function get_blocks_parsing_rules( $rule_tag ) {

		if ( null === $this->parsing_rules ) {
			$this->parsing_rules = $this->extract_blocks_parsing_rules();
		}

		return isset( $this->parsing_rules[ $rule_tag ] ) ? $this->parsing_rules[ $rule_tag ] : array();
	}

	/**
	 * Extract all rules from WPML config file to translate strings for blocks.
	 *
	 * @since 3.3
	 *
	 * @return string[][][] Rules completed with ones from wpml-config file.
	 */
	protected function extract_blocks_parsing_rules() {
		$parsing_rules = array();

		foreach ( $this->xmls as $xml ) {
			$blocks = $xml->xpath( 'gutenberg-blocks/gutenberg-block' );

			if ( ! is_array( $blocks ) ) {
				continue;
			}

			foreach ( $blocks as $block ) {
				$translate = $this->get_field_attribute( $block, 'translate' );

				if ( '1' !== $translate ) {
					continue;
				}

				$block_name = $this->get_field_attribute( $block, 'type' );

				if ( '' === $block_name ) {
					continue;
				}

				foreach ( $block->children() as $child ) {
					$rule      = '';
					$child_tag = $child->getName();

					switch ( $child_tag ) {
						case 'xpath':
							$rule = trim( (string) $child );
							break;

						case 'key':
							$rule = $this->get_field_attribute( $child, 'name' );
							break;
					}

					if ( '' !== $rule ) {
						$parsing_rules[ $child_tag ][ $block_name ][] = $rule;
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
	 * @since 3.3 Type-hinted the parameters `$key` and `$arr`.
	 * @since 3.3 `$arr` is not passed by reference anymore.
	 * @since 3.3 Added the parameter `$fill_value`.
	 *
	 * @param SimpleXMLElement $key        XML node.
	 * @param array            $arr        Array of option keys to translate.
	 * @param mixed            $fill_value Value to use when filling entries. Default is true.
	 * @return array
	 */
	protected function xml_to_array( SimpleXMLElement $key, array $arr = array(), $fill_value = true ) {
		$name = $this->get_field_attribute( $key, 'name' );

		if ( '' === $name ) {
			return $arr;
		}

		$children = $key->children();

		if ( count( $children ) ) {
			foreach ( $children as $child ) {
				if ( ! isset( $arr[ $name ] ) || ! is_array( $arr[ $name ] ) ) {
					$arr[ $name ] = array();
				}

				$arr[ $name ] = $this->xml_to_array( $child, $arr[ $name ], $fill_value );
			}
		} else {
			$arr[ $name ] = $fill_value; // Multiline as in WPML.
		}

		return $arr;
	}

	/**
	 * Get the value of an attribute.
	 *
	 * @since 3.3
	 *
	 * @param  SimpleXMLElement $field          A XML node.
	 * @param  string           $attribute_name Node of the attribute.
	 * @return string
	 */
	private function get_field_attribute( SimpleXMLElement $field, $attribute_name ) {
		$attributes = $field->attributes();

		if ( empty( $attributes ) || ! isset( $attributes[ $attribute_name ] ) ) {
			return '';
		}

		return trim( (string) $attributes[ $attribute_name ] );
	}

	/**
	 * Returns all wpml-config.xml files in MU plugins.
	 *
	 * @since 3.3
	 *
	 * @return string[] A context identifier as array key, a file path as array value.
	 *
	 * @phpstan-return array<string, string>
	 */
	private function get_mu_plugin_files() {
		if ( ! is_dir( WPMU_PLUGIN_DIR ) || ! is_readable( WPMU_PLUGIN_DIR ) ) {
			return array();
		}

		$files = array();

		// Search for top level wpml-config.xml file.
		$file_path = WPMU_PLUGIN_DIR . '/wpml-config.xml';

		if ( is_readable( $file_path ) ) {
			$files['mu-plugins'] = $file_path;
		}

		// Search in proxy loaded MU plugins.
		foreach ( new DirectoryIterator( WPMU_PLUGIN_DIR ) as $file_info ) {
			if ( $file_info->isDot() || ! $file_info->isDir() ) {
				continue;
			}

			$file_path = $file_info->getPathname() . '/wpml-config.xml';

			if ( is_readable( $file_path ) ) {
				$files[ 'mu-plugins/' . $file_info->getFilename() ] = $file_path;
			}
		}

		return $files;
	}

	/**
	 * Returns all wpml-config.xml files in plugins.
	 *
	 * @since 3.3
	 *
	 * @return string[] A context identifier as array key, a file path as array value.
	 *
	 * @phpstan-return array<string, string>
	 */
	private function get_plugin_files() {
		$files   = array();
		$plugins = array();

		if ( is_multisite() ) {
			// Don't forget sitewide active plugins thanks to Reactorshop http://wordpress.org/support/topic/polylang-and-yoast-seo-plugin/page/2?replies=38#post-4801829.
			$sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );

			if ( ! empty( $sitewide_plugins ) && is_array( $sitewide_plugins ) ) {
				$plugins = array_keys( $sitewide_plugins );
			}
		}

		// By-site plugins.
		$active_plugins = get_option( 'active_plugins', array() );

		if ( ! empty( $active_plugins ) && is_array( $active_plugins ) ) {
			$plugins = array_merge( $plugins, $active_plugins );
		}

		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . '%s/wpml-config.xml';

		foreach ( $plugins as $plugin ) {
			if ( ! is_string( $plugin ) || '' === $plugin ) {
				continue;
			}

			$file_dir  = dirname( $plugin );
			$file_path = sprintf( $plugin_path, $file_dir );

			if ( is_readable( $file_path ) ) {
				$files[ "plugins/{$file_dir}" ] = $file_path;
			}
		}

		return $files;
	}

	/**
	 * Returns all wpml-config.xml files in theme and child theme.
	 *
	 * @since 3.3
	 *
	 * @return string[] A context identifier as array key, a file path as array value.
	 *
	 * @phpstan-return array<string, string>
	 */
	private function get_theme_files() {
		$files = array();

		// Theme.
		$template_path = get_template_directory();
		$file_path     = "{$template_path}/wpml-config.xml";

		if ( is_readable( $file_path ) ) {
			$files[ 'themes/' . get_template() ] = $file_path;
		}

		// Child theme.
		$stylesheet_path = get_stylesheet_directory();
		$file_path       = "{$stylesheet_path}/wpml-config.xml";

		if ( $stylesheet_path !== $template_path && is_readable( $file_path ) ) {
			$files[ 'themes/' . get_stylesheet() ] = $file_path;
		}

		return $files;
	}

	/**
	 * Returns the wpml-config.xml file in Polylang custom directory.
	 *
	 * @since 3.3
	 *
	 * @return string[] A context identifier as array key, a file path as array value.
	 *
	 * @phpstan-return array<string, string>
	 */
	private function get_custom_files() {
		$file_path = PLL_LOCAL_DIR . '/wpml-config.xml';

		if ( ! is_readable( $file_path ) ) {
			return array();
		}

		return array(
			'Polylang' => $file_path,
		);
	}
}
