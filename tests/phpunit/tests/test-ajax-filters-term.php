<?php

class Ajax_Filters_Term_Test extends PLL_Ajax_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function setUp() {
		parent::setUp();
		remove_all_actions( 'admin_init' ); // To save (a lot of) time as WP will attempt to update core and plugins.

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests.
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang );
		self::$polylang->terms = new PLL_CRUD_Terms( self::$polylang );
		self::$polylang->links = new PLL_Admin_Links( self::$polylang );
	}

	public function test_term_lang_choice_in_edit_category() {
		// Possible parents.
		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		// The category.
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );

		$_POST = array(
			'action'     => 'term_lang_choice',
			'_pll_nonce' => wp_create_nonce( 'pll_language' ),
			'lang'       => 'fr',
			'post_type'  => 'post',
			'taxonomy'   => 'category',
			'term_id'    => $term_id,
		);

		$_REQUEST['lang'] = $_POST['lang'];
		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'term_lang_choice' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Translations.
		$form = $xml->response[0]->translations->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$this->assertNotEmpty( $xpath->query( '//input[@id="tr_lang_en"]' )->length );
		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		// Parent dropdown.
		$dropdown = $xml->response[1]->parent->response_data;
		$this->assertNotFalse( strpos( $dropdown, 'essai cat' ) );
		$this->assertFalse( strpos( $dropdown, 'test cat' ) );

		// Flag.
		$this->assertNotFalse( strpos( $flag = $xml->response[2]->flag->response_data, 'Français' ) );
	}

	public function test_term_lang_choice_in_new_tag() {
		// Possible parents.
		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		// We need posts for the tag cloud.
		$this->factory->post->create( array( 'tags_input' => 'test' ) );
		$this->factory->post->create( array( 'tags_input' => 'essai' ) );

		// The post_tag.
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );

		$_POST = array(
			'action'     => 'term_lang_choice',
			'_pll_nonce' => wp_create_nonce( 'pll_language' ),
			'lang'       => 'fr',
			'post_type'  => 'post',
			'taxonomy'   => 'post_tag',
		);

		$_REQUEST['lang'] = $_POST['lang'];
		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'term_lang_choice' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Translations.
		$form = $xml->response[0]->translations->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$this->assertNotEmpty( $xpath->query( '//input[@id="tr_lang_en"]' )->length );
		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		// Tag cloud.
		$cloud = $xml->response[1]->tag_cloud->response_data;

		$this->assertNotFalse( strpos( $cloud, 'essai' ) );
		$this->assertFalse( strpos( $cloud, 'test' ) );

		// Flag.
		$this->assertNotFalse( strpos( $flag = $xml->response[2]->flag->response_data, 'Français' ) );
	}

	public function test_terms_not_translated() {
		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$searched = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test searched' ) );
		self::$polylang->model->term->set_language( $searched, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$_GET = array(
			'action'               => 'pll_terms_not_translated',
			'_pll_nonce'           => wp_create_nonce( 'pll_language' ),
			'term'                 => 'tes',
			'term_language'        => 'fr',
			'translation_language' => 'en',
			'post_type'            => 'post',
			'taxonomy'             => 'category',
			'term_id'              => $fr,
		);

		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'pll_terms_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( $searched, $response[0]['id'] );

		// Translate the current term.
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		// The search must contain the current translation.
		try {
			$this->_handleAjax( 'pll_terms_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 2, $response );
		$this->assertEqualSets( array( $searched, $en ), wp_list_pluck( $response, 'id' ) );
	}

	public function test_format_not_translated_term() {
		$parent = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'Parent' ) );
		self::$polylang->model->term->set_language( $parent, 'en' );

		$child = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'Child', 'parent' => $parent ) );
		self::$polylang->model->term->set_language( $child, 'en' );

		self::$polylang->set_current_language();

		// A term with a parent.
		$_GET = array(
			'action'               => 'pll_terms_not_translated',
			'_pll_nonce'           => wp_create_nonce( 'pll_language' ),
			'term'                 => 'Chi',
			'term_language'        => 'fr',
			'translation_language' => 'en',
			'post_type'            => 'post',
			'taxonomy'             => 'category',
			'term_id'              => 'undefined',
		);

		try {
			$this->_handleAjax( 'pll_terms_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( 'Parent > Child', $response[0]['value'] );

		// A term without parent.
		$_GET['term'] = 'Pa';

		try {
			$this->_handleAjax( 'pll_terms_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( 'Parent', $response[0]['value'] );
	}
}
