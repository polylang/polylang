<?php
/**
 * @package Polylang
 */

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
	 * @param object $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ) );
		add_filter( 'use_block_editor_for_post', array( $this, 'new_post_translation' ), 5000 ); // After content duplication.
	}

	/**
	 * Translates the post parent if it exists when using "Add new" (translation).
	 *
	 * @since 0.6
	 *
	 * @param int   $post_parent Post parent ID.
	 * @param int   $post_id     Post ID, unused.
	 * @param array $postarr     Array of parsed post data.
	 * @return int
	 */
	public function wp_insert_post_parent( $post_parent, $post_id, $postarr ) {
		$context_data = $this->get_data_from_request( $postarr );

		if ( empty( $context_data ) ) {
			return $post_parent;
		}

		// Make sure not to impact media translations created at the same time.
		$parent_id = wp_get_post_parent_id( $context_data['from_post_id'] );

		if ( empty( $parent_id ) ) {
			return $post_parent;
		}

		$tr_parent = $this->model->post->get_translation( $parent_id, $context_data['new_lang'] );

		if ( empty( $tr_parent ) ) {
			return $post_parent;
		}

		return $tr_parent;
	}

	/**
	 * Copies menu order, comment, ping status and optionally the date when creating a new translation.
	 *
	 * @since 2.5
	 *
	 * @param array $data An array of slashed post data.
	 * @return array
	 */
	public function wp_insert_post_data( $data ) {
		$context_data = $this->get_data_from_request( $data );

		if ( empty( $context_data ) ) {
			return $data;
		}

		$from_post = get_post( $context_data['from_post_id'] );

		if ( ! $from_post instanceof WP_Post ) {
			return $data;
		}

		foreach ( array( 'menu_order', 'comment_status', 'ping_status' ) as $property ) {
			$data[ $property ] = $from_post->$property;
		}

		// Copy the date only if the synchronization is activated.
		if ( in_array( 'post_date', $this->options['sync'], true ) ) {
			$data['post_date']     = $from_post->post_date;
			$data['post_date_gmt'] = $from_post->post_date_gmt;
		}

		return $data;
	}

	/**
	 * Copies post metas and taxonomies when using "Add new" (translation).
	 *
	 * @since 2.5
	 * @since 3.1 Use of use_block_editor_for_post filter instead of rest_api_init which is triggered too early in WP 5.8.
	 *
	 * @param bool $is_block_editor Whether the post can be edited or not.
	 * @return bool
	 */
	public function new_post_translation( $is_block_editor ) {
		global $post;
		static $done = array();

		$context_data = $this->get_data_from_request( (array) $post );

		if ( empty( $context_data ) || ! empty( $done[ $context_data['from_post_id'] ] ) ) {
			return $is_block_editor;
		}

		$lang = $this->model->get_language( $context_data['new_lang'] );

		if ( empty( $lang ) ) {
			return $is_block_editor;
		}

		$done[ $context_data['from_post_id'] ] = true; // Avoid a second duplication in the block editor. Using an array only to allow multiple phpunit tests.

		$this->taxonomies->copy( $context_data['from_post_id'], $post->ID, $lang->slug );
		$this->post_metas->copy( $context_data['from_post_id'], $post->ID, $lang->slug );

		if ( is_sticky( $context_data['from_post_id'] ) ) {
			stick_post( $post->ID );
		}

		return $is_block_editor;
	}

	/**
	 * Get post fields to synchronize.
	 *
	 * @since 2.4
	 *
	 * @param WP_Post $post Post object.
	 * @return array Fields to synchronize.
	 */
	protected function get_fields_to_sync( $post ) {
		global $wpdb;

		$postarr      = parent::get_fields_to_sync( $post );
		$context_data = $this->get_data_from_request( (array) $post );

		// For new drafts, save the date now otherwise it is overridden by WP. Thanks to JoryHogeveen. See #32.
		if ( ! empty( $context_data ) && in_array( 'post_date', $this->options['sync'], true ) ) {
			unset( $postarr['post_date'] );
			unset( $postarr['post_date_gmt'] );

			$original = get_post( $context_data['from_post_id'] );

			if ( $original instanceof WP_Post ) {
				$wpdb->update(
					$wpdb->posts,
					array(
						'post_date'     => $original->post_date,
						'post_date_gmt' => $original->post_date_gmt,
					),
					array( 'ID' => $post->ID )
				);
			}
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		} elseif ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// 2nd case for quick edit.
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Make sure not to impact media translations when creating them at the same time as post
		if ( in_array( 'post_parent', $this->options['sync'], true ) && ( ! isset( $post_type ) || $post_type !== $post->post_type ) ) {
			unset( $postarr['post_parent'] );
		}

		return $postarr;
	}

	/**
	 * Synchronizes post fields in translations.
	 *
	 * @since 1.2
	 *
	 * @param int     $post_id      Post id.
	 * @param WP_Post $post         Post object.
	 * @param int[]   $translations Post translations.
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
	 * @return mixed|void
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

	/**
	 * Returns some data (`from_post_id` and `new_lang`) from the current request.
	 *
	 * @since 3.7
	 *
	 * @param array $post_data A post array.
	 * @return array
	 *
	 * @phpstan-return array{}|array{from_post_id: int<0,max>, new_lang: string}|never
	 */
	public static function get_data_from_request( array $post_data ): array {
		if ( ! isset( $GLOBALS['pagenow'], $_GET['_wpnonce'], $_GET['from_post'], $_GET['new_lang'], $_GET['post_type'], $post_data['post_type'] ) ) {
			return array();
		}

		if ( 'post-new.php' !== $GLOBALS['pagenow'] ) {
			return array();
		}

		if ( $post_data['post_type'] !== $_GET['post_type'] || ! pll_is_translated_post_type( $post_data['post_type'] ) ) {
			return array();
		}

		// Capability check already done in post-new.php.
		check_admin_referer( 'new-post-translation' );
		return array(
			'from_post_id' => $_GET['from_post'] >= 1 ? abs( (int) $_GET['from_post'] ) : 0,
			'new_lang'     => sanitize_key( $_GET['new_lang'] ),
		);
	}
}
