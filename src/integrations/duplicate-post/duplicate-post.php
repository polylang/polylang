<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Duplicate Post.
 * Version tested: 4.7
 *
 * @since 2.8
 */
class PLL_Duplicate_Post {
	/**
	 * Setups actions.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_filter( 'option_duplicate_post_taxonomies_blacklist', array( $this, 'taxonomies_blacklist' ) );
		add_filter( 'pll_copy_post_metas', array( $this, 'exclude_post_metas' ) );
		add_filter( 'pll_sync_post_fields', array( $this, 'exclude_rewrite_copy_fields' ), 10, 2 );

		add_action( 'duplicate_post_after_rewriting', array( $this, 'after_rewriting' ), 20, 2 );
	}

	/**
	 * Avoid duplicating the 'post_translations' taxonomy.
	 *
	 * @since 1.8
	 *
	 * @param array|string $taxonomies The list of taxonomies not to duplicate.
	 * @return array
	 */
	public function taxonomies_blacklist( $taxonomies ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(); // As we get an empty string when there is no taxonomy.
		}

		$taxonomies[] = 'post_translations';
		return $taxonomies;
	}

	/**
	 * Prevents the status of a "Rewrite & Republish" copy from being synchronized to the translations.
	 *
	 * Duplicate Post gives its copy the translations group of the original post, so the translations are synchronized
	 * from it. They must not take its status: `dp-rewrite-republish` is internal to Duplicate Post and hides them from
	 * the posts list.
	 *
	 * @since 3.9
	 *
	 * @param string[] $fields  The post fields to synchronize.
	 * @param int      $post_id The ID of the post used as the synchronization source.
	 * @return string[]
	 */
	public function exclude_rewrite_copy_fields( $fields, $post_id ) {
		// Duplicate Post checks this meta the same way, see `Permissions_Helper::is_rewrite_and_republish_copy()`.
		if ( 1 === (int) get_post_meta( $post_id, '_dp_is_rewrite_republish_copy', true ) ) {
			unset( $fields['post_status'] );
		}

		return $fields;
	}

	/**
	 * Exclude Duplicate Post metas from the copy/synchronization.
	 *
	 * This avoids synchronized posts to be deleted when using "Rewrite & Republish".
	 *
	 * @since 3.9
	 *
	 * @param string[] $keys List of meta keys.
	 * @return string[]
	 */
	public function exclude_post_metas( $keys ): array {
		$to_remove = array(
			'_dp_original',
			'_dp_is_rewrite_republish_copy',
			'_dp_has_rewrite_republish_copy',
			'_dp_creation_date_gmt',
		);

		return array_diff( $keys, $to_remove );
	}

	/**
	 * Fixes the translations group just after a post is republished.
	 *
	 * @since 3.9
	 *
	 * @param int $copy_id The copy's ID.
	 * @param int $post_id The original post's ID.
	 * @return void
	 */
	public function after_rewriting( $copy_id, $post_id ): void {
		$language     = pll_get_post_language( $post_id );
		$translations = pll_get_post_translations( $post_id );

		$translations[ $language ] = $post_id;
		pll_save_post_translations( $translations );
	}
}
