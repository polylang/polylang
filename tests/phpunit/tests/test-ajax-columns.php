<?php

class Ajax_Columns_Test extends PLL_Ajax_UnitTestCase {
	static $editor;

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	function setUp() {
		parent::setUp();
		remove_all_actions( 'admin_init' ); // to save ( a lot of ) time as WP will attempt to update core and plugins

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
		self::$polylang->links = new PLL_Admin_Links( self::$polylang );
		self::$polylang->filters_columns = new PLL_Admin_Filters_Columns( self::$polylang );
	}

	function tearDown() {
		parent::tearDown();

		unset( $_REQUEST, $_GET, $_POST );
	}

	/**
	 * Allows to convert some html entities to xml entities to avoid breaking simplexml_load_string
	 */
	function convert_html_to_xml( $str ) {
		$chars = array(
			'&nbsp;'  => '&#160;',
		);

		return str_replace( array_keys( $chars ), $chars, $str );
	}

	function test_post_translations() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$_POST = array(
			'action'       => 'pll_update_post_rows',
			'post_id'      => $en,
			'translations' => $fr,
			'post_type'    => 'post',
			'screen'       => 'edit-post',
			'_pll_nonce'   => wp_create_nonce( 'inlineeditnonce', '_inline_edit' ),
		);

		try {
			$this->_handleAjax( 'pll_update_post_rows' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// First row French post
		$row = $xml->response[0]->row->response_data;
		$row = simplexml_load_string( $this->convert_html_to_xml( $row ) );
		$attributes = $row->attributes();
		$this->assertEquals( "post-$fr", $attributes['id'] );

		$a = $row->xpath( '//a[@class="pll_icon_tick"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "post=$fr", (string) $attributes['href'] );

		$a = $row->xpath( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "post=$en", (string) $attributes['href'] );

		$this->assertEquals( $fr, ( string ) $xml->response[0]->row->supplemental->post_id );

		// Second row English post
		$row = $xml->response[1]->row->response_data;
		$row = simplexml_load_string( $this->convert_html_to_xml( $row ) );
		$attributes = $row->attributes();
		$this->assertEquals( "post-$en", $attributes['id'] );

		$a = $row->xpath( '//a[@class="pll_icon_tick"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "post=$en", (string) $attributes['href'] );

		$a = $row->xpath( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "post=$fr", (string) $attributes['href'] );

		$this->assertEquals( $en, ( string ) $xml->response[1]->row->supplemental->post_id );
	}

	function test_term_translations() {
		$en = $this->factory->category->create();
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->category->create();
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$_POST = array(
			'action'       => 'pll_update_term_rows',
			'term_id'      => $en,
			'translations' => $fr,
			'taxonomy'     => 'category',
			'post_type'    => 'post',
			'screen'       => 'edit-category',
			'_pll_nonce'   => wp_create_nonce( 'pll_language', '_pll_nonce' ),
		);

		try {
			$this->_handleAjax( 'pll_update_term_rows' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// First row French term
		$row = $xml->response[0]->row->response_data;
		$row = simplexml_load_string( $this->convert_html_to_xml( $row ) );
		$attributes = $row->attributes();
		$this->assertEquals( "tag-$fr", $attributes['id'] );

		$a = $row->xpath( '//a[@class="pll_icon_tick"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "tag_ID=$fr", (string) $attributes['href'] );

		$a = $row->xpath( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "tag_ID=$en", (string) $attributes['href'] );

		$this->assertEquals( $fr, ( string ) $xml->response[0]->row->supplemental->term_id );

		// Second row English term
		$row = $xml->response[1]->row->response_data;
		$row = simplexml_load_string( $this->convert_html_to_xml( $row ) );
		$attributes = $row->attributes();
		$this->assertEquals( "tag-$en", $attributes['id'] );

		$a = $row->xpath( '//a[@class="pll_icon_tick"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "tag_ID=$en", (string) $attributes['href'] );

		$a = $row->xpath( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$attributes = $a[0]->attributes();
		$this->assertContains( "tag_ID=$fr", (string) $attributes['href'] );

		$this->assertEquals( $en, ( string ) $xml->response[1]->row->supplemental->term_id );
	}
}
