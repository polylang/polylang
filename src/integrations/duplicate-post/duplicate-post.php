<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Duplicate Post.
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
