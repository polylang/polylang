<?php

class Ajax_Filters_Post_Test extends PLL_Ajax_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'es_ES' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );

		$this->pll_admin->filters_post = new PLL_Admin_Filters_Post( $this->pll_admin );
		$this->pll_admin->classic_editor = new PLL_Admin_Classic_Editor( $this->pll_admin );
		$this->pll_admin->links = new PLL_Admin_Links( $this->pll_admin );
	}

	public function test_post_lang_choice() {
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin ); // We need this for categories and tags

		// categories
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test cat' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai cat' ) );
		self::$model->term->set_language( $fr, 'fr' );

		// the post
		$post_id = self::factory()->post->create();
		self::$model->post->set_language( $post_id, 'en' );

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
		$this->pll_admin->set_current_language();

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

	public function test_page_lang_choice() {
		$this->pll_admin->filters = new PLL_Admin_Filters( $this->pll_admin ); // we need this for the pages dropdown

		// possible parents
		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		// the post
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $post_id, 'en' );

		$_POST = array(
			'action'      => 'post_lang_choice',
			'_pll_nonce'  => wp_create_nonce( 'pll_language' ),
			'lang'        => 'fr',
			'post_type'   => 'page',
			'post_id'     => $post_id,
			'pll_post_id' => $post_id,
		);

		$_REQUEST['lang'] = $_POST['lang'];
		$this->pll_admin->set_current_language();

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

	public function test_posts_not_translated() {
		$en = self::factory()->post->create( array( 'post_title' => 'test english' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'test français' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$searched = self::factory()->post->create( array( 'post_title' => 'test searched' ) );
		self::$model->post->set_language( $searched, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$_GET = array(
			'action'               => 'pll_posts_not_translated',
			'_pll_nonce'           => wp_create_nonce( 'pll_language' ),
			'term'                 => 'tes',
			'post_language'        => 'fr',
			'translation_language' => 'en',
			'post_type'            => 'post',
			'pll_post_id'          => $fr,
		);

		$this->pll_admin->set_current_language();

		try {
			$this->_handleAjax( 'pll_posts_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( $searched, $response[0]['id'] );

		// translate the current post
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

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

	public function test_tricky_posts_not_translated() {
		for ( $i = 0; $i < 5; $i++ ) {
			$en = self::factory()->post->create( array( 'post_title' => "test {$i} english" ) );
			self::$model->post->set_language( $en, 'en' );

			$fr = self::factory()->post->create( array( 'post_title' => "test {$i} français" ) );
			self::$model->post->set_language( $fr, 'fr' );

			$es = self::factory()->post->create( array( 'post_title' => "test {$i} espagnol" ) );
			self::$model->post->set_language( $es, 'es' );

			self::$model->post->save_translations( $en, compact( 'en', 'fr', 'es' ) );
		}

		$searched_en = self::factory()->post->create( array( 'post_title' => 'test searched en' ) );
		self::$model->post->set_language( $searched_en, 'en' );

		$searched_es = self::factory()->post->create( array( 'post_title' => 'test searched es' ) );
		self::$model->post->set_language( $searched_es, 'es' );

		self::$model->post->save_translations(
			$searched_en,
			array(
				'en' => $searched_en,
				'es' => $searched_es,
			)
		);

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		add_filter(
			'pll_ajax_posts_not_translated_args',
			function ( $args ) {
				$args['numberposts'] = 4;
				return $args;
			}
		);

		$_GET = array(
			'action'               => 'pll_posts_not_translated',
			'_pll_nonce'           => wp_create_nonce( 'pll_language' ),
			'term'                 => 'tes',
			'post_language'        => 'fr',
			'translation_language' => 'en',
			'post_type'            => 'post',
			'pll_post_id'          => $fr,
		);

		$this->pll_admin->set_current_language();

		try {
			$this->_handleAjax( 'pll_posts_not_translated' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertCount( 1, $response );
		$this->assertEquals( $searched_en, $response[0]['id'] );
	}

	public function test_save_post_from_quick_edit() {
		$post_id = $en = self::factory()->post->create();
		self::$model->post->set_language( $post_id, 'en' );

		$es = self::factory()->post->create();
		self::$model->post->set_language( $es, 'es' );

		self::$model->post->save_translations( $en, compact( 'en', 'es' ) );

		// Switch to a free language in the translation group
		$_REQUEST = $_POST = array(
			'action'             => 'inline-save',
			'post_ID'            => $post_id,
			'inline_lang_choice' => 'fr',
			'_inline_edit'       => wp_create_nonce( 'inlineeditnonce' ),
		);

		try {
			$this->_handleAjax( 'inline-save' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}

		$this->assertEquals( 'fr', self::$model->post->get_language( $post_id )->slug );
		$this->assertEquals( 'es', self::$model->post->get_language( $es )->slug );
		$this->assertEqualSets( array( 'fr' => $post_id, 'es' => $es ), self::$model->post->get_translations( $post_id ) );
		$this->assertEqualSets( array( 'fr' => $post_id, 'es' => $es ), self::$model->post->get_translations( $es ) );

		// Switch to a *non* free language in the translation group
		$_REQUEST['inline_lang_choice'] = $_POST['inline_lang_choice'] = 'es';

		try {
			$this->_handleAjax( 'inline-save' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}

		$this->assertEquals( 'es', self::$model->post->get_language( $post_id )->slug );
		$this->assertEquals( 'es', self::$model->post->get_language( $es )->slug );
		$this->assertEquals( array( 'es' => $post_id ), self::$model->post->get_translations( $post_id ) );
		$this->assertEquals( array( 'es' => $es ), self::$model->post->get_translations( $es ) );
	}
}
