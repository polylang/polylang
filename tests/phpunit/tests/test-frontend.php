<?php


class Frontend_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		wp_set_current_user( 1 ); // Set a user to pass current_user_can tests.
	}

	public function tear_down() {
		parent::tear_down();

		switch_theme( 'default' );
	}

	public function test_remove_customize_admin_bar_with_block_base_theme() {
		global $wp_admin_bar;

		switch_theme( 'block-theme' );
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->nav_menu = new PLL_Nav_Menu( $frontend ); // For auto added pages to menu.

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );
		do_action( 'wp_before_admin_bar_render' );

		$this->assertEquals( null, $wp_admin_bar->get_node( 'customize' ) );
	}

	public function test_remove_customize_admin_bar_with_non_block_base_theme() {
		global $wp_admin_bar;
		add_filter( 'show_admin_bar', '__return_true' ); // Make sure to show admin bar.

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->nav_menu = new PLL_Nav_Menu( $frontend ); // For auto added pages to menu.

		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		remove_action( 'wp_before_admin_bar_render', 'wp_customize_support_script' ); // To avoid the script launch in test.

		do_action( 'wp_before_admin_bar_render' );

		$this->assertInstanceOf( stdClass::class, $wp_admin_bar->get_node( 'customize' ) );
	}

	/**
	 * Tests that the main query doesn't do extra queries to fetch the language's `term_taxonomy_id`.
	 *
	 * @see https://github.com/polylang/polylang-pro/issues/2562
	 *
	 * @return void
	 */
	public function test_optimize_query(): void {
		self::create_language( 'en_US' );

		$options = self::create_options(
			array(
				'hide_default' => false,
				'default_lang' => 'en',
			)
		);

		$model = new PLL_Model( $options );
		$links_model = $model->get_links_model();
		$links_model->init();

		new PLL_Frontend( $links_model );

		$model->get_language( 'en' ); // Put the query in cache before the following filter.
		$queries = array();

		add_filter(
			'query',
			function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			}
		);

		$GLOBALS['wp_object_cache']->flush_group( 'term-queries' );
		$GLOBALS['wp_object_cache']->flush_group( 'terms' );
		new WP_Query( array( 'lang' => 'en' ) );

		$this->assertCount( 1, $queries );
	}
}
