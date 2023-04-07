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
	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Preferred language to assign to a new post.
	 *
	 * @var PLL_Language|null
	 */
	protected $pref_lang;

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

		add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'preload_paths' ), 10, 2 );
	}

	/**
	 * Filters the preload REST requests by the current language of the post.
	 * Necessary otherwise subsequent REST requests, all filtered by the language,
	 * would not hit the preloaded requests.
	 *
	 * @since 2.5
	 *
	 * @param (string|string[])[]     $preload_paths Array of paths to preload.
	 * @param WP_Block_Editor_Context $context       Block editor context.
	 * @return (string|string[])[]
	 */
	public function preload_paths( $preload_paths, $context ) {
		if (
			$context instanceof WP_Block_Editor_Context
			&& $context->post instanceof WP_Post
			&& $this->model->is_translated_post_type( $context->post->post_type )
		) {
			$lang = $this->model->post->get_language( $context->post->ID );

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
