<?php

class PLL_Domain_UnitTestCase extends PLL_UnitTestCase {
	protected $structure = '/%postname%/';
	protected $hosts;
	protected static $server;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$server = $_SERVER; // backup

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE' );
	}

	static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		$_SERVER = self::$server;
	}

	function test_add_language_to_link() {
		$url = $this->hosts['en'] . '/test/';

		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->add_language_to_link( $url, self::$model->get_language( 'fr' ) ) );
	}

	function test_double_add_language_to_link() {
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->add_language_to_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'fr' ) ) );
	}

	function test_remove_language_from_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_language_from_link( $this->hosts['en'] . '/test/' ) );
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_language_from_link( $this->hosts['fr'] . '/test/' ) );
	}

	function test_switch_language_in_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['de'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['fr'] . '/test/', self::$model->get_language( 'de' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->switch_language_in_link( $this->hosts['en'] . '/test/', self::$model->get_language( 'fr' ) ) );
	}

	function test_add_paged_to_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/page/2/', $this->links_model->add_paged_to_link( $this->hosts['en'] . '/test/', 2 ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/page/2/', $this->links_model->add_paged_to_link( $this->hosts['fr'] . '/test/', 2 ) );
	}

	function test_remove_paged_from_link() {
		$this->assertEquals( $this->hosts['en'] . '/test/', $this->links_model->remove_paged_from_link( $this->hosts['en'] . '/test/page/2/' ) );
		$this->assertEquals( $this->hosts['fr'] . '/test/', $this->links_model->remove_paged_from_link( $this->hosts['fr'] . '/test/page/2/' ) );
	}

	function test_get_language_from_url() {
		// hack $_SERVER
		$server = $_SERVER;
		$_SERVER['REQUEST_URI'] = '/test/';
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$this->assertEquals( 'fr', $this->links_model->get_language_from_url() );

		// clean up
		$_SERVER = $server;
	}

	function test_home_url() {
		$this->assertEquals( $this->hosts['en'] . '/', $this->links_model->home_url( self::$model->get_language( 'en' ) ) );
		$this->assertEquals( $this->hosts['fr'] . '/', $this->links_model->home_url( self::$model->get_language( 'fr' ) ) );
	}

	function test_allowed_redirect_hosts() {
		$hosts = str_replace( 'http://', '', array_values( $this->hosts ) );
		$this->assertEquals( $hosts, $this->links_model->allowed_redirect_hosts( array() ) );
		$this->assertEquals( $this->hosts['fr'], wp_validate_redirect( $this->hosts['fr'] ) );
	}

	function test_upload_dir() {
		// Hack $_SERVER.
		$server = $_SERVER;
		$_SERVER['REQUEST_URI'] = '/test/';
		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['fr'], PHP_URL_HOST );
		$uploads = wp_get_upload_dir(); // Since WP 4.5.

		$this->assertContains( $this->hosts['fr'], $uploads['url'] );
		$this->assertContains( $this->hosts['fr'], $uploads['baseurl'] );

		$_SERVER['HTTP_HOST'] = wp_parse_url( $this->hosts['en'], PHP_URL_HOST );
		$uploads = wp_get_upload_dir(); // Since WP 4.5.

		$this->assertContains( $this->hosts['en'], $uploads['url'] );
		$this->assertContains( $this->hosts['en'], $uploads['baseurl'] );

		// Clean up.
		$_SERVER = $server;
	}
}
