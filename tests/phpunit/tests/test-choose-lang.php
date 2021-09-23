<?php

class Choose_Lang_Test extends PLL_UnitTestCase {
	/**
	 * @var PLL_Frontend
	 */
	private $frontend;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$model->post->register_taxonomy();
	}

	function tear_down() {
		self::delete_all_languages();

		parent::tear_down();
	}

	function set_up() {
		parent::set_up();

		// FIXME: Tests fail when trying to use a new instance of PLL_Admin_Model
		$links_model = self::$model->get_links_model();
		$this->frontend = new PLL_Frontend( $links_model );
	}

	function accepted_languages_provider() {
		return array(
			array( 'fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3', 'en' ),
			array( 'en-us;q=0.5,de-de', 'de' ),
			array( 'de-de', 'de' ),
			array( 'es-es,fr-fr;q=0.8', false ),
			array( 'de;q=0.3,en;q=1', 'en' ),
			array( 'de;q=0.3,en;q=1.0', 'en' ),
			array( 'de;q=0,es-es;', false ),
		);
	}

	/**
	 * @dataProvider accepted_languages_provider
	 * @param string      $accept_languages_header Accept-Language HTTP header like those issued by web browsers.
	 * @param string|bool $expected_preference Expected results of our preferred browser language detection.
	 */
	function test_browser_preferred_language( $accept_languages_header, $expected_preference ) {
		self::create_language( 'en_US' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'fr_FR' );

		// Only languages with posts will be accepted
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'de' );

		self::$model->clean_languages_cache();

		$choose_lang = new PLL_Choose_Lang_Url( $this->frontend );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept_languages_header;
		$this->assertEquals( $expected_preference, $choose_lang->get_preferred_browser_language() );
	}

	function accepted_languages_with_same_slug_provider() {
		return array(
			array( 'en-gb;q=0.8,en-us;q=0.5,en;q=0.3', 'en' ),
			array( 'en-us;q=0.8,en-gb;q=0.5,en;q=0.3', 'us' ),
			array( 'en;q=0.8,fr;q=0.5', 'us' ),
		);
	}

	/**
	 * @since 1.8 Bugfix
	 * @see https://wordpress.org/support/topic/browser-detection
	 *
	 * @dataProvider accepted_languages_with_same_slug_provider
	 * @param string      $accept_languages_header Accept-Language HTTP header like those issued by web browsers.
	 * @param string|bool $expected_preference Expected results of our preferred browser language detection.
	 */
	function test_browser_preferred_language_with_same_slug( $accept_languages_header, $expected_preference ) {
		self::create_language( 'en_GB', array( 'term_group' => 2 ) );
		self::create_language( 'en_US', array( 'slug' => 'us', 'term_group' => 1 ) );

		// only languages with posts will be accepted
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'us' );

		self::$model->clean_languages_cache(); // FIXME foor some reason the cache is not clean before (resulting in wrong count)

		$choose_lang = new PLL_Choose_Lang_Url( $this->frontend );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept_languages_header;
		$this->assertEquals( $expected_preference, $choose_lang->get_preferred_browser_language() );
	}

}
