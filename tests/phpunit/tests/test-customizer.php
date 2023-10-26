<?php

class Customizer_Test extends PLL_UnitTestCase {
	/**
	 * @var WP_Customize_Manager
	 */
	protected $wp_customize;

	protected $page_en;
	protected $page_fr;

	protected static $default_theme;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$default_theme = get_stylesheet();
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		unset( $_POST );
		unset( $GLOBALS['wp_customize'] );

		switch_theme( self::$default_theme );
	}

	public function set_up() {
		parent::set_up();

		$this->page_en = $this->factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Front Page EN',
			)
		);
		self::$model->post->set_language( $this->page_en, 'en' );
		$this->page_fr = $this->factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Front Page FR',
			)
		);
		self::$model->post->set_language( $this->page_fr, 'fr' );
		self::$model->post->save_translations(
			$this->page_en,
			array(
				'en' => $this->page_en,
				'fr' => $this->page_fr,
			)
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
	}

	public function test_static_front_page_update() {
		$_POST['customized'] = wp_json_encode(
			array(
				'show_on_front' => 'page',
				'page_on_front' => $this->page_fr,
			)
		);
		$_POST['wp_customize'] = 'on';

		$links_model             = self::$model->get_links_model();
		$this->pll_env           = new PLL_Frontend( $links_model );
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		$this->wp_customize      = $GLOBALS['wp_customize'];
		do_action( 'customize_register', $this->wp_customize );
		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined', $this->pll_env->curlang->slug, $this->pll_env->curlang );

		$show_on_front = $this->wp_customize->get_setting( 'show_on_front' );
		$show_on_front->save();
		$page_on_front = $this->wp_customize->get_setting( 'page_on_front' );
		$page_on_front->save();

		$this->assertSame( 'page', $show_on_front->value() );
		$this->assertSame( $this->page_fr, $page_on_front->value() );
	}

	public function test_static_front_page_display_secondary_language() {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->page_en );
		self::$model->clean_languages_cache();

		$_POST['wp_customize'] = 'on';

		$links_model             = self::$model->get_links_model();
		$this->pll_env           = new PLL_Frontend( $links_model );
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		$this->wp_customize      = $GLOBALS['wp_customize'];
		do_action( 'customize_register', $this->wp_customize );
		$this->pll_env->curlang = self::$model->get_language( 'fr' );
		do_action( 'pll_language_defined', $this->pll_env->curlang->slug, $this->pll_env->curlang );

		$show_on_front = $this->wp_customize->get_setting( 'show_on_front' );
		$page_on_front = $this->wp_customize->get_setting( 'page_on_front' );

		$this->assertSame( 'page', $show_on_front->value() );
		$this->assertSame( $this->page_fr, $page_on_front->value() );
	}

	public function test_customize_registered_hooks_with_static_page_on_front() {
		global $_wp_theme_features;

		update_option( 'show_on_front', 'page' ); // Implicit `PLL_Frontend_Static_Pages` instance.
		update_option( 'page_on_front', $this->page_en );
		self::$model->clean_languages_cache();

		// Switch to a block theme.
		switch_theme( 'twentytwentythree' );
		// Force the features.
		$_wp_theme_features['block-templates']      = true;
		$_wp_theme_features['block-template-parts'] = true;

		$_POST['wp_customize'] = 'on';

		$links_model   = self::$model->get_links_model();
		$this->pll_env = new PLL_Frontend( $links_model );
		$this->pll_env->init(); // Implicit `PLL_Frontend_Nav_Menu` instance.
		add_action( 'customize_register', '__return_false' );

		$this->assertFalse( $this->pll_env->should_customize_menu_be_removed() );
	}
}
