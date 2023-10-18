<?php

class Preload_Paths_Test extends PLL_Preload_Paths_TestCase {
	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the path should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	public function test_preload_paths_with_post_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		if ( $is_translatable ) {
			$this->pll_admin->model->post->set_language( $post->ID, $language );
		}

		$context       = $this->get_context( 'core/edit-post', $post );
		$filtered_path = $this->get_preload_paths( array( $path ), $context );

		if ( $is_translatable ) {
			$this->assertSame( $language, $this->pll_admin->model->post->get_language( $post->ID )->slug, "Post language should be set to {$language}." );
		} else {
			$this->assertFalse( $this->pll_admin->model->post->get_language( $post->ID ), 'Post is untranslatable and shouldn\'t have a language set.' );
		}

		$this->assert_path_added( array( $path ), $filtered_path, array(), 'There should not be added path.' );

		// A path could be an array containing the proper path and the method.
		$filtered_path = reset( $filtered_path );
		$filtered_path = is_array( $filtered_path ) ? reset( $filtered_path ) : $filtered_path;
		$expected_path = is_array( $path ) ? reset( $path ) : $path;

		if ( $is_filtered && $is_translatable ) {
			$this->assertStringContainsString( "lang={$language}", $filtered_path, "{$expected_path} should have the language parameter added." );
		} else {
			$this->assertStringNotContainsString( "lang={$language}", $filtered_path, "{$expected_path} should not have the language parameter added." );
		}
	}

	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the path should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	public function test_preload_paths_in_site_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		$this->assert_unfiltered_path_for_context( $path, 'core/edit-site' );
	}

	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the path should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	public function test_preload_paths_in_widget_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		$this->assert_unfiltered_path_for_context( $path, 'core/edit-widgets' );
	}

	public function test_preload_path_for_translatable_media() {
		$this->pll_admin->options['media_support'] = 1;
		$post = $this->factory()->post->create_and_get();
		$this->pll_admin->model->post->set_language( $post->ID, 'en' );
		$media_path = array(
			'raw' => array(
				array(
					0 => '/wp/v2/media',
					1 => 'OPTIONS',
				),
			),
			'expected' => array(
				array(
					0 => '/wp/v2/media?lang=en',
					1 => 'OPTIONS',
				),
			),
		);

		$this->assertSameSets(
			$media_path['expected'],
			$this->get_preload_paths( $media_path['raw'], $this->get_context( 'core/edit-post', $post ) ),
			'Media path should be filtered by language when option is activated.'
		);
	}

	/**
	 * @ticket #1861 {@see https://github.com/polylang/polylang-pro/issues/1861}
	 */
	public function test_rest_routes_for_custom_taxonomies() {
		register_post_type( 'book' );

		register_taxonomy(
			'genre',
			'book',
			array(
				'show_in_rest' => true,
			)
		);

		$this->pll_admin->model->cache->clean( 'post_types' );
		$this->pll_admin->model->cache->clean( 'taxonomies' );

		add_filter(
			'pll_get_post_types',
			function ( $post_types ) {
				return array_merge( $post_types, array( 'book' => 'book' ) );
			}
		);

		add_filter(
			'pll_get_taxonomies',
			function ( $taxonomies ) {
				return array_merge( $taxonomies, array( 'genre' => 'genre' ) );
			}
		);

		$filter_route = new ReflectionClass( PLL_Filter_REST_Routes::class );
		$get_method   = $filter_route->getMethod( 'get' );
		$get_method->setAccessible( true );

		$routes = $get_method->invokeArgs( new PLL_Filter_REST_Routes( $this->pll_admin->model ), array( ) );

		$this->assertArrayHasKey( 'genre', $routes );
		$this->assertSame( 'wp/v2/genre', $routes['genre'] );
	}
}
