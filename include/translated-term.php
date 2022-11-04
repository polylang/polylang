<?php
/**
 * @package Polylang
 */

/**
 * Setups the taxonomies languages and translations model.
 *
 * @since 1.8
 */
class PLL_Translated_Term extends PLL_Translated_Object {

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_language = 'term_language';

	/**
	 * Object type to use when registering the taxonomy.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $object_type = 'term';

	/**
	 * Name of the `PLL_Language` property that stores the term_taxonomy ID.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_tt_prop_name = 'tl_term_taxonomy_id';

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations = 'term_translations';

	/**
	 * Object type to use when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type = 'term';

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		parent::__construct( $model );

		$this->init();
	}

	/**
	 * Adds hooks.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'get_terms', array( $this, '_prime_terms_cache' ), 10, 2 );
		add_action( 'clean_term_cache', array( $this, 'clean_term_cache' ) );
	}

	/**
	 * Stores the term's language in the database.
	 *
	 * @since 0.6
	 * @since 3.3 Renamed the parameter $term_id into $id.
	 *
	 * @param int                     $id   Term ID.
	 * @param int|string|PLL_Language $lang Language (term_id, slug, or object).
	 * @return void
	 */
	public function set_language( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		$old_lang = $this->get_language( $id );
		$old_lang = $old_lang ? $old_lang->tl_term_id : '';

		$lang = $this->model->get_language( $lang );
		$lang = $lang ? $lang->tl_term_id : '';

		if ( $old_lang === $lang ) {
			return;
		}

		wp_set_object_terms( $id, $lang, $this->tax_language );

		// Add translation group for correct WXR export.
		$translations = $this->get_translations( $id );
		$slug         = array_search( $id, $translations );

		if ( ! empty( $slug ) ) {
			unset( $translations[ $slug ] );
		}

		$this->save_translations( $id, $translations );
	}

	/**
	 * Removes the term language from the database.
	 *
	 * @since 0.5
	 * @since 3.3 Renamed the parameter $term_id into $id.
	 *
	 * @param int $id Term ID.
	 * @return void
	 */
	public function delete_language( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		wp_delete_object_term_relationships( $id, $this->tax_language );
	}

	/**
	 * Returns the language of a term.
	 *
	 * @since 0.1
	 * @since 3.3 Renamed the parameter $value into $id.
	 * @since 3.3 Deprecated to retrieve the language by term slug + taxonomy anymore.
	 *
	 * @param int $id Term ID.
	 * @return PLL_Language|false A `PLL_Language` object, `false` if no language is associated to that object.
	 */
	public function get_language( $id ) {
		if ( func_num_args() > 1 ) {
			// Backward compatibility.
			_deprecated_argument(
				__METHOD__ . '()',
				'3.4',
				esc_html(
					sprintf(
						/* translators: s is a function name. */
						__( 'Please use %s instead.', 'polylang' ),
						get_class( $this ) . '::get_language_by_term_slug( $slug, $taxonomy )'
					)
				)
			);
			return $this->get_language_by_term_slug( $id, func_get_arg( 1 ) ); // @phpstan-ignore-line
		}

		return parent::get_language( $id );
	}

	/**
	 * Returns the language of a term by slug and taxonomy.
	 *
	 * @since 3.3
	 *
	 * @param string $slug     Term slug.
	 * @param string $taxonomy Taxonomy.
	 * @return PLL_Language|false A `PLL_Language` object. `false` if no language is associated to that term.
	 */
	public function get_language_by_term_slug( $slug, $taxonomy ) {
		if ( empty( $slug ) || empty( $taxonomy ) ) {
			return false;
		}

		// get_term_by() still not cached in WP 3.5.1 but internally, the function is always called by term_id.
		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( ! $term instanceof WP_Term ) {
			return false;
		}

		// Get the language and make sure it is a PLL_Language object.
		$lang = $this->get_object_term( $term->term_id, $this->tax_language );

		if ( empty( $lang ) ) {
			return false;
		}

		return $this->model->get_language( $lang->term_id );
	}

	/**
	 * Deletes a translation of a term.
	 *
	 * @since 0.5
	 *
	 * @param int $id Term ID.
	 * @return void
	 */
	public function delete_translation( $id ) {
		global $wpdb;

		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		$slug = array_search( $id, $this->get_translations( $id ) ); // in case some plugin stores the same value with different key

		parent::delete_translation( $id );
		wp_delete_object_term_relationships( $id, $this->tax_translations );

		if ( doing_action( 'pre_delete_term' ) ) {
			return;
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM $wpdb->terms WHERE term_id = %d;", $id ) ) ) {
			return;
		}

		// Always keep a group for terms to allow relationships remap when importing from a WXR file
		$group        = uniqid( 'pll_' );
		$translations = array( $slug => $id );
		wp_insert_term( $group, $this->tax_translations, array( 'description' => maybe_serialize( $translations ) ) );
		wp_set_object_terms( $id, $group, $this->tax_translations );
	}

	/**
	 * A JOIN clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 1.2
	 * @since 2.6 The `$alias` parameter was added.
	 *
	 * @param string $alias Optional alias for object table.
	 * @return string The JOIN clause.
	 *
	 * @phpstan-return non-empty-string
	 */
	public function join_clause( $alias = '' ) {
		global $wpdb;

		if ( empty( $alias ) ) {
			$alias = 't';
		}

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
		$ids = array();

		if ( is_array( $terms ) && $this->model->is_translated_taxonomy( $taxonomies ) ) {
			foreach ( $terms as $term ) {
				$ids[] = is_object( $term ) ? $term->term_id : (int) $term;
			}
		}

		if ( ! empty( $ids ) ) {
			update_object_term_cache( array_unique( $ids ), 'term' ); // Adds language and translation of terms to cache
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

	/**
	 * Tells whether a translation term must updated.
	 *
	 * @since 2.3
	 *
	 * @param int   $id           Term ID.
	 * @param int[] $translations An associative array of translations with language code as key and translation ID as
	 *                            value. Make sure to sanitize this.
	 * @return bool
	 */
	protected function should_update_translation_group( $id, $translations ) {
		// Don't do anything if no translations have been added to the group.
		$old_translations = $this->get_translations( $id );
		if ( count( $translations ) > 1 && ! empty( array_diff_assoc( $translations, $old_translations ) ) ) {
			return true;
		}

		// But we need a translation group for terms to allow relationships remap when importing from a WXR file
		$term = $this->get_object_term( $id, $this->tax_translations );
		return empty( $term ) || ! empty( array_diff_assoc( $translations, $old_translations ) );
	}
}
