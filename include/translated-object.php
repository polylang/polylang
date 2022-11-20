<?php
/**
 * @package Polylang
 */

/**
 * Setups the objects languages and translations model.
 *
 * @since 1.8
 */
abstract class PLL_Translated_Object {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Object type to use when registering the taxonomies.
	 * Left empty for posts.
	 *
	 * @var string|null
	 */
	protected $object_type;

	/**
	 * Object type to use when checking capabilities.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Taxonomy name for the languages.
	 *
	 * @var string
	 */
	protected $tax_language;

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 */
	protected $tax_translations;

	/**
	 * PLL_Language property name for the term_taxonomy id.
	 *
	 * @var string
	 */
	protected $tax_tt;

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of PLL_Model.
	 */
	public function __construct( &$model ) {
		$this->model = &$model;

		/*
		 * Register our taxonomies as soon as possible.
		 * This is early registration, not ready for rewrite rules as $wp_rewrite will be setup later.
		 */
		$args = array( 'label' => false, 'public' => false, 'query_var' => false, 'rewrite' => false, '_pll' => true );
		register_taxonomy( $this->tax_language, $this->object_type, $args );
		$args['update_count_callback'] = '_update_generic_term_count'; // Count *all* objects to avoid deleting in clean_translations_terms.
		register_taxonomy( $this->tax_translations, $this->object_type, $args );
	}

	/**
	 * Stores the language in the database.
	 *
	 * @since 0.6
	 *
	 * @param int                     $id   Object id.
	 * @param int|string|PLL_Language $lang Language (term_id or slug or object).
	 * @return void
	 */
	abstract public function set_language( $id, $lang );

	/**
	 * Returns the language of an object.
	 *
	 * @since 0.1
	 *
	 * @param int $id Object id.
	 * @return PLL_Language|false PLL_Language object, false if no language is associated to that object.
	 */
	abstract public function get_language( $id );

	/**
	 * Assigns a new language to an object, taking care of the translations group.
	 *
	 * @since 3.1
	 *
	 * @param int          $id   Object id.
	 * @param PLL_Language $lang New language to assign to the object.
	 * @return void
	 */
	public function update_language( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) || $this->get_language( $id ) === $lang ) {
			return;
		}

		$this->set_language( $id, $lang );

		$translations = $this->get_translations( $id );

		if ( $translations ) {
			// Remove the post's former language from the new translations group.
			$translations = array_diff( $translations, array( $id ) );
			$this->save_translations( $id, $translations );
		}
	}

	/**
	 * Wraps wp_get_object_terms() to cache it and return only one object.
	 * Inspired by the WordPress function get_the_terms().
	 *
	 * @since 1.2
	 *
	 * @param int    $object_id Object id ( typically a post_id or term_id ).
	 * @param string $taxonomy  Polylang taxonomy depending if we are looking for a post ( or term ) language ( or translation ).
	 * @return WP_Term|false The term associated to the object in the requested taxonomy if it exists, false otherwise.
	 */
	public function get_object_term( $object_id, $taxonomy ) {
		global $wp_version;

		$object_id = $this->sanitize_int_id( $object_id );

		if ( empty( $object_id ) ) {
			return false;
		}

		$term = get_object_term_cache( $object_id, $taxonomy );

		if ( is_array( $term ) ) {
			return ! empty( $term ) ? reset( $term ) : false;
		}

		// Query language and translations at the same time.
		$taxonomies = array( $this->tax_language, $this->tax_translations );

		// Query terms.
		$terms        = array();
		$term         = false;
		$object_terms = wp_get_object_terms( $object_id, $taxonomies, array( 'update_term_meta_cache' => false ) );

		if ( is_array( $object_terms ) ) {
			foreach ( $object_terms as $t ) {
				$terms[ $t->taxonomy ] = $t;
				if ( $t->taxonomy === $taxonomy ) {
					$term = $t;
				}
			}
		}

		// Stores it the way WP expects it. Set an empty cache if no term was found in the taxonomy.
		$store_only_term_ids = version_compare( $wp_version, '6.0', '>=' );
		foreach ( $taxonomies as $tax ) {
			if ( empty( $terms[ $tax ] ) ) {
				$to_cache = array();
			} elseif ( $store_only_term_ids ) {
				$to_cache = array( $terms[ $tax ]->term_id );
			} else {
				$to_cache = array( $terms[ $tax ] );
			}

			wp_cache_add( $object_id, $to_cache, $tax . '_relationships' );
		}

		return $term;
	}

	/**
	 * Returns a list of post translations, given a `tax_translations` term ID.
	 *
	 * @since 3.2
	 *
	 * @param int $term_id Term ID.
	 * @return int[] An associative array of translations with language code as key and translation id as value.
	 */
	public function get_translations_from_term_id( $term_id ) {
		$term_id = $this->sanitize_int_id( $term_id );

		if ( empty( $term_id ) ) {
			return array();
		}

		$translations_term = get_term( $term_id, $this->tax_translations );

		if ( ! $translations_term instanceof WP_Term || empty( $translations_term->description ) ) {
			return array();
		}

		// Lang slugs as array keys, template IDs as array values.
		$translations = maybe_unserialize( $translations_term->description );

		return $this->validate_translations( $translations, 0, 'display' );
	}

	/**
	 * Tells whether a translation term must be updated.
	 *
	 * @since 2.3
	 *
	 * @param int   $id           Object id ( typically a post_id or term_id ).
	 * @param int[] $translations An associative array of translations with language code as key and translation id as
	 *                            value. Make sure to sanitize this.
	 * @return bool
	 */
	protected function should_update_translation_group( $id, $translations ) {
		// Don't do anything if no translations have been added to the group.
		$old_translations = $this->get_translations( $id ); // Includes at least $id itself.
		return ! empty( array_diff_assoc( $translations, $old_translations ) );
	}

	/**
	 * Saves translations for posts or terms.
	 *
	 * @since 0.5
	 *
	 * @param int   $id           Object id ( typically a post_id or term_id ).
	 * @param int[] $translations An associative array of translations with language code as key and translation id as value.
	 * @return int[] An associative array with language codes as key and post ids as values.
	 */
	public function save_translations( $id, $translations ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return array();
		}

		$lang = $this->get_language( $id );

		if ( empty( $lang ) ) {
			return array();
		}

		// Sanitize and validate the translations array.
		$translations = $this->validate_translations( $translations, $id );

		// Unlink removed translations.
		$old_translations = $this->get_translations( $id );

		foreach ( array_diff_assoc( $old_translations, $translations ) as $object_id ) {
			$this->delete_translation( $object_id );
		}

		// Check id we need to create or update the translation group.
		if ( ! $this->should_update_translation_group( $id, $translations ) ) {
			return $translations;
		}

		$terms = wp_get_object_terms( $translations, $this->tax_translations );
		$term  = is_array( $terms ) && ! empty( $terms ) ? reset( $terms ) : false;

		if ( empty( $term ) ) {
			// Create a new term if necessary.
			$group = uniqid( 'pll_' );
			wp_insert_term( $group, $this->tax_translations, array( 'description' => maybe_serialize( $translations ) ) );
		} else {
			// Take care not to overwrite extra data stored in the description field, if any.
			$group = (int) $term->term_id;
			$descr = maybe_unserialize( $term->description );
			$descr = is_array( $descr ) ? array_diff_key( $descr, $old_translations ) : array(); // Remove old translations.
			$descr = array_merge( $descr, $translations ); // Add new one.
			wp_update_term( $group, $this->tax_translations, array( 'description' => maybe_serialize( $descr ) ) );
		}

		// Link all translations to the new term.
		foreach ( $translations as $p ) {
			wp_set_object_terms( $p, $group, $this->tax_translations );
		}

		if ( ! is_array( $terms ) ) {
			return $translations;
		}

		// Clean now unused translation groups.
		foreach ( $terms as $term ) {
			// Get fresh count value.
			$term = get_term( $term->term_id, $this->tax_translations );

			if ( $term instanceof WP_Term && empty( $term->count ) ) {
				wp_delete_term( $term->term_id, $this->tax_translations );
			}
		}

		return $translations;
	}

	/**
	 * Deletes a translation of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object id ( typically a post_id or term_id ).
	 * @return void
	 */
	public function delete_translation( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return;
		}

		$term = $this->get_object_term( $id, $this->tax_translations );

		if ( empty( $term ) ) {
			return;
		}

		$descr = maybe_unserialize( $term->description );

		if ( ! empty( $descr ) && is_array( $descr ) ) {
			$slug = array_search( $id, $this->get_translations( $id ) ); // In case some plugin stores the same value with different key.

			if ( false !== $slug ) {
				unset( $descr[ $slug ] );
			}
		}

		if ( empty( $descr ) || ! is_array( $descr ) ) {
			wp_delete_term( (int) $term->term_id, $this->tax_translations );
		} else {
			wp_update_term( (int) $term->term_id, $this->tax_translations, array( 'description' => maybe_serialize( $descr ) ) );
		}
	}

	/**
	 * Returns an array of translations of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object id ( typically a post_id or term_id ).
	 * @return int[] An associative array of translations with language code as key and translation id as value.
	 */
	public function get_translations( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return array();
		}

		$term         = $this->get_object_term( $id, $this->tax_translations );
		$translations = empty( $term->description ) ? array() : maybe_unserialize( $term->description );

		return $this->validate_translations( $translations, $id, 'display' );
	}

	/**
	 * Returns the id of the translation of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int                 $id   Object id ( typically a post_id or term_id ).
	 * @param PLL_Language|string $lang Language ( slug or object ).
	 * @return int|false Object id of the translation, false if there is none.
	 */
	public function get_translation( $id, $lang ) {
		$lang = $this->model->get_language( $lang );

		if ( empty( $lang ) ) {
			return false;
		}

		$translations = $this->get_translations( $id );

		return isset( $translations[ $lang->slug ] ) ? $translations[ $lang->slug ] : false;
	}

	/**
	 * Among the object and its translations, returns the id of the object which is in $lang
	 *
	 * @since 0.1
	 *
	 * @param int                     $id   Object id ( typically a post_id or term_id ).
	 * @param int|string|PLL_Language $lang Language ( term_id or slug or object ).
	 * @return int|false The translation object id if exists, otherwise the passed id, false if the passed object has no language.
	 */
	public function get( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		$lang = $this->model->get_language( $lang );

		if ( empty( $lang ) ) {
			return false;
		}

		$obj_lang = $this->get_language( $id );

		if ( empty( $obj_lang ) ) {
			return false;
		}

		return $obj_lang->term_id === $lang->term_id ? $id : $this->get_translation( $id, $lang );
	}

	/**
	 * A join clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 1.2
	 *
	 * @param string $alias Optional alias for object table.
	 * @return string Join clause.
	 */
	abstract public function join_clause( $alias = '' );

	/**
	 * A where clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language|PLL_Language[]|string|string[] $lang PLL_Language object or a comma separated list of language slug or an array of language slugs or objects.
	 * @return string Where clause.
	 */
	public function where_clause( $lang ) {
		$tt_id = $this->tax_tt;

		/*
		 * $lang is an object.
		 * This is generally the case if the query is coming from Polylang.
		 */
		if ( is_object( $lang ) ) {
			return ' AND pll_tr.term_taxonomy_id = ' . absint( $lang->$tt_id );
		}

		/*
		 * $lang is an array of objects, an array of slugs, or a comma separated list of slugs.
		 * The comma separated list of slugs can happen if the query is coming from outside with a 'lang' parameter.
		 */
		$languages        = is_array( $lang ) ? $lang : explode( ',', $lang );
		$languages_tt_ids = array();
		foreach ( $languages as $language ) {
			$language = $this->model->get_language( $language );

			if ( ! empty( $language ) ) {
				$languages_tt_ids[] = absint( $language->$tt_id );
			}
		}

		if ( empty( $languages_tt_ids ) ) {
			return '';
		}

		return ' AND pll_tr.term_taxonomy_id IN ( ' . implode( ',', $languages_tt_ids ) . ' )';
	}

	/**
	 * Checks if a user can synchronize translations.
	 *
	 * @since 2.6
	 *
	 * @param int $id Object id.
	 * @return bool
	 */
	public function current_user_can_synchronize( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		/**
		 * Filters whether a synchronization capability check should take place.
		 *
		 * @since 2.6
		 *
		 * @param bool|null $check Null to enable the capability check,
		 *                         true to always allow the synchronization,
		 *                         false to always disallow the synchronization.
		 *                         Defaults to true.
		 * @param int       $id    The synchronization source object id.
		 */
		$check = apply_filters( "pll_pre_current_user_can_synchronize_{$this->type}", true, $id );

		if ( null !== $check ) {
			return (bool) $check;
		}

		if ( ! current_user_can( "edit_{$this->type}", $id ) ) {
			return false;
		}

		foreach ( $this->get_translations( $id ) as $tr_id ) {
			if ( $tr_id !== $id && ! current_user_can( "edit_{$this->type}", $tr_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitizes an ID as positive integer.
	 * Kind of similar to `absint()`, but rejects negetive integers instead of making them positive.
	 *
	 * @since 3.2
	 *
	 * @param mixed $id A supposedly numeric ID.
	 * @return int A positive integer. `0` for non numeric values and negative integers.
	 *
	 * @phpstan-return int<0,max>
	 */
	public function sanitize_int_id( $id ) {
		return is_numeric( $id ) && $id >= 1 ? abs( (int) $id ) : 0;
	}

	/**
	 * Sanitizes an array of IDs as positive integers.
	 * `0` values are removed.
	 *
	 * @since 3.2
	 *
	 * @param mixed $ids An associative array of translations with language code as key and translation ID as value.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 */
	public function sanitize_int_ids_list( $ids ) {
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return array();
		}

		$ids = array_map( array( $this, 'sanitize_int_id' ), $ids );

		return array_filter( $ids );
	}

	/**
	 * Validates and sanitizes translations.
	 * This will:
	 * - Make sure to return only translations in existing languages (and only translations).
	 * - Sanitize the values.
	 * - Make sure the provided translation (`$id`) is in the list.
	 * - Check that the translated objects are in the right language, if `$context` is set to 'save'.
	 *
	 * @since 3.1
	 * @since 3.2 Doesn't return `0` ID values.
	 * @since 3.2 Added parameters `$id` and `$context`.
	 *
	 * @param int[]  $translations An associative array of translations with language code as key and translation ID as
	 *                             value.
	 * @param int    $id           Optional. The object ID for which the translations are validated. When provided, the
	 *                             process makes sure it is added to the list. Default 0.
	 * @param string $context      Optional. The operation for which the translations are validated. When set to
	 *                             'save', a check is done to verify that the IDs and langs correspond.
	 *                             'display' should be used otherwise. Default 'save'.
	 * @return int[]
	 */
	protected function validate_translations( $translations, $id = 0, $context = 'save' ) {
		if ( ! is_array( $translations ) ) {
			$translations = array();
		}

		/**
		 * Remove translations in non-existing languages, and non-translation data (we allow plugins to store other
		 * information in the array).
		 */
		$translations = array_intersect_key(
			$translations,
			array_flip( $this->model->get_languages_list( array( 'fields' => 'slug' ) ) )
		);

		// Make sure values are clean before working with them.
		$translations = $this->sanitize_int_ids_list( $translations );

		if ( 'save' === $context ) {
			/**
			 * Check that the translated objects are in the right language.
			 * For better performance, this should be done only when saving the data into the database, not when
			 * retrieving data from it.
			 */
			$valid_translations = array();

			foreach ( $translations as $lang_slug => $tr_id ) {
				$tr_lang = $this->get_language( $tr_id );

				if ( ! empty( $tr_lang ) && $tr_lang->slug === $lang_slug ) {
					$valid_translations[ $lang_slug ] = $tr_id;
				}
			}

			$translations = $valid_translations;
		}

		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return $translations;
		}

		// Make sure to return at least the passed object in its translation array.
		$lang = $this->get_language( $id );

		if ( empty( $lang ) ) {
			return $translations;
		}

		return array_merge( array( $lang->slug => $id ), $translations );
	}
}
