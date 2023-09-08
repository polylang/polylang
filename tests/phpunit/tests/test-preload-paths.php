<?php

class Preload_Paths_Test extends PLL_Preload_Paths_TestCase {
	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the pass should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug, empty if none.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	public function test_preaload_paths_in_site_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		$this->assert_unfiltered_path_for_context( $path, 'core/edit-site' );
	}

	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the pass should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug, empty if none.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	public function test_preaload_paths_in_widget_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		$this->assert_unfiltered_path_for_context( $path, 'core/edit-widgets' );
	}

	/**
	 * Asserts the output path has no added route in any context.
	 *
	 * @param array                   $input_path    Input path.
	 * @param array                   $output_path   Output path to test.
	 * @param bool                    $is_filterable Whether or not the input path is filterable in the first place.
	 * @param WP_Block_Editor_Context $context       The context of the preload path.
	 * @return void
	 */
	protected function assert_path_added( $path, $filtered_path, $is_filterable, $context ) {
		$this->assertCount( count( array( $path ) ), $filtered_path, 'There should be any path added.' );
	}
}
