<?php

class Media_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->options['media_support'] = 1;
		self::$polylang->filters_media = new PLL_Admin_Filters_Media( self::$polylang );
		self::$polylang->posts = new PLL_CRUD_Posts( self::$polylang );
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );  // don't create intermediate sizes to save time
	}

	function test_upload() {
		self::$polylang->pref_lang = self::$polylang->model->get_language( 'fr' );

		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$fr = $this->factory->attachment->create_upload_object( $filename );
		$this->assertEquals( self::$polylang->pref_lang->slug, self::$polylang->model->post->get_language( $fr )->slug );

		// cleanup
		wp_delete_attachment( $fr );
	}

	function test_media_translation_and_delete_attachment() {
		self::$polylang->pref_lang = self::$polylang->model->get_language( 'en' );

		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$en = $this->factory->attachment->create_upload_object( $filename );
		$fr = self::$polylang->posts->create_media_translation( $en, 'fr' );

		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $fr )->slug );
		$this->assertEquals( self::$polylang->model->post->get_translation( $en, 'fr' ), $fr );

		$data = wp_get_attachment_metadata( $en );
		$uploads_dir = wp_upload_dir();
		$filename = $uploads_dir['basedir'] . '/' . $data['file'];

		// deleting a translation does not delete the file
		wp_delete_attachment( $en );
		$this->assertFileExists( $filename );

		// deleting all translations deletes the file
		wp_delete_attachment( $fr );
		$this->assertFileNotExists( $filename );
	}

	function test_attachment_fields_to_edit() {
		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$fr = $this->factory->attachment->create_upload_object( $filename );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$fields = get_attachment_fields_to_edit( $fr );
		$this->assertEquals( 'Language', $fields['language']['label'] );

		$doc = new DomDocument();
		$doc->loadHTML( $fields['language']['html'] );
		$xpath = new DOMXpath( $doc );

		$selected = $xpath->query( '//option[@selected="selected"]' );
		$this->assertEquals( 'fr', $selected->item( 0 )->getAttribute( 'value' ) );

		// Don't use on the Edit Media panel
		$GLOBALS['pagenow'] = 'post.php';
		$fields = get_attachment_fields_to_edit( $fr );
		$this->assertFalse( isset( $fields['language'] ) );
	}

	function test_attachment_fields_to_save() {
		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$en = $this->factory->attachment->create_upload_object( $filename );
		self::$polylang->model->post->set_language( $en, 'en' );
		$fr = $this->factory->attachment->create_upload_object( $filename );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor ); // Set a user to pass current_user_can tests

		$_REQUEST = $_POST = array(
			'post_ID'       => $fr,
			'post_title'    => 'Test image',
			'attachments'   => array( $fr => array( 'language' => 'fr' ) ),
			'media_tr_lang' => array( 'en' => $en ),
			'_pll_nonce'    => wp_create_nonce( 'pll_language' ),
		);
		edit_post();

		$this->assertEquals( 'en', self::$polylang->model->post->get_language( $en )->slug );
		$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $fr )->slug );
		$this->assertEqualSets( array( 'en' => $en, 'fr' => $fr ), self::$polylang->model->post->get_translations( $en ) );
	}

	function test_create_media_translation_with_slashes() {
		$slash_2 = '\\\\';
		$en = $this->factory->attachment->create(
			array(
				'post_title'   => $slash_2,
				'post_content' => $slash_2,
				'post_excerpt' => $slash_2,
			)
		);
		add_post_meta( $en, '_wp_attachment_image_alt', $slash_2 );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = self::$polylang->posts->create_media_translation( $en, 'fr' );
		$post = get_post( $fr );
		$this->assertEquals( wp_unslash( $slash_2 ), $post->post_title );
		$this->assertEquals( wp_unslash( $slash_2 ), $post->post_content );
		$this->assertEquals( wp_unslash( $slash_2 ), $post->post_excerpt );
		$this->assertEquals( wp_unslash( $slash_2 ), get_post_meta( $fr, '_wp_attachment_image_alt', true ) );
	}
}
