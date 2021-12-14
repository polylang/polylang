<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class Rest_Request_Test extends PLL_UnitTestCase {
	// Adds Mockery expectations to the PHPUnit assertions count.
	use MockeryPHPUnitIntegration;

	/**
	 * @var string
	 */
	public $structure = '/%postname%/';

	/**
	 * Initialization before all tests run.
	 *
	 * @param  WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		Monkey\setUp();

		$links_model         = self::$model->get_links_model();
		$this->frontend      = new PLL_REST_Request( $links_model );
		$GLOBALS['polylang'] = &$this->frontend;

		Functions\when( 'pll_filter_input' )->alias(
			function ( $type, $var_name, $filter, $options ) {
				return INPUT_GET === $type && 'rest_route' === $var_name ? '/wp/v2/foobar' : null;
			}
		);
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		Monkey\tearDown();
		parent::tear_down();

		unset( $GLOBALS['polylang'] );
	}

	public function test_should_define_language_when_language_is_valid() {
		self::$model->options['default_lang'] = 'en';

		$_GET['lang'] = 'fr';

		$this->frontend->init();

		$this->assertInstanceOf( 'PLL_Language', $this->frontend->curlang );
		$this->assertSame( 'fr', $this->frontend->curlang->slug );
	}

	public function test_should_define_default_language_when_language_is_invalid() {
		self::$model->options['default_lang'] = 'en';

		$_GET['lang'] = 'it';

		$this->frontend->init();

		$this->assertInstanceOf( 'PLL_Language', $this->frontend->curlang );
		$this->assertSame( 'en', $this->frontend->curlang->slug );
	}

	public function test_should_not_define_default_language_when_default_language_is_invalid() {
		self::$model->options['default_lang'] = 'es';

		$_GET['lang'] = 'it';

		$this->frontend->init();

		$this->assertFalse( $this->frontend->curlang );
	}

	public function test_should_not_define_language_when_not_sent() {
		self::$model->options['default_lang'] = 'en';

		unset( $_GET['lang'] );

		$this->frontend->init();

		$this->assertNull( $this->frontend->curlang );
	}
}
