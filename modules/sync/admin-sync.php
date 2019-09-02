<?php

/**
 * Manages copy and synchronization of terms and post metas
 *
 * @since 1.2
 */
class PLL_Admin_Sync extends PLL_Sync {

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ) );
		add_action( 'rest_api_init', array( $this, 'new_post_translation' ) ); // Block editor
		add_action( 'add_meta_boxes', array( $this, 'new_post_translation' ), 5 ); // Classic editor, before Types which populates custom fields in same hook with priority 10
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
		if ( isset( $_GET['from_post'], $_GET['new_lang'], $_GET['post_type'] ) ) {
			check_admin_referer( 'new-post-translation' );
			// Make sure not to impact media translations created at the same time
			if ( $_GET['post_type'] === $postarr['post_type'] && ( $id = wp_get_post_parent_id( (int) $_GET['from_post'] ) ) && $parent = $this->model->post->get_translation( $id, sanitize_key( $_GET['new_lang'] ) ) ) {
				$post_parent = $parent;
			}
		}
		return $post_parent;
	}

	/**
	 * Copy menu order, comment, ping status and optionally the date when creating a new tanslation
	 *
	 * @since 2.5
	 *
	 * @param array $data An array of slashed post data.
	 * @return array
	 */
	public function wp_insert_post_data( $data ) {
		if ( isset( $GLOBALS['pagenow'], $_GET['from_post'], $_GET['new_lang'] ) && 'post-new.php' === $GLOBALS['pagenow'] && $this->model->is_translated_post_type( $data['post_type'] ) ) {
			check_admin_referer( 'new-post-translation' );

			$from_post_id = (int) $_GET['from_post'];
			$from_post    = get_post( $from_post_id );

			foreach ( array( 'menu_order', 'comment_status', 'ping_status' ) as $property ) {
				$data[ $property ] = $from_post->$property;
			}

			// Copy the date only if the synchronization is activated
			if ( in_array( 'post_date', $this->options['sync'] ) ) {
				$data['post_date']     = $from_post->post_date;
				$data['post_date_gmt'] = $from_post->post_date_gmt;
			}
		}

		return $data;
	}

	/**
	 * Copy post metas, and taxonomies when using "Add new" ( translation )
	 *
	 * @since 2.5
	 */
	public function new_post_translation() {
		global $post;
		static $done = array();

		if ( isset( $GLOBALS['pagenow'], $_GET['from_post'], $_GET['new_lang'] ) && 'post-new.php' === $GLOBALS['pagenow'] && $this->model->is_translated_post_type( $post->post_type ) ) {
			check_admin_referer( 'new-post-translation' );

			// Capability check already done in post-new.php
			$from_post_id = (int) $_GET['from_post'];
			$lang         = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) );

			if ( ! $from_post_id || ! $lang || ! empty( $done[ $from_post_id ] ) ) {
				return;
			}

			$done[ $from_post_id ] = true; // Avoid a second duplication in the block editor. Using an array only to allow multiple phpunit tests.

			$this->taxonomies->copy( $from_post_id, $post->ID, $lang->slug );
			$this->post_metas->copy( $from_post_id, $post->ID, $lang->slug );

			if ( is_sticky( $from_post_id ) ) {
				stick_post( $post->ID );
			}
		}
	}

	/**
	 * Get post fields to synchronize
	 *
	 * @since 2.4
	 *
	 * @param object $post Post object
	 * @return array
	 */
	protected function get_fields_to_sync( $post ) {
		global $wpdb;

		$postarr = parent::get_fields_to_sync( $post );

		// For new drafts, save the date now otherwise it is overriden by WP. Thanks to JoryHogeveen. See #32.
		if ( in_array( 'post_date', $this->options['sync'] ) && isset( $GLOBALS['pagenow'], $_GET['from_post'], $_GET['new_lang'] ) && 'post-new.php' === $GLOBALS['pagenow'] ) {
			check_admin_referer( 'new-post-translation' );

			unset( $postarr['post_date'] );
			unset( $postarr['post_date_gmt'] );

			$original = get_post( (int) $_GET['from_post'] );
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_date'     => $original->post_date,
					'post_date_gmt' => $original->post_date_gmt,
				),
				array( 'ID' => $post->ID )
			);
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		} elseif ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // 2nd case for quick edit
		}

		// Make sure not to impact media translations when creating them at the same time as post
		if ( in_array( 'post_parent', $this->options['sync'] ) && ( ! isset( $post_type ) || $post_type !== $post->post_type ) ) {
			unset( $postarr['post_parent'] );
		}

		return $postarr;
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
		parent::pll_save_post( $post_id, $post, $translations );

		// Sticky posts
		if ( in_array( 'sticky_posts', $this->options['sync'] ) ) {
			$stickies = get_option( 'sticky_posts' );
			if ( isset( $_REQUEST['sticky'] ) && 'sticky' === $_REQUEST['sticky'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$stickies = array_merge( $stickies, array_values( $translations ) );
			} else {
				$stickies = array_diff( $stickies, array_values( $translations ) );
			}
			update_option( 'sticky_posts', array_unique( $stickies ) );
		}
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
				$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				$i = 1 + empty( $debug[1]['line'] ); // The file and line are in $debug[2] if the function was called using call_user_func

				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
					sprintf(
						'%1$s was called incorrectly in %3$s on line %4$s: the call to PLL()->sync->%1$s() has been deprecated in Polylang 2.3, use PLL()->sync->%2$s->copy() instead.' . "\nError handler",
						esc_html( $func ),
						esc_html( $obj ),
						esc_html( $debug[ $i ]['file'] ),
						absint( $debug[ $i ]['line'] )
					)
				);
			}
			return call_user_func_array( array( $this->$obj, 'copy' ), $args );
		}

		$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			sprintf(
				'Call to undefined function PLL()->sync->%1$s() in %2$s on line %3$s' . "\nError handler",
				esc_html( $func ),
				esc_html( $debug[0]['file'] ),
				absint( $debug[0]['line'] )
			),
			E_USER_ERROR
		);
	}
}
