<?php

class Ajax_Columns_Test extends PLL_Ajax_UnitTestCase {
	protected static $editor;
	protected static $contributor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor      = self::factory()->user->create( array( 'role' => 'editor' ) );
		self::$contributor = self::factory()->user->create( array( 'role' => 'contributor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
		$this->pll_admin->filters_columns = new PLL_Admin_Filters_Columns( $this->pll_admin );
	}

	public function test_post_translations() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$_POST = array(
			'action'       => 'pll_update_post_rows',
			'post_id'      => $en,
			'translations' => "$fr",
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

		$this->assertSame( 2, $xml->count(), 'The response should contain 2 elements.' );

		// First row French post.
		$element = $xml->response[0];
		$this->assertObjectHasProperty( 'row', $element, 'The element object should have a `row` property.' );
		$doc = new DomDocument();
		$doc->loadHTML( $element->row->response_data );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertSame( "post-$fr", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertStringContainsString( "post=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$this->assertStringContainsString( "post=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertSame( $fr, (int) $element->row->supplemental->post_id );

		// Second row English post.
		$element = $xml->response[1];
		$this->assertObjectHasProperty( 'row', $element, 'The element object should have a `row` property.' );
		$doc = new DomDocument();
		$doc->loadHTML( $element->row->response_data );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertSame( "post-$en", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertStringContainsString( "post=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$this->assertStringContainsString( "post=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertSame( $en, (int) $element->row->supplemental->post_id );
	}

	/**
	 * @ticket #3037 {@see https://github.com/polylang/polylang-pro/issues/3037}
	 */
	public function test_unauthorized_posts(): void {
		list( $en, $fr ) = array_values(
			self::factory()->post->create_translated(
				array(
					'post_status' => 'private',
					'post_title'  => 'Private post',
					'lang'        => 'en',
				),
				array(
					'post_title' => 'Public post',
					'lang'       => 'fr',
				)
			)
		);

		wp_set_current_user( self::$contributor );

		$_POST = array(
			'action'       => 'pll_update_post_rows',
			'post_id'      => $en,
			'translations' => "$fr",
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

		// When there is only one item to return.
		$this->assertSame( 1, $xml->count(), 'The response should contain only 1 element.' );
		$element = $xml->children()[0];
		$this->assertObjectHasProperty( 'row', $element, 'The element object should have a `row` property.' );
		$this->assertInstanceOf( SimpleXMLElement::class, $element->row, 'The `row` property should be an instance of SimpleXMLElement.' );

		// Assert the `supplemental` property.
		$this->assertObjectHasProperty( 'supplemental', $element->row, 'The `row` object should have a `supplemental` property.' );
		$this->assertInstanceOf( SimpleXMLElement::class, $element->row->supplemental, 'The `supplemental` property should be an instance of SimpleXMLElement.' );
		$this->assertObjectHasProperty( 'post_id', $element->row->supplemental, 'The `supplemental` object should have a `post_id` property.' );
		$this->assertSame( $fr, (int) $element->row->supplemental->post_id, 'The post ID should be the one of the public post.' );

		// Assert the HTML.
		$this->assertObjectHasProperty( 'response_data', $element->row, 'The `row` object should have a `response_data` property.' );

		$this->assertNotEmpty( $element->row->response_data, 'The HTML should not be empty.' );
		$this->assertStringContainsString( 'Public post', $element->row->response_data, 'The HTML should contain the title of the public post.' );
		$this->assertStringNotContainsString( 'Private post', $element->row->response_data, 'The HTML should not contain the title of the private post.' );
		$this->assertStringContainsString( 'You are not allowed to edit a translation in English', $element->row->response_data, 'The user should not be able to edit the private post.' );
		$this->assertStringContainsString( 'You are not allowed to edit this item in Français', $element->row->response_data, 'The user should not be able to edit the translation of the private post.' );
	}

	public function test_term_translations() {
		$en = self::factory()->category->create();
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->category->create();
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$_POST = array(
			'action'       => 'pll_update_term_rows',
			'term_id'      => $en,
			'translations' => "$fr",
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

		// First row English term
		$row = $xml->response[0]->row->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertSame( "tag-$en", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertStringContainsString( "tag_ID=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $fr . '"]' );
		$this->assertStringContainsString( "tag_ID=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertSame( $en, (int) $xml->response[0]->row->supplemental->term_id );

		// Second row French term
		$row = $xml->response[1]->row->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $row );
		$xpath = new DOMXpath( $doc );

		$tr = $xpath->query( '//tr' );
		$this->assertSame( "tag-$fr", $tr->item( 0 )->getAttribute( 'id' ) );

		$a = $xpath->query( '//a[@class="pll_column_flag"]' );
		$this->assertStringContainsString( "tag_ID=$fr", $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//a[@class="pll_icon_edit translation_' . $en . '"]' );
		$this->assertStringContainsString( "tag_ID=$en", $a->item( 0 )->getAttribute( 'href' ) );

		$this->assertSame( $fr, (int) $xml->response[1]->row->supplemental->term_id );
	}
}
