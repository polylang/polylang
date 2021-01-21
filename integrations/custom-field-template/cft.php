<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Custom Field Template.
 *
 * @since 2.8
 */
class PLL_Cft {
	/**
	 * Setups actions.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'cft_copy' ), 10, 2 );
	}

	/**
	 * Custom field template does check $_REQUEST['post'] to populate the custom fields values.
	 *
	 * @since 1.0.2
	 *
	 * @param string  $post_type Unused.
	 * @param WP_Post $post      Current post object.
	 */
	public function cft_copy( $post_type, $post ) {
		global $custom_field_template;
		if ( isset( $custom_field_template, $_REQUEST['from_post'], $_REQUEST['new_lang'] ) && ! empty( $post ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_REQUEST['post'] = $post->ID;
		}
	}
}
