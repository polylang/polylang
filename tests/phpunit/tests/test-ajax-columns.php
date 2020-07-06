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
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertEquals( "post-$fr", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertContains( "post=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$this->assertContains( "post=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertEquals( $fr, (string) $xml->response[0]->row->supplemental->post_id );

		// Second row English post
		$row = $xml->response[1]->row->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertEquals( "post-$en", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertContains( "post=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$this->assertContains( "post=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertEquals( $en, (string) $xml->response[1]->row->supplemental->post_id );
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
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertEquals( "tag-$fr", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertContains( "tag_ID=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$this->assertContains( "tag_ID=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertEquals( $fr, (string) $xml->response[0]->row->supplemental->term_id );

		// Second row English term
		$row = $xml->response[1]->row->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertEquals( "tag-$en", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertContains( "tag_ID=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$this->assertContains( "tag_ID=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertEquals( $en, (string) $xml->response[1]->row->supplemental->term_id );
	}
}
