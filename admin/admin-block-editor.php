<?php

/**
 * Manages filters and actions related to the block editor
 *
 * @since 2.5
 */
class PLL_Admin_Block_Editor {

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 2.5
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		add_filter( 'block_editor_preload_paths', array( $this, 'preload_paths' ), 10, 2 );
	}

	/**
	 * Filter the preload REST requests by the current language of the post
	 * Necessary otherwise subsequent REST requests all filtered by the language
	 * would not hit the preloaded requests
	 *
	 * @since 2.5
	 *
	 * @param array  $preload_paths Array of paths to preload.
	 * @param object $post          The post resource data.
	 * @return array
	 */
	public function preload_paths( $preload_paths, $post ) {
		$lang = pll_get_post_language( $post->ID );

		foreach ( $preload_paths as $k => $path ) {
			if ( is_string( $path ) && '/' !== $path ) {
				$preload_paths[ $k ] = $path . "&lang={$lang}";
			}
		}

		return $preload_paths;
	}
}
