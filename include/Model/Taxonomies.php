<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Translated_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Model for taxonomies filtered/translated by Polylang.
 *
 * @since 3.7
 */
class Taxonomies {
	/**
	 * Translated term model.
	 *
	 * @var PLL_Translated_Term
	 */
	public $translated_object;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Translated_Term $translated_object Terms model.
	 */
	public function __construct( PLL_Translated_Term $translated_object ) {
		$this->translated_object = $translated_object;
	}

	/**
	 * Returns taxonomies that need to be translated.
	 * The taxonomies list is cached for better better performance.
	 * The method waits for 'after_setup_theme' to apply the cache
	 * to allow themes adding the filter in functions.php.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Model::get_translated_taxonomies()` to `WP_Syntex\Polylang\Model\Taxonomies::get_translated()`.
	 *
	 * @param bool $filter True if we should return only valid registered taxonomies.
	 * @return string[] Array of registered taxonomy names for which Polylang manages languages and translations.
	 */
	public function get_translated( $filter = true ): array {
		return $this->translated_object->get_translated_object_types( $filter );
	}

	/**
	 * Returns true if Polylang manages languages and translations for this taxonomy.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Model::is_translated_taxonomy()` to `WP_Syntex\Polylang\Model\Taxonomies::is_translated()`.
	 *
	 * @param string|string[] $tax Taxonomy name or array of taxonomy names.
	 * @return bool
	 */
	public function is_translated( $tax ): bool {
		if ( empty( array_filter( (array) $tax ) ) ) {
			return false;
		}

		return $this->translated_object->is_translated_object_type( $tax );
	}

	/**
	 * Return taxonomies that need to be filtered (post_format like).
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::get_filtered_taxonomies()` to `WP_Syntex\Polylang\Model\Taxonomies::get_filtered()`.
	 *
	 * @param bool $filter True if we should return only valid registered taxonomies.
	 * @return string[] Array of registered taxonomy names.
	 */
	public function get_filtered( $filter = true ): array {
		if ( did_action( 'after_setup_theme' ) ) {
			static $taxonomies = null;
		}

		if ( empty( $taxonomies ) ) {
			$taxonomies = array( 'post_format' => 'post_format' );

			/**
			 * Filters the list of taxonomies not translatable but filtered by language.
			 * Includes only the post format by default
			 * The filter must be added soon in the WordPress loading process:
			 * in a function hooked to ‘plugins_loaded’ or directly in functions.php for themes.
			 *
			 * @since 1.7
			 *
			 * @param string[] $taxonomies  List of taxonomy names.
			 * @param bool     $is_settings True when displaying the list of custom taxonomies in Polylang settings.
			 */
			$taxonomies = apply_filters( 'pll_filtered_taxonomies', $taxonomies, false );
		}

		return $filter ? array_intersect( $taxonomies, get_taxonomies() ) : $taxonomies;
	}

	/**
	 * Returns true if Polylang filters this taxonomy per language.
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::is_filtered_taxonomy()` to `WP_Syntex\Polylang\Model\Taxonomies::is_filtered()`.
	 *
	 * @param string|string[] $tax Taxonomy name or array of taxonomy names.
	 * @return bool
	 */
	public function is_filtered( $tax ): bool {
		$taxonomies = $this->get_filtered( false );
		return ( is_array( $tax ) && array_intersect( $tax, $taxonomies ) ) || in_array( $tax, $taxonomies );
	}

	/**
	 * Returns the query vars of all filtered taxonomies.
	 *
	 * @since 1.7
	 * @since 3.7 Moved from `PLL_Model::get_filtered_taxonomies_query_vars()` to `WP_Syntex\Polylang\Model\Taxonomies::get_filtered_query_vars()`.
	 *
	 * @return string[]
	 */
	public function get_filtered_query_vars(): array {
		$query_vars = array();
		foreach ( $this->get_filtered() as $filtered_tax ) {
			$tax = get_taxonomy( $filtered_tax );
			if ( ! empty( $tax ) && is_string( $tax->query_var ) ) {
				$query_vars[] = $tax->query_var;
			}
		}
		return $query_vars;
	}
}
