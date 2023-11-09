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
		register_taxonomy( 'trtax', 'trcpt', array( 'show_in_rest' => true ) ); // Translated custom taxonomy.
		register_taxonomy( 'trtax_with_no_namespace', 'trcpt', array( 'show_in_rest' => true, 'rest_namespace' => null ) ); // Translated custom taxonomy with namespace explicitly set to null as in CPTUI.

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
					'trtax'                   => 'trtax',
					'trtax_with_no_namespace' => 'trtax_with_no_namespace',
				),
			)
		);
		$model           = new PLL_Admin_Model( $options );
		$links_model     = new PLL_Links_Default( $model );
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->pll_admin->init();
		$this->pll_admin->block_editor = new PLL_Admin_Block_Editor( $this->pll_admin );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'custom' );
		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
		_unregister_taxonomy( 'trtax_with_no_namespace' );
	}

	/**
	 * Calls `block_editor_rest_api_preload_paths` and returns filtered preload paths.
	 *
	 * @param array                   $paths   List of preload paths.
	 * @param WP_Block_Editor_Context $context Context of preload paths.
	 * @return mixed
	 */
	protected function get_preload_paths( $paths, $context ) {
		return apply_filters( 'block_editor_rest_api_preload_paths', $paths, $context );
	}

	/**
	 * Asserts the output path has the expected added routes.
	 *
	 * @param array  $input_path  Input path.
	 * @param array  $output_path Output path to test.
	 * @param array  $added_paths Expected added paths. Pass empty array for no added path expected.
	 * @param string $message     Error message. Default empty string.
	 * @return void
	 */
	protected function assert_path_added( $input_path, $output_path, $added_paths, $message = '' ) {
		$this->assertCount( count( $input_path ) + count( $added_paths ), $output_path, $message );

		foreach ( $added_paths as $added_path ) {
			$this->assertContains( $added_path, $output_path, "{$added_path} path should be added." );
		}
	}

	protected function assert_unfiltered_path_for_context( $path, $context_name ) {
		$this->assertSameSets(
			array( $path ),
			$this->get_preload_paths( array( $path ), $this->get_context( $context_name ) ),
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
	 *     - Default or secondary language for a translatable post.
	 *
	 * @return array $data {
	 *    @type string|array $path            The preload path under test. Could be an array if provided along a HTTP method.
	 *    @type bool         $is_filtered     Whether the path should be filtered or not.
	 *    @type WP_Post      $post            The post provided for the context.
	 *    @type string       $lang            The post's language slug, empty if none.
	 *    @type bool         $is_translatable Whether or not the post type is translatable.
	 * }
	 */
	public function preload_paths_provider(): array {
		$return    = array();
		$languages = array(
			'en',
			'fr',
		);
		$posts     = array(
			'translatable',
			'translatable_cpt',
			'untranslatable',
		);

		foreach ( $languages as $language ) {
			foreach ( $posts as $context_post ) {
				foreach ( $this->get_paths_dataset() as $is_filtered => $_paths ) {
					foreach ( $_paths as $path ) {
						$return[] = array(
							'path'            => $path,
							'is_filtered'     => 'filtered' === $is_filtered,
							'context_post'    => $context_post,
							'lang'            => $language,
						);
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Returns a paths dataset, defined here to be easily overridden.
	 *
	 * @return array $data {
	 *    @type array $filtered   List of filterable paths.
	 *    @type array $unfiltered List of unfilterable paths.
	 * }
	 */
	protected function get_paths_dataset(): array {
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
				'/wp/v2/types/{post_type}?context=edit',
				'/wp/v2/users/me?post_type={post_type}&context=edit',
				'/wp/v2/{post_type}s/{ID}?context=edit',
				'/wp/v2/{post_type}s/{ID}/autosaves?context=edit',
				array(
					0 => '/wp/v2/media',
					1 => 'OPTIONS',
				),
			),
		);
	}

	/**
	 * Makes the data returned by preload_paths_provider concrete by creating a post and injecting its type and ID into the given path.
	 *
	 * @param string|string[] $path         The preload path under test. Could be an array if provided along a HTTP method.
	 * @param bool            $is_filtered  Whether the path should be filtered or not.
	 * @param string          $context_post Says what type of post must be created for the context. Possible values are `translatable`, `translatable_cpt`, `untranslatable`.
	 * @return array                        The filtered path at key 0, the post at key 1, "is the post type translatable" at key 2.
	 */
	protected function make_data_concrete( $path, bool $is_filtered, string $context_post ): array {
		switch ( $context_post ) {
			case 'translatable':
				$post            = $this->factory()->post->create_and_get();
				$is_translatable = true;
				break;

			case 'translatable_cpt':
				$post            = $this->factory()->post->create_and_get( array( 'post_type' => 'trcpt' ) );
				$is_translatable = true;
				break;

			default:
				$post            = $this->factory()->post->create_and_get( array( 'post_type' => 'custom' ) );
				$is_translatable = false;
				break;
		}

		if ( ! $is_filtered && is_string( $path ) ) {
			$path = str_replace( array( '{post_type}', '{ID}' ), array( $post->post_type, $post->ID ), $path );
		}

		return array( $path, $post, $is_translatable );
	}
}
