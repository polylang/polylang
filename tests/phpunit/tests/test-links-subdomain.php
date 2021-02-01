<?php

class Links_Subdomain_Test extends PLL_Domain_UnitTestCase {

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		$this->hosts = array(
			'en' => 'http://example.org',
			'fr' => 'http://fr.example.org',
			'de' => 'http://de.example.org',
		);

		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang'] = 2;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		$this->links_model = self::$model->get_links_model();
	}

	function test_get_language_from_url() {
		$this->assertEquals( 'en', $this->links_model->get_language_from_url( 'http://example.org' ) );
		$this->assertEquals( 'en', $this->links_model->get_language_from_url( 'http://example.org/test/' ) );
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url( 'http://fr.example.org/test/' ) );
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url( 'http://fr.example.org' ) );
	}

	function test_get_language_from_url_with_empty_param() {
		$_SERVER['HTTP_HOST'] = 'fr.example.org';
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url() );

		$_SERVER['REQUEST_URI'] = '/test/';
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url() );

		$_SERVER['HTTP_HOST'] = 'example.org';
		$this->assertEquals( 'en', $this->links_model->get_language_from_url() );
	}

	function test_wrong_get_language_from_url() {
		$this->assertEmpty( $this->links_model->get_language_from_url( 'http://es.example.org' ) );
		$this->assertEmpty( $this->links_model->get_language_from_url( 'http://fr.org' ) );

		$_SERVER['HTTP_HOST'] = 'es.example.org';
		$this->assertEmpty( $this->links_model->get_language_from_url() ); // ok

		$_SERVER['HTTP_HOST'] = 'fr.org';
		$this->assertEmpty( $this->links_model->get_language_from_url() ); // fails ( returns 'fr' )
	}
}
