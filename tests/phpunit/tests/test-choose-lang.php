<?php

class Choose_Lang_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$model->post->register_taxonomy();
	}

	function tearDown() {
		self::delete_all_languages();

		parent::tearDown();
	}

	function setUp() {
		parent::setUp();

		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
	}

	function test_browser_preferred_language() {
		self::create_language( 'en_US' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'fr_FR' );

		// Only languages with posts will be accepted
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'de' );

		$choose_lang = new PLL_Choose_Lang_Url( $this->frontend );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3';
		$this->assertEquals( 'en', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-us;q=0.5,de-de'; // just to test sorting
		$this->assertEquals( 'de', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de';
		$this->assertEquals( 'de', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-es,fr-fr;q=0.8';
		$this->assertFalse( $choose_lang->get_preferred_browser_language() );

		// Bugs fixed in 2.4 with exotic values of q
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de;q=0.3,en;q=1';
		$this->assertEquals( 'en', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de;q=0.3,en;q=1.0';
		$this->assertEquals( 'en', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de;q=0,es-es;';
		$this->assertFalse( $choose_lang->get_preferred_browser_language() );
	}

	// bug fixed in 1.8
	// see https://wordpress.org/support/topic/browser-detection
	function test_browser_preferred_language_with_same_slug() {
		self::create_language( 'en_GB', array( 'term_group' => 2 ) );
		self::create_language( 'en_US', array( 'slug' => 'us', 'term_group' => 1 ) );

		// only languages with posts will be accepted
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'us' );

		self::$model->clean_languages_cache(); // FIXME foor some reason the cache is not clean before (resulting in wrong count)

		$choose_lang = new PLL_Choose_Lang_Url( $this->frontend );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-gb;q=0.8,en-us;q=0.5,en;q=0.3';
		$this->assertEquals( 'en', $choose_lang->get_preferred_browser_language() );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-us;q=0.8,en-gb;q=0.5,en;q=0.3';
		$this->assertEquals( 'us', $choose_lang->get_preferred_browser_language() );

		// when the exact locale is not specified, return the preferred language according to the order
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en;q=0.8,fr;q=0.5';
		$this->assertEquals( 'us', $choose_lang->get_preferred_browser_language() );
	}

	/**
	 * @since 3.0
	 * @see https://github.com/polylang/polylang/issues/591
	 */
	function test_browser_preferred_language_with_script_tag() {
		self::create_language( 'zh_CN', array( 'slug' => 'zh' ) );
		self::create_language( 'zh_HK', array( 'slug' => 'zh-hk' ) );
		self::create_language( 'zh_HK', array( 'slug' => 'zh-hant-hk' ) );

		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'zh' );
		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'zh-hk' );
		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'zh-hant-hk' );

		self::$polylang->model->clean_languages_cache(); // FIXME for some reason the cache is not clean before (resulting in wrong count)

		$choose_lang = new PLL_Choose_Lang_Url( self::$polylang );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'zh-Hant-HK,zh-HK;q=0.8,zh;q=0.5';
		$this->assertEquals( 'zh-hk', $choose_lang->get_preferred_browser_language() );
	}
}
