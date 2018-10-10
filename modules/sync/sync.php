<?php

/**
 * Manages copy and synchronization of terms and post metas on front
 *
 * @since 2.4
 */
class PLL_Sync {
	public $taxonomies, $post_metas, $term_meta;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model   = &$polylang->model;
		$this->options = &$polylang->options;

		$this->taxonomies = new PLL_Sync_Tax( $polylang );
		$this->post_metas = new PLL_Sync_Post_Metas( $polylang );
		$this->term_metas = new PLL_Sync_Term_Metas( $polylang );

		add_action( 'pll_save_post', array( $this, 'pll_save_post' ), 10, 3 );
		add_action( 'pll_save_term', array( $this, 'sync_term_parent' ), 10, 3 );

		add_action( 'pll_duplicate_term', array( $this->term_metas, 'copy' ), 10, 3 );

		if ( $this->options['media_support'] ) {
			add_action( 'pll_translate_media', array( $this->taxonomies, 'copy' ), 10, 3 );
			add_action( 'pll_translate_media', array( $this->post_metas, 'copy' ), 10, 3 );
			add_action( 'edit_attachment', array( $this, 'edit_attachment' ) );
		}

		add_filter( 'pre_update_option_sticky_posts', array( $this, 'sync_sticky_posts' ), 10, 2 );
	}

	/**
	 * Get post fields to synchornize
	 *
	 * @since 2.4
	 *
	 * @param object $post Post object
	 * @return array
	 */
	protected function get_fields_to_sync( $post ) {
		$postarr = array();

		foreach ( array( 'comment_status', 'ping_status', 'menu_order' ) as $property ) {
			if ( in_array( $property, $this->options['sync'] ) ) {
				$postarr[ $property ] = $post->$property;
			}
		}

		if ( in_array( 'post_date', $this->options['sync'] ) ) {
			$postarr['post_date']     = $post->post_date;
			$postarr['post_date_gmt'] = $post->post_date_gmt;
		}

		if ( in_array( 'post_parent', $this->options['sync'] ) ) {
			$postarr['post_parent'] = wp_get_post_parent_id( $post->ID );
		}

		return $postarr;
	}

	/**
	 * Synchronizes post fields in translations
	 *
	 * @since 2.4
	 *
	 * @param int    $post_id      post id
	 * @param object $post         post object
	 * @param array  $translations post translations
	 */
	public function pll_save_post( $post_id, $post, $translations ) {
		global $wpdb;

		$postarr = $this->get_fields_to_sync( $post );

		if ( ! empty( $postarr ) ) {
			foreach ( $translations as $lang => $tr_id ) {
				if ( ! $tr_id || $tr_id === $post_id ) {
					continue;
				}

				$tr_arr = $postarr;
				unset( $tr_arr['post_parent'] );

				// Do not udpate the translation parent if the user set a parent with no translation
				if ( isset( $postarr['post_parent'] ) ) {
					$post_parent = $postarr['post_parent'] ? $this->model->post->get_translation( $postarr['post_parent'], $lang ) : 0;
					if ( ! ( $postarr['post_parent'] && ! $post_parent ) ) {
						$tr_arr['post_parent'] = $post_parent;
					}
				}

				// Update all the row at once
				// Don't use wp_update_post to avoid infinite loop
				$wpdb->update( $wpdb->posts, $tr_arr, array( 'ID' => $tr_id ) );
				clean_post_cache( $tr_id );
			}
		}
	}

	/**
	 * Synchronize term parent in translations
	 * Calling clean_term_cache *after* this is mandatory otherwise the $taxonomy_children option is not correctly updated
	 * Before WP 3.9 clean_term_cache could be called ( efficiently ) only one time due to static array which prevented to update the option more than once
	 * This is the reason to use the edit_term filter and not edited_term
	 *
	 * @since 2.3
	 *
	 * @param int    $term_id      Term id
	 * @param string $taxonomy     Taxonomy name
	 * @param array  $translations The list of translations term ids
	 */
	public function sync_term_parent( $term_id, $taxonomy, $translations ) {
		global $wpdb;

		if ( is_taxonomy_hierarchical( $taxonomy ) && $this->model->is_translated_taxonomy( $taxonomy ) ) {
			$term = get_term( $term_id );

			foreach ( $translations as $lang => $tr_id ) {
				if ( ! empty( $tr_id ) && $tr_id !== $term_id && $tr_parent = $this->model->term->get_translation( $term->parent, $lang ) ) {
					$wpdb->update(
						$wpdb->term_taxonomy,
						array( 'parent' => isset( $tr_parent ) ? $tr_parent : 0 ),
						array( 'term_taxonomy_id' => get_term( (int) $tr_id, $taxonomy )->term_taxonomy_id )
					);

					clean_term_cache( $tr_id, $taxonomy ); // OK since WP 3.9
				}
			}
		}
	}

	/**
	 * Synchronizes terms and metas in translations for media
	 *
	 * @since 1.8
	 *
	 * @param int $post_id post id
	 */
	public function edit_attachment( $post_id ) {
		$this->pll_save_post( $post_id, get_post( $post_id ), $this->model->post->get_translations( $post_id ) );
	}

	/**
	 * Synchronize sticky posts
	 *
	 * @since 2.3
	 *
	 * @param array $value     New option value
	 * @param array $old_value Old option value
	 * @return array
	 */
	public function sync_sticky_posts( $value, $old_value ) {
		if ( in_array( 'sticky_posts', $this->options['sync'] ) ) {
			// Stick post
			if ( $sticked = array_diff( $value, $old_value ) ) {
				$translations = $this->model->post->get_translations( reset( $sticked ) );
				$value        = array_unique( array_merge( $value, array_values( $translations ) ) );
			}

			// Unstick post
			if ( $unsticked = array_diff( $old_value, $value ) ) {
				$translations = $this->model->post->get_translations( reset( $unsticked ) );
				$value        = array_unique( array_diff( $value, array_values( $translations ) ) );
			}
		}

		return $value;
	}
}
