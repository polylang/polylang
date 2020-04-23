<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to the block editor
 *
 * @since 2.5
 */
class PLL_Admin_Block_Editor {
	public $model;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 2.5
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model     = &$polylang->model;
		$this->pref_lang = &$polylang->pref_lang;

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
		if ( $this->model->is_translated_post_type( $post->post_type ) ) {
			$lang = $this->model->post->get_language( $post->ID );

			if ( ! $lang ) {
				$lang = $this->pref_lang;
			}

			foreach ( $preload_paths as $k => $path ) {
				if ( is_string( $path ) && '/' !== $path ) {
					$preload_paths[ $k ] = $path . "&lang={$lang->slug}";
				}
			}
		}

		return $preload_paths;
	}
}
