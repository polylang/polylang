<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Block_Editor_Filter_Preload_Paths_Test
 */
class PLL_Block_Editor_Filter_Preload_Paths_Test extends PLL_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->spy = $this->getMockBuilder( stdClass::class )
			->setMethods( array( '__invoke' ) )
			->getMock();
	}

	public function test_invoke_filter_with_one_argument() {
		new PLL_Block_Editor_Filter_Preload_Paths( array( $this->spy, '__invoke' ) );

		$fail_if_more_than_one_argument = function (...$args) {
			if ( count( $args ) > 1) {
				$this->fail( 'Filter registered with one parameter is expected to be called with one argument.' );
			}
		};
		$this->spy->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback( $fail_if_more_than_one_argument	);

		if ( class_exists( WP_Block_Editor_Context::class ) ) {
			block_editor_rest_api_preload( array( '/' ), new WP_Block_Editor_Context( array( 'post' => new WP_Post( new stdClass() ) ) ) );
		} else {
			apply_filters( 'block_editor_preload_paths', array( '/' ), new WP_Post( new stdClass() ) );
		}
	}

	public function test_do_not_invoke_callback_with_no_post_in_context() {
		if ( ! class_exists( WP_Block_Editor_Context::class ) ) {
			$this->markTestSkipped( 'block_editor_preload_paths is not called without a WP_Post as argument' );
		}

		$this->spy->expects( $this->never() )
			->method( '__invoke' );

		new PLL_Block_Editor_Filter_Preload_Paths( array( $this->spy, '__invoke' ), 10, 2 );

		block_editor_rest_api_preload( array( '/' ), new WP_Block_Editor_Context() );
	}

	public function test_register_filter_with_custom_priority() {
		global $wp_filter;

		new PLL_Block_Editor_Filter_Preload_Paths( array( $this->spy, '__invoke' ), 50 );

		if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha', '<' ) ) {
			$this->arrayHasKey( 'block_editor_preload_paths', $wp_filter );
			$this->arrayHasKey( 50, $wp_filter['block_editor_preload_paths']['callbacks'] );
		} else {
			$this->arrayHasKey( 'block_editor_rest_api_preload_paths', $wp_filter );
			$this->arrayHasKey( 50, $wp_filter['block_editor_rest_api_preload_paths']['callbacks'] );
		}

	}

	public function test_transform_block_editor_context_into_related_post() {
		if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha', '<' ) ) {
			$this->markTestSkipped( 'This test needs WordPress version 5.8+' );
		} else {
			new PLL_Block_Editor_Filter_Preload_Paths( array( $this->spy, '__invoke' ), 10, 2 );

			$this->spy->expects( $this->once() )
				->method( '__invoke' )
				->with(
					$this->isType( 'array' ),
					$this->isInstanceOf( WP_Post::class )
				);

			block_editor_rest_api_preload( array( '/' ), new WP_Block_Editor_Context( array( 'post' => new WP_Post( new stdClass() ) ) ) );
		}
	}

	public function test_honor_backward_compatibility() {
		if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha', '>=' ) ) {
			$this->markTestSkipped( 'This test needs WordPress version < 5.8' );
		} else {
			new PLL_Block_Editor_Filter_Preload_Paths( array( $this->spy, '__invoke' ), 10, 2 );

			$this->spy->expects( $this->once() )
				->method( '__invoke' )
				->with(
					$this->isType( 'array' ),
					$this->isInstanceOf( WP_Post::class )
				);

			apply_filters( 'block_editor_preload_paths', array( '/' ), new WP_Post( new stdClass() ) );
		}
	}
}
