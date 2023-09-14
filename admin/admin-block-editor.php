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
	 * @var PLL_Filter_REST_Routes
	 */
	public $filter_rest_routes;

	/**
	 * Constructor: setups filters and actions.
	 *
	 * @since 2.5
	 *
	 * @param PLL_Admin $polylang The Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->model              = &$polylang->model;
		$this->filter_rest_routes = new PLL_Filter_REST_Routes( $polylang->model );

		add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'filter_preload_paths' ), 50, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_block_editor_inline_script' ), 15 ); // After `PLL_Admin_Base::admin_enqueue_scripts()` to ensure `pll_block-editor`script is enqueued.
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

	/**
	 * Adds inline block editor script for filterable REST routes.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function add_block_editor_inline_script() {
		$handle = 'pll_block-editor';

		if ( wp_script_is( $handle, 'enqueued' ) ) {
			$this->filter_rest_routes->add_inline_script( $handle );
		}
	}
}
