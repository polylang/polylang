<?php
/**
 * @package Polylang
 */

/**
 * Manages copy and synchronization of terms and post metas on front
 *
 * @since 2.4
 */
class PLL_Sync {
	/**
	 * @var PLL_Sync_Tax
	 */
	public $taxonomies;

	/**
	 * @var PLL_Sync_Post_Metas
	 */
	public $post_metas;

	/**
	 * @var PLL_Sync_Term_Metas
	 */
	public $term_metas;

	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model   = &$polylang->model;
		$this->options = &$polylang->options;

		$this->taxonomies = new PLL_Sync_Tax( $polylang );
		$this->post_metas = new PLL_Sync_Post_Metas( $polylang );
		$this->term_metas = new PLL_Sync_Term_Metas( $polylang );

		add_filter( 'wp_insert_post_parent', array( $this, 'can_sync_post_parent' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, 'can_sync_post_data' ), 10, 2 );

		add_action( 'pll_save_post', array( $this, 'pll_save_post' ), 10, 3 );
		add_action( 'created_term', array( $this, 'sync_term_parent' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'sync_term_parent' ), 10, 3 );

		add_action( 'pll_duplicate_term', array( $this->term_metas, 'copy' ), 10, 3 );

		if ( $this->options['media_support'] ) {
			add_action( 'pll_translate_media', array( $this->taxonomies, 'copy' ), 10, 3 );
			add_action( 'pll_translate_media', array( $this->post_metas, 'copy' ), 10, 3 );
			add_action( 'edit_attachment', array( $this, 'edit_attachment' ) );
		}

		add_filter( 'pre_update_option_sticky_posts', array( $this, 'sync_sticky_posts' ), 10, 2 );
	}

	/**
	 * Get post fields to synchronize.
	 *
	 * @since 2.4
	 *
	 * @param WP_Post $post Post object.
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
	 * Prevents synchronized post parent modification if the current user hasn't enough rights
	 *
	 * @since 2.6
	 *
	 * @param int   $post_parent Post parent ID
	 * @param int   $post_id     Post ID, unused
	 * @param array $postarr     Array of parsed post data
	 * @return int
	 */
	public function can_sync_post_parent( $post_parent, $post_id, $postarr ) {
		if ( ! empty( $postarr['ID'] ) && ! $this->model->post->current_user_can_synchronize( $postarr['ID'] ) ) {
			$tr_ids = $this->model->post->get_translations( $postarr['ID'] );
			foreach ( $tr_ids as $tr_id ) {
				if ( $tr_id !== $postarr['ID'] && $post = get_post( $tr_id ) ) {
					$post_parent = $post->post_parent;
					break;
				}
			}
		}
		return $post_parent;
	}

	/**
	 * Prevents synchronized post data modification if the current user hasn't enough rights
	 *
	 * @since 2.6
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 * @return array
	 */
	public function can_sync_post_data( $data, $postarr ) {
		if ( ! empty( $postarr['ID'] ) && ! $this->model->post->current_user_can_synchronize( $postarr['ID'] ) ) {
			foreach ( $this->model->post->get_translations( $postarr['ID'] ) as $tr_id ) {
				if ( $tr_id !== $postarr['ID'] && $post = get_post( $tr_id ) ) {
					$to_sync = $this->get_fields_to_sync( $post );
					$data = array_merge( $data, $to_sync );
					break;
				}
			}
		}
		return $data;
	}

	/**
	 * Synchronizes post fields in translations.
	 *
	 * @since 2.4
	 *
	 * @param int     $post_id      Post id.
	 * @param WP_Post $post         Post object.
	 * @param int[]   $translations Post translations.
	 * @return void
	 */
	public function pll_save_post( $post_id, $post, $translations ) {
		global $wpdb;

		if ( $this->model->post->current_user_can_synchronize( $post_id ) ) {
			$postarr = $this->get_fields_to_sync( $post );

			if ( ! empty( $postarr ) ) {
				foreach ( $translations as $lang => $tr_id ) {
					if ( ! $tr_id || $tr_id === $post_id ) {
						continue;
					}

					$tr_arr = $postarr;
					unset( $tr_arr['post_parent'] );

					// Do not udpate the translation parent if the user set a parent with no translation.
					if ( isset( $postarr['post_parent'] ) ) {
						$post_parent = $postarr['post_parent'] ? $this->model->post->get_translation( $postarr['post_parent'], $lang ) : 0;
						if ( ! ( $postarr['post_parent'] && ! $post_parent ) ) {
							$tr_arr['post_parent'] = $post_parent;
						}
					}

					// Update all the rows at once.
					if ( ! empty( $tr_arr ) ) {
						// Don't use wp_update_post to avoid infinite loop.
						$wpdb->update( $wpdb->posts, $tr_arr, array( 'ID' => $tr_id ) );
						clean_post_cache( $tr_id );
					}
				}
			}
		}
	}

	/**
	 * Synchronize term parent in translations
	 * Calling clean_term_cache *after* this is mandatory otherwise the $taxonomy_children option is not correctly updated
	 *
	 * @since 2.3
	 *
	 * @param int    $term_id  Term id.
	 * @param int    $tt_id    Term taxonomy id, not used.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public function sync_term_parent( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;

		if ( is_taxonomy_hierarchical( $taxonomy ) && $this->model->is_translated_taxonomy( $taxonomy ) ) {
			$term = get_term( $term_id );

			if ( $term instanceof WP_Term ) {
				$translations = $this->model->term->get_translations( $term_id );

				foreach ( $translations as $lang => $tr_id ) {
					if ( $tr_id !== $term_id ) {
						$tr_parent = $this->model->term->get_translation( $term->parent, $lang );
						$tr_term   = get_term( (int) $tr_id, $taxonomy );

						if ( $tr_term instanceof WP_Term && ! ( $term->parent && empty( $tr_parent ) ) ) {
							$wpdb->update(
								$wpdb->term_taxonomy,
								array( 'parent' => $tr_parent ? $tr_parent : 0 ),
								array( 'term_taxonomy_id' => $tr_term->term_taxonomy_id )
							);

							clean_term_cache( $tr_id, $taxonomy ); // OK since WP 3.9.
						}
					}
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
	 * @return void
	 */
	public function edit_attachment( $post_id ) {
		$this->pll_save_post( $post_id, get_post( $post_id ), $this->model->post->get_translations( $post_id ) );
	}

	/**
	 * Synchronize sticky posts.
	 *
	 * @since 2.3
	 *
	 * @param int[] $value     New option value.
	 * @param int[] $old_value Old option value.
	 * @return int[]
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
