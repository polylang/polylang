<?php

class Media_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->options['media_support'] = 1;
		self::$polylang->filters_media = new PLL_Admin_Filters_Media( self::$polylang );
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );  // don't create intermediate sizes to save time
	}

	function test_upload() {
		self::$polylang->pref_lang = self::$polylang->model->get_language( 'fr' );

		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$fr = $this->factory->attachment->create_upload_object( $filename );
		$this->assertEquals( self::$polylang->pref_lang, self::$polylang->model->post->get_language( $fr ) );

		// cleanup
		wp_delete_attachment( $fr );
	}

	function test_media_translation_and_delete_attachment() {
		self::$polylang->pref_lang = self::$polylang->model->get_language( 'en' );

		$filename = dirname( __FILE__ ) . '/../data/image.jpg';
		$en = $this->factory->attachment->create_upload_object( $filename );
		$fr = self::$polylang->filters_media->create_media_translation( $en, 'fr' );

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
}
