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
	 * @var PLL_CRUD_Posts
	 */
	protected $posts;

	/**
	 * @var PLL_Filter_REST_Routes
	 */
	protected $filter_rest_routes;

	/**
	 * Constructor: setups filters and actions.
	 *
	 * @since 2.5
	 *
	 * @param PLL_Admin $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->posts = &$polylang->posts;

		$this->filter_rest_routes = &$polylang->filter_rest_routes;

		add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'filter_preload_paths' ), 50, 2 );
	}

	/**
	 * Filters preload paths based on the context (block editor for posts, site editor or widget editor for instance).
	 *
	 * @since 3.5
	 *
	 * @param array                   $preload_paths Preload paths.
	 * @param WP_Block_Editor_Context $context       Editor context.
	 * @return array Filtered preload paths.
	 */
	public function filter_preload_paths( $preload_paths, $context ) {
		if ( ! $context instanceof WP_Block_Editor_Context ) {
			return $preload_paths;
		}

		// Backward compatibility with WP < 6.0 where `WP_Block_Editor_Context::$name` doesn't exist yet.
		if (
			( property_exists( $context, 'name' ) && 'core/edit-post' !== $context->name )
			|| ! $context->post instanceof WP_Post
		) {
			// Do nothing if not post editor.
			return $preload_paths;
		}

		if ( ! $this->model->is_translated_post_type( $context->post->post_type ) ) {
			return $preload_paths;
		}

		// Set default language according to the context if no language is defined yet.
		$this->posts->set_default_language( $context->post->ID );
		$language = $this->model->post->get_language( $context->post->ID );

		if ( empty( $language ) ) {
			return $preload_paths;
		}

		return $this->filter_rest_routes->add_query_parameters(
			$preload_paths,
			array(
				'lang' => $language->slug,
			)
		);
	}
}
