<?php

class Single_Language_Test extends PLL_UnitTestCase {
	protected $structure = '/%postname%/';
	protected $home_en;
	protected $posts_en;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
	}

	public function set_up() {
		parent::set_up();

		$this->home_en = $this->factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Front Page EN',
			)
		);
		self::$model->post->set_language( $this->home_en, 'en' );
		$this->posts_en = $this->factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Blog Page EN',
			)
		);
		self::$model->post->set_language( $this->posts_en, 'en' );
	}

	/**
	 * @ticket #1697
	 * @see https://github.com/polylang/polylang-pro/issues/1697.
	 */
	public function test_front_page() {
		global $wp_rewrite;

		$options                  = PLL_Install::get_default_options();
		$options['redirect_lang'] = 1;
		$options['hide_default']  = 0;
		$options['force_lang']    = 1;
		$options['rewrite']       = 1;
		$options['default_lang']  = 'en';
		$model                    = new PLL_Model( $options );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->home_en );
		update_option( 'page_for_posts', $this->posts_en );

		// Switch to pretty permalinks.
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		$links_model = $model->get_links_model();
		$links_model->init();

		$wp_rewrite->flush_rules();

		$pll = new PLL_Frontend( $links_model );
		$pll->init();
		$pll->model->clean_languages_cache();

		$this->go_to( home_url( '/en' ) );

		$this->assertTrue( is_front_page() );
		$this->assertQueryTrue( 'is_page', 'is_singular', 'is_front_page' );
	}
}
