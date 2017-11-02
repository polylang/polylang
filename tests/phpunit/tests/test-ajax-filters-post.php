<?php

class Ajax_Filters_Post_Test extends PLL_Ajax_UnitTestCase {
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
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->filters_post = new PLL_Admin_Filters_Post( self::$polylang );
		self::$polylang->links = new PLL_Admin_Links( self::$polylang );
	}

	function tearDown() {
		parent::tearDown();

		unset( $_REQUEST, $_GET, $_POST );
	}

	function test_post_lang_choice() {
		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang ); // we need this for categories and tags

		// categories
		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		// the post
		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$_POST = array(
			'action'      => 'post_lang_choice',
			'_pll_nonce'  => wp_create_nonce( 'pll_language' ),
			'lang'        => 'fr',
			'post_type'   => 'post',
			'post_id'     => $post_id,
			'pll_post_id' => $post_id,
			'taxonomies'  => array( 'category', 'post_tag' ),
		);

		$_REQUEST['lang'] = $_POST['lang'];
		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'post_lang_choice' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// translations
		$form = $xml->response[0]->translations->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_tr_lang[en]"]' );
		$this->assertEquals( 0, $input->item( 0 )->getAttribute( 'value' ) );

		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		// categories dropdown
		$dropdown = $xml->response[1]->taxonomy->supplemental->dropdown;
		$this->assertNotFalse( strpos( $dropdown, 'essai cat' ) );
		$this->assertFalse( strpos( $dropdown, 'test cat' ) );

		// flag
		$this->assertNotFalse( strpos( $flag = $xml->response[3]->flag->response_data, 'Français' ) );
	}

	function test_page_lang_choice() {
		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang ); // we need this for the pages dropdown

		// possible parents
		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		// the post
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$_POST = array(
			'action'      => 'post_lang_choice',
			'_pll_nonce'  => wp_create_nonce( 'pll_language' ),
			'lang'        => 'fr',
			'post_type'   => 'page',
			'post_id'     => $post_id,
			'pll_post_id' => $post_id,
		);

		$_REQUEST['lang'] = $_POST['lang'];
		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'post_lang_choice' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// translations
		$form = $xml->response[0]->translations->response_data;
		$doc = new DomDocument();
		$doc->loadHTML( $form );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_tr_lang[en]"]' );
		$this->assertEquals( 0, $input->item( 0 )->getAttribute( 'value' ) );

		$this->assertEmpty( $xpath->query( '//input[@id="tr_lang_fr"]' )->length );

		// parents
		$dropdown = $xml->response[1]->pages->response_data;
		$this->assertNotFalse( strpos( $dropdown, 'essai' ) );
		$this->assertFalse( strpos( $dropdown, 'test' ) );

		// flag
		$this->assertNotFalse( strpos( $flag = $xml->response[2]->flag->response_data, 'Français' ) );
	}

	function test_posts_not_translated() {
		$en = $this->factory->post->create( array( 'post_title' => 'test english' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'test français' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$searched = $this->factory->post->create( array( 'post_title' => 'test searched' ) );
		self::$polylang->model->post->set_language( $searched, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$_GET = array(
			'action'               => 'pll_posts_not_translated',
			'_pll_nonce'           => wp_create_nonce( 'pll_language' ),
			'term'                 => 'tes',
			'post_language'        => 'fr',
			'translation_language' => 'en',
			'post_type'            => 'post',
			'pll_post_id'          => $fr,
		);

		self::$polylang->set_current_language();

		try {
			$this->_handleAjax( 'pll_posts_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( $searched, $response[0]['id'] );

		// translate the current post
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// the search must contain the current translation
		try {
			$this->_handleAjax( 'pll_posts_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 2, $response );
		$this->assertEqualSets( array( $searched, $en ), wp_list_pluck( $response, 'id' ) );
	}
}
