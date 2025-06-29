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

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests.

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->filters_term = new PLL_Admin_Filters_Term( $this->pll_admin );
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
	}

	public function test_term_lang_choice_in_edit_category() {
		// Possible parents.
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$model->term->set_language( $fr, 'fr' );

		// The category.
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );

		$_POST = array(
			'action'     => 'term_lang_choice',
			'_pll_nonce' => wp_create_nonce( 'pll_language' ),
			'lang'       => 'fr',
			'post_type'  => 'post',
			'taxonomy'   => 'category',
			'term_id'    => $term_id,
		);

		$_REQUEST['lang'] = $_POST['lang'];
		$this->pll_admin->set_current_language();

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
		$this->assertStringContainsString( 'essai cat', $dropdown );
		$this->assertStringNotContainsString( 'test cat', $dropdown );

		// Flag.
		$this->assertStringContainsString( 'Français', $xml->response[2]->flag->response_data );
	}

	public function test_term_lang_choice_in_new_tag() {
		// Possible parents.
		$en = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		// We need posts for the tag cloud.
		self::factory()->post->create( array( 'tags_input' => 'test' ) );
		self::factory()->post->create( array( 'tags_input' => 'essai' ) );

		$_POST = array(
			'action'     => 'term_lang_choice',
			'_pll_nonce' => wp_create_nonce( 'pll_language' ),
			'lang'       => 'fr',
			'post_type'  => 'post',
			'taxonomy'   => 'post_tag',
		);

		$_REQUEST['lang'] = $_POST['lang'];
		$this->pll_admin->set_current_language();

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

		$this->assertStringContainsString( 'essai', $cloud );
		$this->assertStringNotContainsString( 'test', $cloud );

		// Flag.
		$this->assertStringContainsString( 'Français', $xml->response[2]->flag->response_data );
	}

	public function test_terms_not_translated() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$model->term->set_language( $fr, 'fr' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$searched = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test searched' ) );
		self::$model->term->set_language( $searched, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $fr, 'fr' );

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

		$this->pll_admin->set_current_language();

		try {
			$this->_handleAjax( 'pll_terms_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( $searched, $response[0]['id'] );

		// Translate the current term.
		$en = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $en, 'en' );

		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

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
		$parent = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Parent' ) );
		self::$model->term->set_language( $parent, 'en' );

		$child = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Child', 'parent' => $parent ) );
		self::$model->term->set_language( $child, 'en' );

		$this->pll_admin->set_current_language();

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

	public function test_inline_save_tax_with_same_slugs() {
		// Load WP ajax response action.
		require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
		add_action( 'wp_ajax_inline-save-tax', 'wp_ajax_inline_save_tax', 1 );

		wp_set_current_user( self::$editor ); // Set a user to pass check_ajax_referer test.

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Test' ) );
		self::$model->term->set_language( $en, 'en' );

		// Create a category translation with the same name (i.e. the same slug).
		$_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);
		$_REQUEST = $_POST;

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Test' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$term = get_term( $fr );
		$this->assertInstanceOf( WP_Term::class, $term, 'Expected the category to be an instance of WP_Term.' );
		$this->assertSame( 'Test', $term->name, 'Expected the title of the category in secondary language to match the one sent via POST.' );
		$this->assertSame( 'test-fr', $term->slug, 'Expected the slug of the category in secondary language to be suffixed.' );
		$this->assertSame( 'fr', self::$model->term->get_language( $fr )->slug, 'The langue is not set correctly for the category.' );


		$_POST = array(
			'name'               => 'More test',
			'slug'               => 'test-fr',
			'inline_lang_choice' => 'fr',
			'_inline_edit'       => wp_create_nonce( 'taxinlineeditnonce', '_inline_edit' ),
			'taxonomy'           => 'category',
			'post_type'          => 'post',
			'action'             => 'inline-save-tax',
			'tax_type'           => 'tag',
			'tax_ID'             => $fr,
			'pll_ajax_backend'   => '1',
			'description'        => '',
		);
		$_REQUEST = $_POST;

		try {
			$this->_handleAjax( 'inline-save-tax' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$term = get_term( $fr );
		$this->assertInstanceOf( WP_Term::class, $term, 'Expected the category to be an instance of WP_Term.' );
		$this->assertSame( 'More test', $term->name, 'Expected the title of the category in secondary language to match the one sent via POST.' );
		$this->assertSame( 'test-fr', $term->slug, 'Expected the slug of the category in secondary language to be suffixed.' );
	}

	public function test_inline_save_tax_with_new_slugs() {
		// Load WP ajax response action.
		require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
		add_action( 'wp_ajax_inline-save-tax', 'wp_ajax_inline_save_tax', 1 );

		wp_set_current_user( self::$editor ); // Set a user to pass check_ajax_referer test.

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Test' ) );
		self::$model->term->set_language( $en, 'en' );

		// Create a category translation with the same name (i.e. the same slug).
		$_POST = array(
			'action'           => 'add-tag',
			'term_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
		);
		$_REQUEST = $_POST;

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'Test' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$term = get_term( $fr );
		$this->assertInstanceOf( WP_Term::class, $term, 'Expected the category to be an instance of WP_Term.' );
		$this->assertSame( 'Test', $term->name, 'Expected the title of the category in secondary language to match the one sent via POST.' );
		$this->assertSame( 'test-fr', $term->slug, 'Expected the slug of the category in secondary language to be suffixed.' );
		$this->assertSame( 'fr', self::$model->term->get_language( $fr )->slug, 'The langue is not set correctly for the category.' );


		$_POST = array(
			'name'               => 'Test',
			'slug'               => 'test-new-fr',
			'inline_lang_choice' => 'fr',
			'_inline_edit'       => wp_create_nonce( 'taxinlineeditnonce', '_inline_edit' ),
			'taxonomy'           => 'category',
			'post_type'          => 'post',
			'action'             => 'inline-save-tax',
			'tax_type'           => 'tag',
			'tax_ID'             => $fr,
			'pll_ajax_backend'   => '1',
			'description'        => '',
		);
		$_REQUEST = $_POST;

		try {
			$this->_handleAjax( 'inline-save-tax' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$term = get_term( $fr );
		$this->assertInstanceOf( WP_Term::class, $term, 'Expected the category to be an instance of WP_Term.' );
		$this->assertSame( 'Test', $term->name, 'Expected the title of the category in secondary language to remain the same.' );
		$this->assertSame( 'test-new-fr', $term->slug, 'Expected the slug of the category in secondary language to be updated.' );
	}
}
