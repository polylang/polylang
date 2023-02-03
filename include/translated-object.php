<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to use for object types that support translations.
 *
 * @since 1.8
 */
abstract class PLL_Translated_Object extends PLL_Translatable_Object {

	/**
	 * Taxonomy name for the translation groups.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	protected $tax_translations;

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
				'update_count_callback' => '_update_generic_term_count', // Count *all* objects to correctly detect unused terms.
			)
		);
	}

	/**
	 * Returns the translations group taxonomy name.
	 *
	 * @since 3.4
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_translations() {
		return $this->tax_translations;
	}

	/**
	 * Assigns a language to an object, taking care of the translations group.
	 *
	 * @since 3.4
	 *
	 * @param int                     $id   Object ID.
	 * @param PLL_Language|string|int $lang Language to assign to the object.
	 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
	 *              the object).
	 */
	public function set_language( $id, $lang ) {
		if ( ! parent::set_language( $id, $lang ) ) {
			return false;
		}

		$id = $this->sanitize_int_id( $id );

		$translations = $this->get_translations( $id );

		// Don't create translation groups with only 1 value.
		if ( ! empty( $translations ) ) {
			// Remove the object's former language from the new translations group before adding the new value.
			$translations = array_diff( $translations, array( $id ) );
			$this->save_translations( $id, $translations );
		}

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

		// Lang slugs as array keys, translation IDs as array values.
		$translations = maybe_unserialize( $translations_term->description );
		$translations = is_array( $translations ) ? $translations : array();

		return $this->validate_translations( $translations, 0, 'display' );
	}

	/**
	 * Saves the object's translations.
	 *
	 * @since 0.5
	 *
	 * @param int   $id           Object ID.
	 * @param int[] $translations An associative array of translations with language code as key and translation ID as value.
	 * @return int[] An associative array with language codes as key and object IDs as values.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
	 */
	public function save_translations( $id, array $translations = array() ) {
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
	 * Returns an array of valid translations of an object.
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

		$translations = $this->get_raw_translations( $id );

		return $this->validate_translations( $translations, $id, 'display' );
	}

	/**
	 * Returns an unvalidated array of translations of an object.
	 * It is generally preferable to use `get_translations()`.
	 *
	 * @since 3.4
	 *
	 * @param int $id Object ID.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
	 */
	public function get_raw_translations( $id ) {
		$id = $this->sanitize_int_id( $id );

		if ( empty( $id ) ) {
			return array();
		}

		$term = $this->get_object_term( $id, $this->tax_translations );

		if ( empty( $term->description ) ) {
			return array();
		}

		$translations = maybe_unserialize( $term->description );
		$translations = is_array( $translations ) ? $translations : array();

		return $translations;
	}

	/**
	 * Returns the ID of the translation of an object.
	 *
	 * @since 0.5
	 *
	 * @param int                 $id   Object ID.
	 * @param PLL_Language|string $lang Language (slug or object).
	 * @return int|false Object ID of the translation, `false` if there is none.
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
	 * @since 3.4 Returns 0 instead of false.
	 *
	 * @param int                     $id   Object ID.
	 * @param PLL_Language|string|int $lang Language (object, slug, or term ID).
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
	 * @phpstan-param non-empty-string $context
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

		/** @phpstan-var array<non-empty-string, positive-int> $translations */
		return array_merge( array( $lang->slug => $id ), $translations );
	}
	/**
	 * Creates translations groups in mass.
	 *
	 * @since 1.6.3
	 * @since 3.4 Moved from PLL_Admin_Model class.
	 *
	 * @param int[][] $translations Array of translations arrays.
	 * @return void
	 *
	 * @phpstan-param array<array<string,int>> $translations
	 */
	public function set_translation_in_mass( $translations ) {
		global $wpdb;

		$terms       = array();
		$slugs       = array();
		$description = array();
		$count       = array();

		foreach ( $translations as $t ) {
			$term = uniqid( 'pll_' ); // the term name
			$terms[] = $wpdb->prepare( '( %s, %s )', $term, $term );
			$slugs[] = $wpdb->prepare( '%s', $term );
			$description[ $term ] = maybe_serialize( $t );
			$count[ $term ] = count( $t );
		}

		// Insert terms
		if ( ! empty( $terms ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->terms} ( slug, name ) VALUES " . implode( ',', array_unique( $terms ) ) );
		}

		// Get all terms with their term_id
		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$terms    = $wpdb->get_results( "SELECT term_id, slug FROM {$wpdb->terms} WHERE slug IN ( " . implode( ',', $slugs ) . ' )' );
		$term_ids = array();
		$tts      = array();

		// Prepare terms taxonomy relationship
		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id;
			$tts[] = $wpdb->prepare( '( %d, %s, %s, %d )', $term->term_id, $this->tax_translations, $description[ $term->slug ], $count[ $term->slug ] );
		}

		// Insert term_taxonomy
		if ( ! empty( $tts ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->term_taxonomy} ( term_id, taxonomy, description, count ) VALUES " . implode( ',', array_unique( $tts ) ) );
		}

		// Get all terms with term_taxonomy_id
		$terms = get_terms( array( 'taxonomy' => $this->tax_translations, 'hide_empty' => false ) );
		$trs   = array();

		// Prepare objects relationships.
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$t = maybe_unserialize( $term->description );
				if ( is_array( $t ) && in_array( $t, $translations ) ) {
					foreach ( $t as $object_id ) {
						if ( ! empty( $object_id ) ) {
							$trs[] = $wpdb->prepare( '( %d, %d )', $object_id, $term->term_taxonomy_id );
						}
					}
				}
			}
		}

		// Insert term_relationships
		if ( ! empty( $trs ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->term_relationships} ( object_id, term_taxonomy_id ) VALUES " . implode( ',', $trs ) );
			$trs = array_unique( $trs );
		}

		clean_term_cache( $term_ids, $this->tax_translations );
	}
}
