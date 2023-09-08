<?php

abstract class PLL_Preload_Paths_TestCase extends PLL_UnitTestCase {
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		register_post_type( 'custom', array( 'public' => true, 'show_in_rest' => true ) ); // Untranslatable CPT.
		register_post_type( 'trcpt', array( 'public' => true, 'show_in_rest' => true ) ); // Translated CPT.
		register_taxonomy( 'trtax', 'post', array( 'show_in_rest' => true ) ); // Translated custom taxonomy.

		$options = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
			),
			array(
				'post_types' => array(
					'trcpt' => 'trcpt',
				),
			),
			array(
				'taxonomies' => array(
					'trtax' => 'trtax',
				),
			)
		);
		$model           = new PLL_Admin_Model( $options );
		$links_model     = new PLL_Links_Default( $model );
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->pll_admin->init();
		$this->pll_admin->admin_block_editor = new PLL_Admin_Block_Editor( $this->pll_admin );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'custom' );
		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	/**
	 * Calls the system under test and returns its output.
	 *
	 * @param array $paths                     List of preload paths.
	 * @param WP_Block_Editor_Context $context Context of preload paths.
	 * @return mixed
	 */
	protected function call_sut( $paths, $context ) {
		return apply_filters( 'block_editor_rest_api_preload_paths', $paths, $context );
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
	public function test_preload_paths_with_post_editor_context( $path, $is_filtered, $post, $language, $is_translatable ) {
		if ( $is_translatable ) {
			if ( empty( $language ) ) {
				// Preferred language should be used in case the post doesn't have one yet.
				$language = 'fr';
				$this->pll_admin->pref_lang = $this->pll_admin->model->get_language( $language );
			} else {
				// Otherwise the post already have a language set.
				$this->pll_admin->model->post->set_language( $post->ID, $language );
			}
		}

		$context       = $this->get_context( 'core/edit-post', $post );
		$filtered_path = $this->call_sut( array( $path ), $context );

		if ( $is_translatable ) {
			$this->assertSame( $language, $this->pll_admin->model->post->get_language( $post->ID )->slug, "Post language should be set to {$language}." );
		} else {
			$this->assertFalse( $this->pll_admin->model->post->get_language( $post->ID ), 'Post is untranslatable and shouldn\'t have a language set.' );
		}

		$this->assert_path_added( $path, $filtered_path, $is_filtered && $is_translatable, $context );

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
			$this->call_sut( $media_path['raw'], $this->get_context( 'core/edit-post', $post ) ),
			'Media path should be filtered by language when option is activated.'
		);
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
	abstract public function test_preaload_paths_in_site_editor_context( $path, $is_filtered, $post, $language, $is_translatable );

	/**
	 * @dataProvider preload_paths_provider
	 *
	 * @param string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool         $is_filtered     Whether the pass should be filtered or not.
	 * @param WP_Post      $post            The post provided for the context.
	 * @param string       $language        The post's language slug, empty if none.
	 * @param bool         $is_translatable Whether or not the post type is translatable.
	 */
	abstract public function test_preaload_paths_in_widget_editor_context( $path, $is_filtered, $post, $language, $is_translatable );

	/**
	 * Asserts the output path has the expected added routes.
	 *
	 * @param array                   $input_path    Input path.
	 * @param array                   $output_path   Output path to test.
	 * @param bool                    $is_filterable Whether or not the input path is filterable in the first place.
	 * @param WP_Block_Editor_Context $context       The context of the preload path.
	 * @return void
	 */
	abstract protected function assert_path_added( $input_path, $output_path, $is_filterable, $context );

	protected function assert_unfiltered_path_for_context( $path, $context_name ) {
		$this->assertSameSets(
			array( $path ),
			$this->call_sut( array( $path ), $this->get_context( $context_name ) ),
			"Path should not be filtered in {$context_name} context."
		);
	}

	protected function get_context( $context_name, $post = null ) {
		$context_settings = array();

		if ( property_exists( WP_Block_Editor_Context::class, 'name' ) ) {
			// Backward compatibility with WordPress < 6.0 where `WP_Block_Editor_Context::$name` didn't exist yet.
			$context_settings['name'] = $context_name;
		}

		if ( ! is_null( $post ) ) {
			$context_settings['post'] = $post;
		}

		return new WP_Block_Editor_Context( $context_settings );
	}

	/**
	 * Provides preload paths with different dataset combination such as:
	 *     - Standard, translatable or unstranslatable custom post type.
	 *     - Default, secondary or undefined language for a translatable post.
	 *
	 * @return array $data {
	 *    @type string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 *    @type bool         $is_filtered     Whether the pass should be filtered or not.
	 *    @type WP_Post      $post            The post provided for the context.
	 *    @type string       $lang            The post's language slug, empty if none.
	 *    @type bool         $is_translatable Whether or not the post type is translatable.
	 * }
	 */
	public function preload_paths_provider() {
		$languages = array(
			'en',
			'fr',
			'',
		);

		foreach ( $languages as $language ) {
			$posts = array(
				'translatable'     => $this->factory()->post->create_and_get(),
				'translatable_cpt' => $this->factory()->post->create_and_get( array( 'post_type' => 'trcpt' ) ),
				'untranslatable'   => $this->factory()->post->create_and_get( array( 'post_type' => 'custom' ) ),
			);

			foreach ( $posts as $is_translatable => $post ) {
				foreach ( $this->get_paths_dataset( $post ) as $is_filtered => $_paths ) {
					foreach ( $_paths as $path ) {
						yield array(
							'path'            => $path,
							'is_filtered'     => 'filtered' === $is_filtered,
							'post'            => $post,
							'lang'            => $language,
							'is_translatable' => 'untranslatable' !== $is_translatable,
						);
					}
				}
			}
		}
	}

	/**
	 * Returns a paths dataset generated with a given post, defined here to be easily overridden.
	 *
	 * @param WP_Porst $post
	 * @return array $data {
	 *    @type array $filtered   List of filterable paths.
	 *    @type array $unfiltered List of unfilterable paths.
	 * }
	 */
	protected function get_paths_dataset( $post ) {
		return array(
			'filtered' => array(
				array(
					0 => '/wp/v2/blocks',
					1 => 'OPTIONS',
				),
				'/wp/v2/categories',
				'/wp/v2/posts/',
			),
			'unfiltered' => array(
				'/',
				'/wp/v2/types?context=edit',
				'/wp/v2/taxonomies?per_page=-1&context=edit',
				'/wp/v2/themes?status=active',
				"/wp/v2/types/{$post->post_type}?context=edit",
				"/wp/v2/users/me?post_type={$post->post_type}&context=edit",
				"/wp/v2/{$post->post_type}s/{$post->ID}?context=edit",
				"/wp/v2/{$post->post_type}s/{$post->ID}/autosaves?context=edit",
				array(
					0 => '/wp/v2/media',
					1 => 'OPTIONS',
				),
			),
		);
	}
}
