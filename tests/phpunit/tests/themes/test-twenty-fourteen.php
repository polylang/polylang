<?php

class Twenty_Fourteen_Test extends PLL_UnitTestCase {
	protected static $stylesheet;
	protected static $tags;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		self::markTestSkippedIfFileNotExists( PLL_TEST_THEMES_DIR . 'twentyfourteen/style.css', 'This test requires the theme Twenty Fourteen.' );
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		self::$stylesheet = get_option( 'stylesheet' ); // Save default theme.
		switch_theme( 'twentyfourteen' );
	}

	public function set_up() {
		parent::set_up();

		require_once get_template_directory() . '/functions.php';
		twentyfourteen_setup();
		Featured_Content::init();

		$this->frontend = ( new PLL_Context_Frontend() )->get();
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		switch_theme( self::$stylesheet );
	}

	public function test_ephemera_widget() {
		global $content_width; // The widget accesses this global, no matter what it contains.
		$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

		$posts = self::factory()->post->create_translated(
			array(
				'post_content' => 'Test',
				'lang'         => 'en',
			),
			array(
				'post_content' => 'Essai',
				'lang'         => 'fr',
			)
		);
		set_post_format( $posts['en'], 'aside' );
		set_post_format( $posts['fr'], 'aside' );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		require_once get_template_directory() . '/inc/widgets.php';
		$widget   = new Twenty_Fourteen_Ephemera_Widget();
		$args     = array(
			'before_title'  => '',
			'after_title'   => '',
			'before_widget' => '',
			'after_widget'  => '',
		);
		$instance = array();

		ob_start();
		$widget->widget( $args, $instance );
		$out = ob_get_clean();

		$this->assertStringNotContainsString( '<p>Test</p>', $out );
		$this->assertStringContainsString( '<p>Essai</p>', $out );

		unset( $content_width );
	}

	protected function setup_featured_tags() {
		self::$tags = self::factory()->tag->create_translated(
			array(
				'name'     => 'featured',
				'lang'     => 'en',
			),
			array(
				'name'     => 'en avant',
				'lang'     => 'fr',
			)
		);

		$options = array(
			'hide-tag' => 1,
			'tag-id'   => self::$tags['en'],
			'tag-name' => 'featured',
		);

		update_option( 'featured-content', $options );
	}

	public function test_option_featured_content() {
		$this->setup_featured_tags();

		$this->frontend->curlang = self::$model->get_language( 'en' );

		$settings = Featured_Content::get_setting();
		$this->assertEquals( self::$tags['en'], $settings['tag-id'] );

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		$settings = Featured_Content::get_setting();
		$this->assertEquals( self::$tags['fr'], $settings['tag-id'] );
	}

	public function test_featured_content_ids() {
		$this->setup_featured_tags();

		$en = self::factory()->post->create( array( 'tags_input' => array( 'featured' ) ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'tags_input' => array( 'en avant' ) ) );
		self::$model->post->set_language( $fr, 'fr' );

		do_action_ref_array( 'pll_init', array( &$this->frontend ) ); // To pass the test in PLL_Plugins_Compat::twenty_fourteen_featured_content_ids.

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( array( get_post( $en ) ), twentyfourteen_get_featured_posts() );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( array( get_post( $fr ) ), twentyfourteen_get_featured_posts() );
	}
}
