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
		add_filter( 'wp_get_object_terms', array( $this, 'wp_get_object_terms' ), 10, 3 );

		add_action( 'clean_term_cache', array( $this, 'clean_term_cache' ) );
	}

	/**
	 * Stores the term language in the database
	 *
	 * @since 0.6
	 *
	 * @param int               $term_id term id
	 * @param int|string|object $lang    language ( term_id or slug or object )
	 */
	public function set_language( $term_id, $lang ) {
		$term_id = (int) $term_id;

		$old_lang = $this->get_language( $term_id );
		$old_lang = $old_lang ? $old_lang->tl_term_id : '';
		$lang = $lang ? $this->model->get_language( $lang )->tl_term_id : '';

		if ( $old_lang !== $lang ) {
			wp_set_object_terms( $term_id, $lang, 'term_language' );

			// Add translation group for correct WXR export
			$translations = $this->get_translations( $term_id );
			if ( $slug = array_search( $term_id, $translations ) ) {
				unset( $translations[ $slug ] );
			}

			$this->save_translations( $term_id, $translations );
		}
	}

	/**
	 * Removes the term language in database
	 *
	 * @since 0.5
	 *
	 * @param int $term_id term id
	 */
	public function delete_language( $term_id ) {
		wp_delete_object_term_relationships( $term_id, 'term_language' );
	}

	/**
	 * Returns the language of a term
	 *
	 * @since 0.1
	 *
	 * @param int|string $value    term id or term slug
	 * @param string     $taxonomy optional taxonomy needed when the term slug is passed as first parameter
	 * @return bool|object PLL_Language object, false if no language is associated to that term
	 */
	public function get_language( $value, $taxonomy = '' ) {
		if ( is_numeric( $value ) ) {
			$term_id = $value;
		}

		// get_term_by still not cached in WP 3.5.1 but internally, the function is always called by term_id
		elseif ( is_string( $value ) && $taxonomy ) {
			$term_id = get_term_by( 'slug', $value, $taxonomy )->term_id;
		}

		// Get the language and make sure it is a PLL_Language object
		return isset( $term_id ) && ( $lang = $this->get_object_term( $term_id, 'term_language' ) ) ? $this->model->get_language( $lang->term_id ) : false;
	}

	/**
	 * Tells whether a translation term must updated
	 *
	 * @since 2.3
	 *
	 * @param array $id           Post id or term id
	 * @param array $translations An associative array of translations with language code as key and translation id as value
	 */
	protected function should_update_translation_group( $id, $translations ) {
		// Don't do anything if no translations have been added to the group
		$old_translations = $this->get_translations( $id );
		if ( count( $translations ) > 1 && count( array_diff_assoc( $translations, $old_translations ) ) > 0 ) {
			return true;
		}

		// But we need a translation group for terms to allow relationships remap when importing from a WXR file
		$term = $this->get_object_term( $id, $this->tax_translations );
		return empty( $term ) || count( array_diff_assoc( $translations, $old_translations ) );
	}

	/**
	 * Deletes a translation
	 *
	 * @since 0.5
	 *
	 * @param int $id term id
	 */
	public function delete_translation( $id ) {
		global $wpdb;
		$slug = array_search( $id, $this->get_translations( $id ) ); // in case some plugin stores the same value with different key

		parent::delete_translation( $id );
		wp_delete_object_term_relationships( $id, 'term_translations' );

		if ( ! doing_action( 'pre_delete_term' ) && $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM $wpdb->terms WHERE term_id = %d;", $id ) ) ) {
			// Always keep a group for terms to allow relationships remap when importing from a WXR file
			$translations = array( $slug => $id );
			wp_insert_term( $group = uniqid( 'pll_' ), 'term_translations', array( 'description' => maybe_serialize( $translations ) ) );
			wp_set_object_terms( $id, $group, 'term_translations' );
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
	 * Cache language and translations when terms are queried by get_terms
	 *
	 * @since 1.2
	 *
	 * @param array $terms      queried terms
	 * @param array $taxonomies queried taxonomies
	 * @return array unmodified $terms
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
	 * When terms are found for posts, add their language and translations to cache
	 *
	 * @since 1.2
	 *
	 * @param array $terms      terms found
	 * @param array $object_ids not used
	 * @param array $taxonomies terms taxonomies
	 * @return array unmodified $terms
	 */
	public function wp_get_object_terms( $terms, $object_ids, $taxonomies ) {
		$taxonomies = explode( "', '", trim( $taxonomies, "'" ) );
		if ( ! in_array( 'term_translations', $taxonomies ) ) {
			$this->_prime_terms_cache( $terms, $taxonomies );
		}
		return $terms;
	}

	/**
	 * When the term cache is cleaned, clean the object term cache too
	 *
	 * @since 2.0
	 *
	 * @param array $ids An array of term IDs.
	 */
	public function clean_term_cache( $ids ) {
		clean_object_term_cache( $ids, 'term' );
	}
}
