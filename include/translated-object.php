<?php
/**
 * @package Polylang
 */

/**
 * Abstract class to use for object types that support translations.
 *
 * @since 1.8
 */
abstract class PLL_Translated_Object extends PLL_Object_With_Language implements PLL_Translated_Object_Interface {

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations;

	/**
	 * Object type to use when checking capabilities.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $type;

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Model $model Instance of `PLL_Model`.
	 */
	public function __construct( PLL_Model &$model ) {
		parent::__construct( $model );

		$this->tax_to_cache[] = $this->tax_translations;

		/*
		 * Register our taxonomy as soon as possible.
		 * This is early registration, not ready for rewrite rules as $wp_rewrite will be setup later.
		 */
		register_taxonomy(
			$this->tax_translations,
			(array) $this->object_type,
			array(
				'label'                 => false,
				'public'                => false,
				'query_var'             => false,
				'rewrite'               => false,
				'_pll'                  => true,
				'update_count_callback' => '_update_generic_term_count', // Count *all* objects to avoid deleting in clean_translations_terms.
			)
		);
	}

	/**
	 * Returns the translations group taxonomy name.
	 *
	 * @since 3.3
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_translations() {
		return $this->tax_translations;
	}

	/**
	 * Assigns a new language to an object, taking care of the translations group.
	 *
	 * @since 3.1
	 *
	 * @param int          $id   Object ID.
	 * @param PLL_Language $lang New language to assign to the object.
	 * @return bool True on success (or if the given language is already assigned to the object). False otherwise.
	 */
	public function update_language( $id, PLL_Language $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return false;
		}

		if ( $this->get_language( $id ) === $lang ) {
			return true;
		}

		$this->set_language( $id, $lang );

		$translations = $this->get_translations( $id );

		if ( empty( $translations ) ) {
			return true;
		}

		// Remove the object's former language from the new translations group.
		$translations = array_diff( $translations, array( $id ) );
		$this->save_translations( $id, $translations );

		return true;
	}

	/**
	 * Returns a list of object translations, given a `tax_translations` term ID.
	 *
	 * @since 3.2
	 *
	 * @param int $term_id A `tax_translations` term ID.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
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
	 * Saves translations for objects.
	 *
	 * @since 0.5
	 *
	 * @param int   $id           Object ID.
	 * @param int[] $translations An associative array of translations with language code as key and translation ID as value.
	 * @return int[] An associative array with language codes as key and object IDs as values.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
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

		foreach ( array_diff_assoc( $old_translations, $translations ) as $id ) {
			$this->delete_translation( $id );
		}

		// Check ID we need to create or update the translation group.
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
	 * Deletes a translation of an object.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object ID.
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
	 * Returns an array of translations of an object.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object ID.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
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
	 * Returns the ID of the translation of an object.
	 *
	 * @since 0.5
	 *
	 * @param int                 $id   Object ID.
	 * @param PLL_Language|string $lang Language (slug or object).
	 * @return int|false Object ID of the translation, false if there is none.
	 *
	 * @phpstan-return positive-int|false
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
	 * Among the object and its translations, returns the ID of the object which is in `$lang`.
	 *
	 * @since 0.1
	 * @since 3.3 Returns 0 instead of false.
	 *
	 * @param int                     $id   Object ID.
	 * @param int|string|PLL_Language $lang Language (term_id or slug or object).
	 * @return int The translation object ID if exists, otherwise the passed ID. `0` if the passed object has no language.
	 *
	 * @phpstan-return int<0, max>
	 */
	public function get( $id, $lang ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return 0;
		}

		$lang = $this->model->get_language( $lang );

		if ( empty( $lang ) ) {
			return 0;
		}

		$obj_lang = $this->get_language( $id );

		if ( empty( $obj_lang ) ) {
			return 0;
		}

		return $obj_lang->term_id === $lang->term_id ? $id : (int) $this->get_translation( $id, $lang );
	}

	/**
	 * Checks if a user can synchronize translations.
	 *
	 * @since 2.6
	 *
	 * @param int $id Object ID.
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
		 * @param int       $id    The synchronization source object ID.
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
	 * Tells whether a translation term must be updated.
	 *
	 * @since 2.3
	 *
	 * @param int   $id           Object ID.
	 * @param int[] $translations An associative array of translations with language code as key and translation ID as
	 *                            value. Make sure to sanitize this.
	 * @return bool
	 *
	 * @phpstan-param array<non-empty-string, positive-int> $translations
	 */
	protected function should_update_translation_group( $id, $translations ) {
		// Don't do anything if no translations have been added to the group.
		$old_translations = $this->get_translations( $id ); // Includes at least $id itself.
		return ! empty( array_diff_assoc( $translations, $old_translations ) );
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
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
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
		/** @phpstan-var array<non-empty-string, positive-int> $translations */
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

		/** @phpstan-var array<non-empty-string, positive-int> */
		return array_merge( array( $lang->slug => $id ), $translations );
	}
}
