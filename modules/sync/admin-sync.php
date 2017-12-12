<?php

/**
 * Manages copy and synchronization of terms and post metas
 *
 * @since 1.2
 */
class PLL_Admin_Sync {
	public $taxonomies, $post_metas, $term_meta;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;

		$this->taxonomies = new PLL_Sync_Tax( $polylang );
		$this->post_metas = new PLL_Sync_Post_Metas( $polylang );
		$this->term_metas = new PLL_Sync_Term_Metas( $polylang );

		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 3 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 5, 2 ); // Before Types which populates custom fields in same hook with priority 10

		add_action( 'pll_save_post', array( $this, 'pll_save_post' ), 10, 3 );
		add_action( 'pll_save_term', array( $this, 'sync_term_parent' ), 10, 3 );

		if ( $this->options['media_support'] ) {
			add_action( 'pll_translate_media', array( $this->taxonomies, 'copy' ), 10, 3 );
			add_action( 'pll_translate_media', array( $this->post_metas, 'copy' ), 10, 3 );
			add_action( 'edit_attachment', array( $this, 'edit_attachment' ) );
		}

		add_filter( 'pre_update_option_sticky_posts', array( $this, 'sync_sticky_posts' ), 10, 2 );
	}

	/**
	 * Translate post parent if exists when using "Add new" ( translation )
	 *
	 * @since 0.6
	 *
	 * @param int   $post_parent Post parent ID
	 * @param int   $post_id     Post ID, unused
	 * @param array $postarr     Array of parsed post data
	 * @return int
	 */
	public function wp_insert_post_parent( $post_parent, $post_id, $postarr ) {
		// Make sure not to impact media translations created at the same time
		return isset( $_GET['from_post'], $_GET['new_lang'], $_GET['post_type'] ) && $_GET['post_type'] === $postarr['post_type'] && ( $id = wp_get_post_parent_id( (int) $_GET['from_post'] ) ) && ( $parent = $this->model->post->get_translation( $id, $_GET['new_lang'] ) ) ? $parent : $post_parent;
	}

	/**
	 * Copy post metas, menu order, comment and ping status when using "Add new" ( translation )
	 * formerly used dbx_post_advanced deprecated in WP 3.7
	 *
	 * @since 1.2
	 *
	 * @param string $post_type unused
	 * @param object $post      current post object
	 */
	public function add_meta_boxes( $post_type, $post ) {
		if ( 'post-new.php' == $GLOBALS['pagenow'] && isset( $_GET['from_post'], $_GET['new_lang'] ) && $this->model->is_translated_post_type( $post->post_type ) ) {
			// Capability check already done in post-new.php
			$from_post_id = (int) $_GET['from_post'];
			$from_post = get_post( $from_post_id );
			$lang = $this->model->get_language( $_GET['new_lang'] );

			if ( ! $from_post || ! $lang ) {
				return;
			}

			$this->taxonomies->copy( $from_post_id, $post->ID, $lang->slug );
			$this->post_metas->copy( $from_post_id, $post->ID, $lang->slug );

			foreach ( array( 'menu_order', 'comment_status', 'ping_status' ) as $property ) {
				$post->$property = $from_post->$property;
			}

			// Copy the date only if the synchronization is activated
			if ( in_array( 'post_date', $this->options['sync'] ) ) {
				$post->post_date = $from_post->post_date;
				$post->post_date_gmt = $from_post->post_date_gmt;
			}

			if ( is_sticky( $from_post_id ) ) {
				stick_post( $post->ID );
			}
		}
	}

	/**
	 * Synchronizes post fields in translations
	 *
	 * @since 1.2
	 *
	 * @param int    $post_id      post id
	 * @param object $post         post object
	 * @param array  $translations post translations
	 */
	public function pll_save_post( $post_id, $post, $translations ) {
		global $wpdb;

		// Prepare properties to synchronize
		foreach ( array( 'comment_status', 'ping_status', 'menu_order' ) as $property ) {
			if ( in_array( $property, $this->options['sync'] ) ) {
				$postarr[ $property ] = $post->$property;
			}
		}

		if ( in_array( 'post_date', $this->options['sync'] ) ) {
			// For new drafts, save the date now otherwise it is overriden by WP. Thanks to JoryHogeveen. See #32.
			if ( 'post-new.php' === $GLOBALS['pagenow'] && isset( $_GET['from_post'], $_GET['new_lang'] ) ) {
				$original = get_post( (int) $_GET['from_post'] );
				$wpdb->update(
					$wpdb->posts, array(
						'post_date' => $original->post_date,
						'post_date_gmt' => $original->post_date_gmt,
					),
					array( 'ID' => $post_id )
				);
			} else {
				$postarr['post_date'] = $post->post_date;
				$postarr['post_date_gmt'] = $post->post_date_gmt;
			}
		}

		foreach ( $translations as $lang => $tr_id ) {
			if ( ! $tr_id || $tr_id === $post_id ) {
				continue;
			}

			// Add comment status, ping status, menu order... to synchronization
			$tr_arr = empty( $postarr ) ? array() : $postarr;

			if ( isset( $GLOBALS['post_type'] ) ) {
				$post_type = $GLOBALS['post_type'];
			} elseif ( isset( $_REQUEST['post_type'] ) ) {
				$post_type = $_REQUEST['post_type']; // 2nd case for quick edit
			}

			// Add post parent to synchronization
			// Make sure not to impact media translations when creating them at the same time as post
			// Do not udpate the translation parent if the user set a parent with no translation
			if ( in_array( 'post_parent', $this->options['sync'] ) && isset( $post_type ) && $post_type === $post->post_type ) {
				$post_parent = ( $parent_id = wp_get_post_parent_id( $post_id ) ) ? $this->model->post->get_translation( $parent_id, $lang ) : 0;
				if ( ! ( $parent_id && ! $post_parent ) ) {
					$tr_arr['post_parent'] = $post_parent;
				}
			}

			// Update all the row at once
			// Don't use wp_update_post to avoid infinite loop
			if ( ! empty( $tr_arr ) ) {
				$wpdb->update( $wpdb->posts, $tr_arr, array( 'ID' => $tr_id ) );
				clean_post_cache( $tr_id );
			}
		}

		// Sticky posts
		if ( in_array( 'sticky_posts', $this->options['sync'] ) ) {
			$stickies = get_option( 'sticky_posts' );
			if ( isset( $_REQUEST['sticky'] ) && 'sticky' === $_REQUEST['sticky'] ) {
				$stickies = array_merge( $stickies, array_values( $translations ) );
			} else {
				$stickies = array_diff( $stickies, array_values( $translations ) );
			}
			update_option( 'sticky_posts', array_unique( $stickies ) );
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
				if ( ! empty( $tr_id ) && $tr_id !== $term_id ) {
					$tr_parent = $this->model->term->get_translation( $term->parent, $lang );

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
				$value = array_unique( array_merge( $value, array_values( $translations ) ) );
			}

			// Unstick post
			if ( $unsticked = array_diff( $old_value, $value ) ) {
				$translations = $this->model->post->get_translations( reset( $unsticked ) );
				$value = array_unique( array_diff( $value, array_values( $translations ) ) );
			}
		}

		return $value;
	}

	/**
	 * Some backward compatibility with Polylang < 2.3
	 * allows to call PLL()->sync->copy_post_metas() and PLL()->sync->copy_taxonomies()
	 * used for example in Polylang for WooCommerce
	 * the compatibility is however only partial as the 4th argument $sync is lost
	 *
	 * @since 2.3
	 *
	 * @param string $func Function name
	 * @param array  $args Function arguments
	 */
	public function __call( $func, $args ) {
		$obj = substr( $func, 5 );

		if ( is_object( $this->$obj ) && method_exists( $this->$obj, 'copy' ) ) {
			if ( WP_DEBUG ) {
				$debug = debug_backtrace();
				$i = 1 + empty( $debug[1]['line'] ); // The file and line are in $debug[2] if the function was called using call_user_func

				trigger_error( sprintf(
					'%1$s was called incorrectly in %3$s on line %4$s: the call to PLL()->sync->%1$s() has been deprecated in Polylang 2.3, use PLL()->sync->%2$s->copy() instead.' . "\nError handler",
					$func, $obj, $debug[ $i ]['file'], $debug[ $i ]['line']
				) );
			}
			return call_user_func_array( array( $this->$obj, 'copy' ), $args );
		}

		$debug = debug_backtrace();
		trigger_error( sprintf( 'Call to undefined function PLL()->sync->%1$s() in %2$s on line %3$s' . "\nError handler", $func, $debug[0]['file'], $debug[0]['line'] ), E_USER_ERROR );
	}
}
