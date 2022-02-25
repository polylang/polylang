<?php
/**
 * @package Polylang
 */

/**
 * Setups the taxonomies languages and translations model
 *
 * @since 1.8
 */
class PLL_Translated_Term extends PLL_Translated_Object {

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $model
	 */
	public function __construct( &$model ) {
		$this->object_type = 'term'; // For taxonomies
		$this->type = 'term'; // For capabilities
		$this->tax_language = 'term_language';
		$this->tax_translations = 'term_translations';
		$this->tax_tt = 'tl_term_taxonomy_id';

		parent::__construct( $model );

		// Filters to prime terms cache
		add_filter( 'get_terms', array( $this, '_prime_terms_cache' ), 10, 2 );
		add_filter( 'get_object_terms', array( $this, 'wp_get_object_terms' ), 10, 3 );

		add_action( 'clean_term_cache', array( $this, 'clean_term_cache' ) );
	}

	/**
	 * Stores the term language in the database.
	 *
	 * @since 0.6
	 *
	 * @param int                     $term_id Term id.
	 * @param int|string|PLL_Language $lang    Language (term_id or slug or object).
	 * @return void
	 */
	public function set_language( $term_id, $lang ) {
		$term_id = $this->sanitize_int_id( $term_id );

		if ( empty( $term_id ) ) {
			return;
		}

		$old_lang = $this->get_language( $term_id );
		$old_lang = $old_lang ? $old_lang->tl_term_id : '';

		$lang = $this->model->get_language( $lang );
		$lang = $lang ? $lang->tl_term_id : '';

		if ( $old_lang === $lang ) {
			return;
		}

		wp_set_object_terms( $term_id, $lang, $this->tax_language );

		// Add translation group for correct WXR export.
		$translations = $this->get_translations( $term_id );
		$slug         = array_search( $term_id, $translations );

		if ( ! empty( $slug ) ) {
			unset( $translations[ $slug ] );
		}

		$this->save_translations( $term_id, $translations );
	}

	/**
	 * Removes the term language in database
	 *
	 * @since 0.5
	 *
	 * @param int $term_id term id
	 * @return void
	 */
	public function delete_language( $term_id ) {
		wp_delete_object_term_relationships( $this->sanitize_int_id( $term_id ), $this->tax_language );
	}

	/**
	 * Returns the language of a term
	 *
	 * @since 0.1
	 *
	 * @param int|string $value    term id or term slug
	 * @param string     $taxonomy optional taxonomy needed when the term slug is passed as first parameter
	 * @return PLL_Language|false PLL_Language object, false if no language is associated to that term
	 */
	public function get_language( $value, $taxonomy = '' ) {
		if ( is_numeric( $value ) ) {
			$term_id = $this->sanitize_int_id( $value );
		}

		// get_term_by still not cached in WP 3.5.1 but internally, the function is always called by term_id
		elseif ( is_string( $value ) && $taxonomy ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( $term instanceof WP_Term ) {
				$term_id = $term->term_id;
			}
		}

		if ( empty( $term_id ) ) {
			return false;
		}

		// Get the language and make sure it is a PLL_Language object.
		$lang = $this->get_object_term( $term_id, $this->tax_language );

		if ( empty( $lang ) ) {
			return false;
		}

		return $this->model->get_language( $lang->term_id );
	}

	/**
	 * Tells whether a translation term must updated.
	 *
	 * @since 2.3
	 *
	 * @param int   $id           Post id or term id.
	 * @param int[] $translations An associative array of translations with language code as key and translation id as
	 *                            value. Make sure to sanitize this.
	 * @return bool
	 */
	protected function should_update_translation_group( $id, $translations ) {
		// Don't do anything if no translations have been added to the group
		$old_translations = $this->get_translations( $id );
		if ( count( $translations ) > 1 && ! empty( array_diff_assoc( $translations, $old_translations ) ) ) {
			return true;
		}

		// But we need a translation group for terms to allow relationships remap when importing from a WXR file
		$term = $this->get_object_term( $id, $this->tax_translations );
		return empty( $term ) || ! empty( array_diff_assoc( $translations, $old_translations ) );
	}

	/**
	 * Deletes a translation
	 *
	 * @since 0.5
	 *
	 * @param int $id term id
	 * @return void
	 */
	public function delete_translation( $id ) {
		global $wpdb;
		$id   = $this->sanitize_int_id( $id );
		$slug = array_search( $id, $this->get_translations( $id ) ); // in case some plugin stores the same value with different key

		parent::delete_translation( $id );
		wp_delete_object_term_relationships( $id, $this->tax_translations );

		if ( ! doing_action( 'pre_delete_term' ) && $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM $wpdb->terms WHERE term_id = %d;", $id ) ) ) {
			// Always keep a group for terms to allow relationships remap when importing from a WXR file
			$group        = uniqid( 'pll_' );
			$translations = array( $slug => $id );
			wp_insert_term( $group, $this->tax_translations, array( 'description' => maybe_serialize( $translations ) ) );
			wp_set_object_terms( $id, $group, $this->tax_translations );
		}
	}

	/**
	 * A join clause to add to sql queries when filtering by language is needed directly in query
	 *
	 * @since 1.2
	 * @since 2.6 The `$alias` parameter was added.
	 *
	 * @param string $alias Alias for $wpdb->terms table
	 * @return string join clause
	 */
	public function join_clause( $alias = 't' ) {
		global $wpdb;
		return " INNER JOIN $wpdb->term_relationships AS pll_tr ON pll_tr.object_id = $alias.term_id";
	}

	/**
	 * Caches the language and translations when terms are queried by get_terms().
	 *
	 * @since 1.2
	 *
	 * @param WP_Term[]|int[] $terms      Queried terms.
	 * @param string[]        $taxonomies Queried taxonomies.
	 * @return WP_Term[]|int[] Unmodified $terms.
	 */
	public function _prime_terms_cache( $terms, $taxonomies ) {
		$term_ids = array();

		if ( is_array( $terms ) && $this->model->is_translated_taxonomy( $taxonomies ) ) {
			foreach ( $terms as $term ) {
				$term_ids[] = is_object( $term ) ? $term->term_id : (int) $term;
			}
		}

		if ( ! empty( $term_ids ) ) {
			update_object_term_cache( array_unique( $term_ids ), 'term' ); // Adds language and translation of terms to cache
		}
		return $terms;
	}

	/**
	 * When terms are found for posts, add their language and translations to cache.
	 *
	 * @since 1.2
	 *
	 * @param WP_Term[] $terms      Array of terms for the given object or objects.
	 * @param int[]     $object_ids Array of object IDs for which terms were retrieved.
	 * @param string[]  $taxonomies Array of taxonomy names from which terms were retrieved.
	 * @return WP_Term[] Unmodified $terms.
	 */
	public function wp_get_object_terms( $terms, $object_ids, $taxonomies ) {
		if ( ! in_array( $this->tax_translations, $taxonomies ) ) {
			$this->_prime_terms_cache( $terms, $taxonomies );
		}
		return $terms;
	}

	/**
	 * When the term cache is cleaned, cleans the object term cache too.
	 *
	 * @since 2.0
	 *
	 * @param int[] $ids An array of term IDs.
	 * @return void
	 */
	public function clean_term_cache( $ids ) {
		clean_object_term_cache( $this->sanitize_int_ids_list( $ids ), 'term' );
	}
}
