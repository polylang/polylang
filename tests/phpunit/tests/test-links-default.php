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

	function setUp() {
		parent::setUp();

		self::$polylang->options['post_types'] = array(
			'cpt' => 'cpt',
		);
		register_post_type( 'cpt', array( 'public' => true ) ); // translated custom post type

		self::$polylang->options['hide_default'] = 1;
	}

	function test_add_language_to_link() {
		$url = $this->host . '/?p=test';

		$this->assertEquals( $this->host . '/?p=test', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', self::$polylang->links_model->add_language_to_link( $url, self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_double_add_language_to_link() {
		$this->assertEquals( $this->host . '/?p=test&lang=fr', self::$polylang->links_model->add_language_to_link( $this->host . '/?p=test&lang=fr', self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_remove_language_from_link() {
		$this->assertEquals( $this->host . '/?p=test', self::$polylang->links_model->remove_language_from_link( $this->host . '/?p=test&lang=fr' ) );
	}

	function test_switch_language_in_link() {
		$this->assertEquals( $this->host . '/?p=test', self::$polylang->links_model->switch_language_in_link( $this->host . '/?p=test&lang=fr', self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=de', self::$polylang->links_model->switch_language_in_link( $this->host . '/?p=test&lang=fr', self::$polylang->model->get_language( 'de' ) ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', self::$polylang->links_model->switch_language_in_link( $this->host . '/?p=test', self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_add_paged_to_link() {
		$this->assertEquals( $this->host . '/?p=test&paged=2', self::$polylang->links_model->add_paged_to_link( $this->host . '/?p=test', 2 ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr&paged=2', self::$polylang->links_model->add_paged_to_link( $this->host . '/?p=test&lang=fr', 2 ) );
	}

	function test_remove_paged_from_link() {
		$this->assertEquals( $this->host . '/?p=test', self::$polylang->links_model->remove_paged_from_link( $this->host . '/?p=test&paged=2' ) );
		$this->assertEquals( $this->host . '/?p=test&lang=fr', self::$polylang->links_model->remove_paged_from_link( $this->host . '/?p=test&lang=fr&paged=2' ) );
	}

	function test_get_language_from_url() {
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->host, PHP_URL_HOST );
		$_SERVER['REQUEST_URI'] = '/?p=test&lang=fr';
		$this->assertEquals( 'fr', self::$polylang->links_model->get_language_from_url() );
	}

	// bug fixed in 1.8
	function test_home_url() {
		$this->assertEquals( $this->host . '/', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'en' ) ) );
		$this->assertEquals( $this->host . '/?lang=fr', self::$polylang->links_model->home_url( self::$polylang->model->get_language( 'fr' ) ) );
	}

	// bug fixed in v1.8
	function test_language_code_in_post_url() {
		self::$polylang->options['force_lang'] = 1;
		self::$polylang->filter_links = new PLL_Filters_Links( self::$polylang );

		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=en', get_permalink( $en ) );
		$this->assertContains( 'lang=fr', get_permalink( $fr ) );

		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=en', get_permalink( $en ) );
		$this->assertContains( 'lang=fr', get_permalink( $fr ) );

		$en = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=en', get_permalink( $en ) );
		$this->assertContains( 'lang=fr', get_permalink( $fr ) );
	}

	function test_language_from_post_content() {
		self::$polylang->options['force_lang'] = 0;
		self::$polylang->filter_links = new PLL_Filters_Links( self::$polylang );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=fr', get_permalink( $fr ) );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=fr', get_permalink( $fr ) );

		$fr = $this->factory->post->create( array( 'post_type' => 'cpt' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$this->assertNotContains( 'lang=fr', get_permalink( $fr ) );
	}
}
