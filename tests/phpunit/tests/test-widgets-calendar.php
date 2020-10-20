<?php

class Widget_Calendar_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		require_once POLYLANG_DIR . '/include/api.php'; // usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang; // We use PLL()

		self::$polylang->options['hide_default'] = 0;

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );

		self::$polylang->links_model = self::$polylang->model->get_links_model();
	}

	function test_get_calendar() {
		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-05 00:00:00' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->filters_links = new PLL_Frontend_Filters_Links( self::$polylang );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$this->go_to( home_url( '/?m=200709&lang=fr' ) );
		$calendar = PLL_Widget_Calendar::get_calendar( true, false );

		$this->assertNotFalse( strpos( $calendar, '<td>4</td>' ) ); // no French post on 4th
		$this->assertFalse( strpos( $calendar, home_url( '/?m=20070904&lang=fr' ) ) ); // no French post on 4th
		$this->assertNotFalse( strpos( $calendar, home_url( '/?m=20070905&lang=fr' ) ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		$this->go_to( home_url( '/?m=200709&lang=en' ) );
		$calendar = PLL_Widget_Calendar::get_calendar( true, false );

		$this->assertNotFalse( strpos( $calendar, '<td>5</td>' ) );
		$this->assertFalse( strpos( $calendar, home_url( '/?m=20070905&lang=en' ) ) );
		$this->assertNotFalse( strpos( $calendar, home_url( '/?m=20070904&lang=en' ) ) );
	}
}
