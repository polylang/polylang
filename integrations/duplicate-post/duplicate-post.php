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
	}

	/**
	 * Duplicate Post
	 * Avoid duplicating the 'post_translations' taxonomy
	 *
	 * @since 1.8
	 *
	 * @param array|string $taxonomies
	 * @return array
	 */
	public function duplicate_post_taxonomies_blacklist( $taxonomies ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(); // As we get an empty string when there is no taxonomy
		}

		$taxonomies[] = 'post_translations';
		return $taxonomies;
	}
}
