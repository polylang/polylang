<?php

class Parent_Page_Test extends PLL_UnitTestCase {
	static $editor;

	static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	function setUp() {
		parent::setUp();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests.

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );

	}

	public function test_parent_page_with_existing_translation_when_changing_post_language() {
		// Language set from parent
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$child_page_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $en ) );
		$this->assertEquals( 'en', self::$model->post->get_language( $child_page_id )->slug );

		// Change the child post language.
		// Simulate the language is changed.
		self::$model->post->set_language( $child_page_id, 'fr' );
		// Before the post is updated.
		$GLOBALS['post_type'] = 'page';
		$_REQUEST = $_POST = array(
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_ID'          => $child_page_id,
		);
		do_action( 'load-post.php' );
		edit_post();

		$child_page = get_post( $child_page_id );
		$this->assertEquals( 'fr', self::$model->post->get_language( $child_page_id )->slug );
		$this->assertEquals( get_post( $child_page_id )->post_parent, $fr );

	}

	public function test_parent_page_with_no_translation_when_changing_post_language() {
		// Language set from parent
		$en = $this->factory->post->create( array( 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$child_page_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $en ) );
		$this->assertEquals( 'en', self::$model->post->get_language( $child_page_id )->slug );

		// Change the child post language.
		// Simulate the language is changed.
		self::$model->post->set_language( $child_page_id, 'fr' );
		// Before the post is updated.
		$GLOBALS['post_type'] = 'page';
		$_REQUEST = $_POST = array(
			'post_lang_choice' => 'fr',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_ID'          => $child_page_id,
		);
		do_action( 'load-post.php' );
		edit_post();

		$child_page = get_post( $child_page_id );
		$this->assertEquals( 'fr', self::$model->post->get_language( $child_page_id )->slug );
		$this->assertEquals( get_post( $child_page_id )->post_parent, 0 );

	}
}
