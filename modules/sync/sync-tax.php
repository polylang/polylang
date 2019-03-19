<?php

/**
 * A class to manage the sychronization of taxonomy terms across posts translations
 *
 * @since 2.3
 */
class PLL_Sync_Tax {

	/**
	 * Constructor
	 *
	 * @since 2.3
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;

		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 5 );
		add_action( 'pll_save_term', array( $this, 'create_term' ), 10, 3 );
		add_action( 'pre_delete_term', array( $this, 'pre_delete_term' ) );
		add_action( 'delete_term', array( $this, 'delete_term' ) );
	}

	/**
	 * Get the list of taxonomies to copy or to synchronize
	 *
	 * @since 1.7
	 * @since 2.1 The `$from`, `$to`, `$lang` parameters were added.
	 *
	 * @param bool   $sync True if it is synchronization, false if it is a copy
	 * @param int    $from Id of the post from which we copy informations, optional, defaults to null
	 * @param int    $to   Id of the post to which we paste informations, optional, defaults to null
	 * @param string $lang Language slug, optional, defaults to null
	 * @return array List of taxonomy names
	 */
	protected function get_taxonomies_to_copy( $sync, $from = null, $to = null, $lang = null ) {
		$taxonomies = ! $sync || in_array( 'taxonomies', $this->options['sync'] ) ? $this->model->get_translated_taxonomies() : array();
		if ( ! $sync || in_array( 'post_format', $this->options['sync'] ) ) {
			$taxonomies[] = 'post_format';
		}

		/**
		 * Filter the taxonomies to copy or synchronize
		 *
		 * @since 1.7
		 * @since 2.1 The `$from`, `$to`, `$lang` parameters were added.
		 *
		 * @param array  $taxonomies List of taxonomy names
		 * @param bool   $sync       True if it is synchronization, false if it is a copy
		 * @param int    $from       Id of the post from which we copy informations
		 * @param int    $to         Id of the post to which we paste informations
		 * @param string $lang       Language slug
		 */
		return array_unique( apply_filters( 'pll_copy_taxonomies', $taxonomies, $sync, $from, $to, $lang ) );
	}

	/**
	 * When copying or synchronizing terms, translate terms in translatable taxonomies
	 *
	 * @since 2.3
	 *
	 * @param array  $object_id Object ID
	 * @param array  $terms     List of terms ids assigned to the source post
	 * @param string $taxonomy  Taxonomy name
	 * @param string $lang      Language slug
	 * @return array List of terms ids to assign to the target post
	 */
	protected function maybe_translate_terms( $object_id, $terms, $taxonomy, $lang ) {
		if ( is_array( $terms ) && $this->model->is_translated_taxonomy( $taxonomy ) ) {
			$newterms = array();

			// Convert to term ids if we got tag names
			$strings = array_map( 'is_string', $terms );
			if ( in_array( true, $strings, true ) ) {
				$terms = get_the_terms( $object_id, $taxonomy );
				$terms = wp_list_pluck( $terms, 'term_id' );
			}

			foreach ( $terms as $term ) {
				/**
				 * Filter the translated term when a post translation is created or synchronized
				 *
				 * @since 2.3
				 *
				 * @param int    $tr_term Translated term id
				 * @param int    $term    Source term id
				 * @param string $lang    Language slug
				 */
				if ( $term_id = apply_filters( 'pll_maybe_translate_term', $this->model->term->get_translation( $term, $lang ), $term, $lang ) ) {
					$newterms[] = (int) $term_id; // Cast is important otherwise we get 'numeric' tags
				}
			}

			return $newterms;
		}

		return $terms; // Empty $terms or untranslated taxonomy
	}

	/**
	 * When assigning terms to a post, assign translated terms to the translated posts (synchronisation)
	 *
	 * @since 2.3
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     An array of object terms.
	 * @param array  $tt_ids    An array of term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 * @param bool   $append    Whether to append new terms to the old terms.
	 */
	public function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append ) {
		static $avoid_recursion = false;
		$taxonomy_object = get_taxonomy( $taxonomy );

		// Make sure that the taxonomy is registered for a post type
		if ( ! $avoid_recursion && array_filter( $taxonomy_object->object_type, 'post_type_exists' ) ) {
			$avoid_recursion = true;

			$tr_ids = $this->model->post->get_translations( $object_id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id !== $object_id ) {
					$to_copy = $this->get_taxonomies_to_copy( true, $object_id, $tr_id, $lang );

					if ( in_array( $taxonomy, $to_copy ) ) {
						$newterms = $this->maybe_translate_terms( $object_id, $terms, $taxonomy, $lang );

						// For some reasons, the user may have untranslated terms in the translation. Don't forget them.
						if ( $this->model->is_translated_taxonomy( $taxonomy ) ) {
							$tr_terms = get_the_terms( $tr_id, $taxonomy );
							if ( is_array( $tr_terms ) ) {
								foreach ( $tr_terms as $term ) {
									if ( ! $this->model->term->get_translation( $term->term_id, $this->model->post->get_language( $object_id ) ) ) {
										$newterms[] = (int) $term->term_id;
									}
								}
							}
						}

						wp_set_object_terms( $tr_id, $newterms, $taxonomy, $append );
					}
				}
			}

			$avoid_recursion = false;
		}
	}

	/**
	 * Copy terms fron one post to a translation, does not sync
	 *
	 * @since 2.3
	 *
	 * @param int    $from  Id of the source post
	 * @param int    $to    Id of the target post
	 * @param string $lang  Language slug
	 */
	public function copy( $from, $to, $lang ) {
		remove_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 6 );

		// Get taxonomies to sync for this post type
		$taxonomies = array_intersect( get_post_taxonomies( $from ), $this->get_taxonomies_to_copy( false, $from, $to, $lang ) );

		// Update the term cache to reduce the number of queries in the loop
		update_object_term_cache( $from, get_post_type( $from ) );

		// Copy
		foreach ( $taxonomies as $tax ) {
			if ( $terms = get_the_terms( $from, $tax ) ) {
				$terms = array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) );
				$newterms = $this->maybe_translate_terms( $from, $terms, $tax, $lang );

				if ( ! empty( $newterms ) ) {
					wp_set_object_terms( $to, $newterms, $tax );
				}
			}
		}

		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 6 );
	}

	/**
	 * When creating a new term, associate it to posts having translations associated to the translated terms
	 *
	 * @since 2.3
	 *
	 * @param int    $term_id      Id of the created term
	 * @param string $taxonomy     Taxonomy
	 * @param array  $translations Ids of the translations of the created term
	 */
	public function create_term( $term_id, $taxonomy, $translations ) {
		if ( doing_action( 'create_term' ) && in_array( $taxonomy, $this->get_taxonomies_to_copy( true ) ) ) {
			// Get all posts associated to the translated terms
			$tr_posts = get_posts(
				array(
					'numberposts' => -1,
					'nopaging'    => true,
					'post_type'   => 'any',
					'post_status' => 'any',
					'fields'      => 'ids',
					'tax_query'   => array(
						array(
							'taxonomy'         => $taxonomy,
							'field'            => 'id',
							'terms'            => array_merge( array( $term_id ), array_values( $translations ) ),
							'include_children' => false,
						),
					),
				)
			);

			$lang = $this->model->term->get_language( $term_id ); // Language of the created term
			$posts = array();

			foreach ( $tr_posts as $post_id ) {
				$post = $this->model->post->get_translation( $post_id, $lang );

				if ( $post ) {
					$posts[] = $post;
				}
			}

			$posts = array_unique( $posts );

			foreach ( $posts as $post_id ) {
				wp_set_object_terms( $post_id, $term_id, $taxonomy, true );
			}
		}
	}

	/**
	 * Deactivate the synchronization of terms before deleting a term
	 * to avoid translated terms to be removed from translated posts
	 *
	 * @since 2.3.2
	 */
	public function pre_delete_term() {
		remove_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 5 );
	}

	/**
	 * Re-activate the synchronization of terms after a term is deleted
	 *
	 * @since 2.3.2
	 */
	public function delete_term() {
		add_action( 'set_object_terms', array( $this, 'set_object_terms' ), 10, 5 );
	}
}
