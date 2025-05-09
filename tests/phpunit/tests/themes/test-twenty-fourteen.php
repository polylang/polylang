<?php

class Twenty_Fourteen_Test extends PLL_UnitTestCase {
	protected static $stylesheet;
	protected static $tag_en;
	protected static $tag_fr;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::markTestSkippedIfFileNotExists( PLL_TEST_THEMES_DIR . 'twentyfourteen/style.css', 'This test requires the theme Twenty Fourteen.' );

		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::require_api();

		self::$stylesheet = get_option( 'stylesheet' ); // save default theme
		switch_theme( 'twentyfourteen' );
	}

	public function set_up() {
		parent::set_up();

		require_once get_template_directory() . '/functions.php';
		twentyfourteen_setup();
		Featured_Content::init();

		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
		$GLOBALS['polylang'] = &$this->frontend;
		$this->frontend->featured_content = new PLL_Featured_Content();
		$this->frontend->featured_content->init();
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		switch_theme( self::$stylesheet );
	}

	public function tear_down() {
		parent::tear_down();

		unset( $GLOBALS['polylang'] );
	}

	public function test_ephemera_widget() {
		global $content_width; // The widget accesses this global, no matter what it contains.
		$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

		$en = self::factory()->post->create( array( 'post_content' => 'Test', 'post_author' => 1 ) );
		set_post_format( $en, 'aside' );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_content' => 'Essai', 'post_author' => 1 ) );
		set_post_format( $fr, 'aside' );
		self::$model->post->set_language( $fr, 'fr' );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		require_once get_template_directory() . '/inc/widgets.php';
		$widget = new Twenty_Fourteen_Ephemera_Widget();
		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
		$instance = array();

		ob_start();
		$widget->widget( $args, $instance );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, '<p>Test</p>' ) );
		$this->assertNotFalse( strpos( $out, '<p>Essai</p>' ) );

		unset( $content_width );
	}

	protected function setup_featured_tags() {
		self::$tag_en = $en = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'featured' ) );
		self::$model->term->set_language( $en, 'en' );

		self::$tag_fr = $fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'en avant' ) );
		self::$model->term->set_language( $fr, 'fr' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$options = array(
			'hide-tag' => 1,
			'tag-id'   => $en,
			'tag-name' => 'featured',
		);

		update_option( 'featured-content', $options );
	}

	public function test_option_featured_content() {
		$this->setup_featured_tags();

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$settings = Featured_Content::get_setting();
		$this->assertEquals( self::$tag_en, $settings['tag-id'] );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$settings = Featured_Content::get_setting();
		$this->assertEquals( self::$tag_fr, $settings['tag-id'] );
	}

	public function test_featured_content_ids() {
		$this->setup_featured_tags();

		$en = self::factory()->post->create( array( 'tags_input' => array( 'featured' ) ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'tags_input' => array( 'en avant' ) ) );
		self::$model->post->set_language( $fr, 'fr' );

		do_action_ref_array( 'pll_init', array( &$this->frontend ) ); // to pass the test in PLL_Plugins_Compat::twenty_fourteen_featured_content_ids

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( array( get_post( $en ) ), twentyfourteen_get_featured_posts() );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( array( get_post( $fr ) ), twentyfourteen_get_featured_posts() );
	}
}
