<?php

class Links_Domain_Test extends PLL_Domain_UnitTestCase {

	public function set_up() {
		parent::set_up();

		global $wp_rewrite;

		$this->hosts = array(
			'en' => 'http://example.org',
			'fr' => 'http://example.fr',
			'de' => 'http://example.de',
		);

		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang'] = 3;
		self::$model->options['domains'] = $this->hosts;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		$this->links_model = self::$model->get_links_model();
	}

	public function test_wrong_get_language_from_url() {
		$_SERVER['HTTP_HOST'] = 'de.example.fr';
		$this->assertEmpty( $this->links_model->get_language_from_url() );

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEmpty( $this->links_model->get_language_from_url() );
	}

	public function test_login_url() {
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['en'], PHP_URL_HOST );
		$this->assertEquals( $this->hosts['en'] . '/wp-login.php', wp_login_url() );

		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$this->assertEquals( $this->hosts['fr'] . '/wp-login.php', wp_login_url() );
	}

	/**
	 * Bug fixed in version 2.1.2.
	 */
	public function test_second_level_domain() {
		self::$model->options['domains']['fr'] = 'http://example.org.fr';
		$this->links_model = self::$model->get_links_model();

		$url = 'http://example.org.fr';

		$this->assertEquals( 'http://example.org.fr', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( 'http://example.org', $this->links_model->remove_language_from_link( $url, self::$model->get_language( 'fr' ) ) );

		$url = 'http://example.org.fr/test/';

		$this->assertEquals( 'http://example.org.fr/test/', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'fr' ) ) );
		$this->assertEquals( 'http://example.org/test/', $this->links_model->remove_language_from_link( $url, self::$model->get_language( 'fr' ) ) );
	}

	public function test_permalink_and_shortlink() {
		$frontend = new PLL_Frontend( $this->links_model );
		$filters_links = new PLL_Frontend_Filters_Links( $frontend );

		$filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$filters_links->cache->method( 'get' )->willReturn( false );

		$post_id = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $post_id, 'en' );
		$this->assertEquals( 'http://example.org/test/', get_permalink( $post_id ) );
		$this->assertEquals( 'http://example.org/?p=' . $post_id, wp_get_shortlink( $post_id ) );

		$post_id = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		$this->assertEquals( 'http://example.fr/essai/', get_permalink( $post_id ) );
		$this->assertEquals( 'http://example.fr/?p=' . $post_id, wp_get_shortlink( $post_id ) );
	}
}
