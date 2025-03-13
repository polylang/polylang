<?php

class WP_Importer_Test extends PLL_UnitTestCase {
	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::markTestSkippedIfFileNotExists( PLL_TEST_PLUGINS_DIR . 'wordpress-importer/wordpress-importer.php', 'This test requires the plugin WordPress Importer.' );

		parent::wpSetUpBeforeClass( $factory );

		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		require_once PLL_TEST_PLUGINS_DIR . 'wordpress-importer/wordpress-importer.php';
	}


	public function set_up() {
		global $wpdb;

		parent::set_up();

		require_once POLYLANG_DIR . '/include/api.php';

		self::$model->options['hide_default'] = 0;

		// crude but effective: make sure there's no residual data in the main tables
		foreach ( array( 'posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta' ) as $table ) {
			$wpdb->query( "DELETE FROM {$wpdb->$table}" );
		}

		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$GLOBALS['polylang'] = &$pll_admin;
	}

	public function tear_down() {
		unset( $GLOBALS['polylang'] );
		self::delete_all_languages();

		if ( file_exists( PLL_TEST_DATA_DIR . 'test-modified-import.xml' ) ) {
			unlink( PLL_TEST_DATA_DIR . 'test-modified-import.xml' );
		}

		parent::tear_down();
	}

	/**
	 * Import a WXR file.
	 *
	 * Mostly copied from WP_Import_UnitTestCase.
	 *
	 * @param string $filename    Full path of the file to import.
	 * @param array  $users       User import settings.
	 * @param bool   $fetch_files Whether or not do download remote attachments.
	 */
	protected function _import_wp( $filename, $users = array(), $fetch_files = true ) {
		$importer = new PLL_WP_Import(); // Change to our importer
		$file = realpath( $filename );
		$this->assertTrue( ! empty( $file ), 'Path to import file is empty.' );
		$this->assertTrue( is_file( $file ), 'Import file is not a file.' );

		$authors = $mapping = $new = array();
		$i = 0;

		// each user is either mapped to a given ID, mapped to a new user
		// with given login or imported using details in WXR file
		foreach ( $users as $user => $map ) {
			$authors[ $i ] = $user;
			if ( is_int( $map ) ) {
				$mapping[ $i ] = $map;
			} elseif ( is_string( $map ) ) {
				$new[ $i ] = $map;
			}

			++$i;
		}

		$_POST = array( 'imported_authors' => $authors, 'user_map' => $mapping, 'user_new' => $new );

		ob_start();
		$importer->fetch_attachments = $fetch_files;
		$importer->import( $file );
		ob_end_clean();

		$_POST = array();
	}

	public function test_simple_import() {
		$import_filepath      = PLL_TEST_DATA_DIR . 'test-import.xml';
		$import_filepath_test = PLL_TEST_DATA_DIR . 'test-modified-import.xml';

		// Prepare the imported file by replacing language id placeholders by a meaningful int.
		$import_content  = strtr(
			file_get_contents( $import_filepath ),
			array(
				'{lang_id_1}' => 172,
				'{lang_id_2}' => 174,
			)
		);
		file_put_contents( $import_filepath_test, $import_content );

		$this->_import_wp( $import_filepath_test );
		// languages
		$this->assertEqualSets( array( 'en', 'fr' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// Strings translations.
		$en_mo = new PLL_MO();
		$en_mo->import_from_db( self::$model->languages->get( 'en' ) );
		$en_mo->export_to_db( self::$model->languages->get( 'en' ) ); // To clean `PLL_MO` cache for following test.
		$this->assertSame( 'WordPress EN', $en_mo->translate( 'WordPress' ) );
		$fr_mo = new PLL_MO();
		$fr_mo->import_from_db( self::$model->languages->get( 'fr' ) );
		$en_mo->export_to_db( self::$model->languages->get( 'fr' ) );  // To clean `PLL_MO` cache for following test.
		$this->assertSame( 'WordPress FR', $fr_mo->translate( 'WordPress' ) );

		// posts
		$en = get_posts( array( 's' => 'Test', 'lang' => 'en' ) );
		$en = reset( $en );

		$fr = get_posts( array( 's' => 'Essai', 'lang' => 'fr' ) );
		$fr = reset( $fr );

		$this->assertEquals( 'en', self::$model->post->get_language( $en->ID )->slug );
		$this->assertEquals( 'fr', self::$model->post->get_language( $fr->ID )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $en->ID, 'fr' => $fr->ID ), self::$model->post->get_translations( $en->ID ) );

		// categories
		$en = get_term_by( 'name', 'Test', 'category' );
		$this->assertEquals( 'en', self::$model->term->get_language( $en->term_id )->slug );

		$fr = get_term_by( 'name', 'Essai', 'category' );
		$this->assertEquals( 'fr', self::$model->term->get_language( $fr->term_id )->slug );

		$this->assertEquals( 'en', self::$model->term->get_language( $en->term_id )->slug );
		$this->assertEquals( 'fr', self::$model->term->get_language( $fr->term_id )->slug );
		$this->assertEqualSetsWithIndex( array( 'en' => $en->term_id, 'fr' => $fr->term_id ), self::$model->term->get_translations( $en->term_id ) );
	}

	/**
	 * This test checks the import of strings translations with already existing languages.
	 * The strings translations are not the same between those of the existing languages and those of the import file.
	 */
	public function test_simple_import_with_existing_languages() {
		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		$import_filepath      = PLL_TEST_DATA_DIR . 'test-import.xml';
		$import_filepath_test = PLL_TEST_DATA_DIR . 'test-modified-import.xml';

		// Prepare the imported file by replacing language id placeholders by the id of the languages just created.
		$import_content  = strtr(
			file_get_contents( $import_filepath ),
			array(
				'{lang_id_1}' => self::$model->languages->get( 'en' )->term_id,
				'{lang_id_2}' => self::$model->languages->get( 'fr' )->term_id,
			)
		);
		file_put_contents( $import_filepath_test, $import_content );

		$this->_import_wp( $import_filepath_test );

		// Languages.
		$this->assertEqualSets( array( 'en', 'fr' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// Strings translations.
		$en_mo = new PLL_MO();
		$en_mo->import_from_db( self::$model->languages->get( 'en' ) );
		$this->assertSame( 'WordPress EN', $en_mo->translate( 'WordPress' ) );
		$fr_mo = new PLL_MO();
		$fr_mo->import_from_db( self::$model->languages->get( 'fr' ) );
		$this->assertSame( 'WordPress FR', $fr_mo->translate( 'WordPress' ) );
	}
}
