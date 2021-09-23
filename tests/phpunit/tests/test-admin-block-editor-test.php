<?php


class PLL_Admin_Block_Editor_Test extends PLL_UnitTestCase {

	/**
	 * @var PLL_Admin_Block_Editor
	 */
	private $admin_block_editor;

	/**
	 * @var PLL_Admin
	 */
	private $pll_admin;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		$options = PLL_Install::get_default_options();
		$model = new PLL_Admin_Model( $options );
		$links_model = new PLL_Links_Default( $model );
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->admin_block_editor = new PLL_Admin_Block_Editor( $this->pll_admin );
	}

	public function test_do_not_set_language_parameter_to_root_URL() {
		$post = $this->factory()->post->create_and_get();
		$this->pll_admin->model->post->set_language( $post->ID, 'en' );

		$preload_paths = $this->preload_paths( $post );

		$this->assertContains( '/', $preload_paths );
	}

	public function test_do_not_set_language_to_preload_paths_for_untranslated_post_type() {
		register_post_type( 'custom' );
		$post = $this->factory()->post->create_and_get(
			array(
				'post_type' => 'custom',
			)
		);

		$preload_paths = $this->preload_paths( $post );

		$this->assertNotEmpty( $preload_paths );
		foreach ( $preload_paths as $preload_path ) {
			$this->assertFalse(
				is_string( $preload_path ) &&
				stripos( $preload_path, 'lang' )
			);
		}
	}

	public function test_set_preferred_language_as_parameter_to_preload_paths_for_a_post_without_language() {
		$this->pll_admin->pref_lang = $this->pll_admin->model->get_language( 'en' );
		$post = $this->factory()->post->create_and_get();

		$preload_paths = $this->preload_paths( $post );

		$this->assertNotEmpty( $preload_paths );
		foreach ( $preload_paths as $preload_path ) {
			$this->assertTrue(
				is_array( $preload_path ) ||
				'/' === $preload_path ||
				preg_match( '#lang=en#', $preload_path )
			);
		}
	}

	public function test_set_post_language_as_parameter_to_preload_paths_for_a_post_with_a_language() {
		$post = $this->factory()->post->create_and_get();
		$this->pll_admin->model->post->set_language( $post->ID, 'fr' );

		$preload_paths = $this->preload_paths( $post );

		$this->assertNotEmpty( $preload_paths );
		foreach ( $preload_paths as $preload_path ) {
			$this->assertTrue(
				is_array( $preload_path ) ||
				'/' === $preload_path ||
				preg_match( '#lang=fr#', $preload_path )
			);
		}
	}

	/**
	 * Filters the preload paths with the correct function depending on WordPress version.
	 *
	 * @param WP_Post|mixed $post Represents the post being edited. Or a preload path without if no post is being edited.
	 * @return mixed
	 */
	protected function preload_paths( $post ) {
		$preload_paths = array(
			0 => '/',
			1 => '/wp/v2/types?context=edit',
			2 => '/wp/v2/taxonomies?per_page=-1&context=edit',
			3 => '/wp/v2/themes?status=active',
			4 => "/wp/v2/posts/{$post->ID}?context=edit",
			5 => "/wp/v2/types/{$post->post_type}?context=edit",
			6 => "/wp/v2/users/me?post_type={$post->post_type}&context=edit",
			7 => array(
				0 => '/wp/v2/media',
				1 => 'OPTIONS',
			),
			8 => array(
				0 => '/wp/v2/blocks',
				1 => 'OPTIONS',
			),
			9 => "/wp/v2/posts/{$post->ID}/autosaves?context=edit",
		);

		return $this->admin_block_editor->preload_paths( $preload_paths, $post );
	}
}
