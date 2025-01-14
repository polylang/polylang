<?php

class Admin_Static_Pages_Test extends PLL_UnitTestCase {
	protected static $home_en;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
		self::$model->options['default_lang'] = 'en'; // Otherwise static model isn't aware of the created languages...

		self::$home_en = $factory->post->create(
			array(
				'post_title'   => 'home',
				'post_type'    => 'page',
				'post_content' => 'en1<!--nextpage-->en2',
				'lang'         => 'en',
			)
		);

		self::$model->clean_languages_cache();

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', self::$home_en );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$home_en, true );

		parent::wpTearDownAfterClass();
	}

	public function tear_down() {
		parent::tear_down();

		remove_filter( 'pll_get_post_types', array( $this, 'remove_page_post_type' ) );
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $translate_page_post_type True to translate the `page` post type.
	 * @return void
	 */
	public function test_front_page_not_translated_warning( bool $translate_page_post_type ): void {
		wp_set_current_user( 1 );

		// Needed by `LL_Admin_Static_Pages::notice_must_translate()`.
		$GLOBALS['hook_suffix'] = 'toplevel_page_mlang';
		set_current_screen();

		if ( ! $translate_page_post_type ) {
			// Don't translate the `page` post type.
			add_filter( 'pll_get_post_types', array( $this, 'remove_page_post_type' ) );
		}

		$links_model   = self::$model->get_links_model();
		$this->pll_env = new PLL_Admin( $links_model );

		$this->pll_env->init();

		$post_types = $this->pll_env->model->post->get_translated_object_types();

		if ( ! $translate_page_post_type ) {
			$this->assertArrayNotHasKey( 'page', $post_types );
		} else {
			$this->assertArrayHasKey( 'page', $post_types );
		}

		ob_start();
		do_action( 'after_setup_theme' );
		do_action( 'admin_notices' );
		$notices = ob_get_clean();

		if ( ! $translate_page_post_type ) {
			// No warning notice if we don't translate the pages.
			$this->assertStringNotContainsString( '/post-new.php?post_type=page', $notices );
			return;
		}

		// Make sure there is something (our notice) that displays a link to create a translation.
		$this->assertStringContainsString( '/post-new.php?post_type=page', $notices );

		$this->assertNotFalse( preg_match( '@href="([^"]+/post-new\.php[^"]+)"@', $notices, $matches ) );
		$query_str = wp_parse_url( html_entity_decode( $matches[1] ), PHP_URL_QUERY );
		$this->assertIsString( $query_str );
		wp_parse_str( $query_str, $query_arr );

		$expected = array(
			'post_type' => 'page',
			'from_post' => (string) self::$home_en,
			'new_lang'  => 'fr',
		);
		$this->assertSameSetsWithIndex(
			$expected,
			array_intersect_key( $query_arr, $expected )
		);
	}

	/**
	 * Removes the `page` post type from the given array.
	 *
	 * @param array $post_types An array of post types.
	 * @return array
	 */
	public function remove_page_post_type( array $post_types ): array {
		return array_diff( $post_types, array( 'page' ) );
	}
}
