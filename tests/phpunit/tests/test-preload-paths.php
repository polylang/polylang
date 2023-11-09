<?php

class Preload_Paths_Test extends PLL_Preload_Paths_TestCase {
	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|string[] $path         The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool            $is_filtered  Whether the path should be filtered or not.
	 * @param string          $context_post Says what type of post must be created for the context. Possible values are `translatable`, `translatable_cpt`, `untranslatable`.
	 * @param string          $language     The post's language slug.
	 */
	public function test_preload_paths_with_post_editor_context( $path, bool $is_filtered, string $context_post, string $language ) {
		list( $path, $post, $is_translatable ) = $this->make_data_concrete( $path, $is_filtered, $context_post );

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
	 * @param string|string[] $path         The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool            $is_filtered  Whether the path should be filtered or not.
	 * @param string          $context_post Says what type of post must be created for the context. Possible values are `translatable`, `translatable_cpt`, `untranslatable`.
	 */
	public function test_preload_paths_in_site_editor_context( $path, bool $is_filtered, string $context_post ) {
		list( $path ) = $this->make_data_concrete( $path, $is_filtered, $context_post );
		$this->assert_unfiltered_path_for_context( $path, 'core/edit-site' );
	}

	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|string[] $path         The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool            $is_filtered  Whether the path should be filtered or not.
	 * @param string          $context_post Says what type of post must be created for the context. Possible values are `translatable`, `translatable_cpt`, `untranslatable`.
	 */
	public function test_preload_paths_in_widget_editor_context( $path, bool $is_filtered, string $context_post ) {
		list( $path ) = $this->make_data_concrete( $path, $is_filtered, $context_post );
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
	 *
	 * @testWith [ "trtax" ]
	 *           [ "trtax_with_no_namespace" ]
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	public function test_rest_routes_for_custom_taxonomies( $taxonomy ) {
		$preload_paths = $this->pll_admin->block_editor->filter_rest_routes->add_query_parameters( array( '/wp/v2/' . $taxonomy ), array( 'test' => 'something' ) );

		// If the parameter is added to the route, this means that the route is one of the filterable routes.
		$this->assertNotEmpty( $preload_paths );
		$this->assertCount( 1, $preload_paths );
		$this->assertSame( '/wp/v2/' . $taxonomy . '?test=something', reset( $preload_paths ) );
	}
}
