<?php

class Links_Default_Test extends PLL_UnitTestCase {
	protected $host = 'http://example.org';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	public function set_up() {
		parent::set_up();

		self::$model->options['post_types'] = array(
			'cpt' => 'cpt',
		);
		register_post_type( 'cpt', array( 'public' => true ) ); // translated custom post type

		self::$model->options['hide_default'] = 1;

		$this->links_model = self::$model->get_links_model();
	}

	public function test_add_language_to_link() {
		$url = $this->host . '/?p=test';

		$this->assertEquals( $this->host . '/?p=test', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'fr' ) ) );
	}

	public function test_double_add_language_to_link() {
		$this->assertEquals( $this->host . '/?p=test&lang=fr', $this->links_model->add_language_to_link( $this->host . '/?p=test&lang=fr', self::$model->get_language( 'fr' ) ) );
	}

	public function test_remove_language_from_link() {
		$this->assertEquals( $this->host . '/?p=test', $this->links_model->remove_language_from_link( $this->host . '/?p=test&lang=fr' ) );
	}

	public function test_switch_language_in_link() {
		$this->assertEquals( $this->host . '/?p=test', $this->links_model->switch_language_in_link( $this->host . '/?p=test&lang=fr', self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=de', $this->links_model->switch_language_in_link( $this->host . '/?p=test&lang=fr', self::$model->get_language( 'de' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', $this->links_model->switch_language_in_link( $this->host . '/?p=test', self::$model->get_language( 'fr' ) ) );
	}

	public function test_add_paged_to_link() {
		$this->assertEquals( $this->host . '/?p=test&paged=2', $this->links_model->add_paged_to_link( $this->host . '/?p=test', 2 ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr&paged=2', $this->links_model->add_paged_to_link( $this->host . '/?p=test&lang=fr', 2 ) );
	}

	public function test_remove_paged_from_link() {
		$this->assertEquals( $this->host . '/?p=test', $this->links_model->remove_paged_from_link( $this->host . '/?p=test&paged=2' ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', $this->links_model->remove_paged_from_link( $this->host . '/?p=test&lang=fr&paged=2' ) );
	}

	public function test_get_language_from_url() {
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->host, PHP_URL_HOST );
		$_SERVER['REQUEST_URI'] = '/?p=test&lang=fr';
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url() );
	}

	/**
	 * Bug fixed in 1.8.
	 */
	public function test_home_url() {
		$this->assertEquals( $this->host . '/', $this->links_model->home_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?lang=fr', $this->links_model->home_url( self::$model->get_language( 'fr' ) ) );
	}

	/**
	 * Bug fixed in 1.8.
	 */
	public function test_language_code_in_post_url() {
		self::$model->options['force_lang'] = 1;
		$frontend = new PLL_Frontend( $this->links_model );
		new PLL_Filters_Links( $frontend );

		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=en', get_permalink( $en ) );
		$this->assertStringContainsString( 'lang=fr', get_permalink( $fr ) );

		$en = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=en', get_permalink( $en ) );
		$this->assertStringContainsString( 'lang=fr', get_permalink( $fr ) );

		$en = self::factory()->post->create( array( 'post_type' => 'cpt' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_type' => 'cpt' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=en', get_permalink( $en ) );
		$this->assertStringContainsString( 'lang=fr', get_permalink( $fr ) );
	}

	public function test_language_from_post_content() {
		self::$model->options['force_lang'] = 0;
		$frontend = new PLL_Frontend( $this->links_model );
		new PLL_Filters_Links( $frontend );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=fr', get_permalink( $fr ) );

		$fr = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=fr', get_permalink( $fr ) );

		$fr = self::factory()->post->create( array( 'post_type' => 'cpt' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->assertStringNotContainsString( 'lang=fr', get_permalink( $fr ) );
	}
}
