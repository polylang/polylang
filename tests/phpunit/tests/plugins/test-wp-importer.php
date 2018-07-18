<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( file_exists( $_tests_dir . '/../wordpress-importer/wordpress-importer.php' ) ) {

	class WP_Importer_Test extends PLL_UnitTestCase {

		function setUp() {
			parent::setUp();

			require_once PLL_INC . '/api.php';
			$GLOBALS['polylang'] = &self::$polylang; // we still use the global $polylang
			self::$polylang->options['hide_default'] = 0;
			update_option( 'polylang', self::$polylang->options ); // make sure we have options in DB ( needed by PLL_WP_Import )

			if ( ! defined( 'WP_IMPORTING' ) ) {
				define( 'WP_IMPORTING', true );
			}

			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				define( 'WP_LOAD_IMPORTERS', true );
			}

			global $_tests_dir;
			require_once $_tests_dir . '/../wordpress-importer/wordpress-importer.php';

			global $wpdb;
			// crude but effective: make sure there's no residual data in the main tables
			foreach ( array( 'posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta' ) as $table ) {
				$wpdb->query( "DELETE FROM {$wpdb->$table}" );
			}
		}

		function tearDown() {
			self::delete_all_languages();

			parent::tearDown();
		}

		// mostly copied from WP_Import_UnitTestCase
		protected function _import_wp( $filename, $users = array(), $fetch_files = true ) {
			$importer = new PLL_WP_Import(); // Change to our importer
			$file = realpath( $filename );
			assert( '!empty( $file )' );
			assert( 'is_file( $file )' );

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

				$i++;
			}

			$_POST = array( 'imported_authors' => $authors, 'user_map' => $mapping, 'user_new' => $new );

			ob_start();
			$importer->fetch_attachments = $fetch_files;
			$importer->import( $file );
			ob_end_clean();

			self::$polylang->options = get_option( 'polylang' );
			$_POST = array();
		}

		function test_simple_import() {
			$this->_import_wp( dirname( __FILE__ ) . '/../../data/test-import.xml' );

			// languages
			$this->assertEqualSets( array( 'en', 'fr' ), self::$polylang->model->get_languages_list( array( 'fields' => 'slug' ) ) );

			// posts
			$en = get_posts( array( 's' => 'Test', 'lang' => 'en' ) );
			$en = reset( $en );

			$fr = get_posts( array( 's' => 'Essai', 'lang' => 'fr' ) );
			$fr = reset( $fr );

			$this->assertEquals( 'en', self::$polylang->model->post->get_language( $en->ID )->slug );
			$this->assertEquals( 'fr', self::$polylang->model->post->get_language( $fr->ID )->slug );
			$this->assertEqualSetsWithIndex( array( 'en' => $en->ID, 'fr' => $fr->ID ), self::$polylang->model->post->get_translations( $en->ID ) );

			// categories
			$en = get_term_by( 'name', 'Test', 'category' );
			$this->assertEquals( 'en', self::$polylang->model->term->get_language( $en->term_id )->slug );

			$fr = get_term_by( 'name', 'Essai', 'category' );
			$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $fr->term_id )->slug );

			$this->assertEquals( 'en', self::$polylang->model->term->get_language( $en->term_id )->slug );
			$this->assertEquals( 'fr', self::$polylang->model->term->get_language( $fr->term_id )->slug );
			$this->assertEqualSetsWithIndex( array( 'en' => $en->term_id, 'fr' => $fr->term_id ), self::$polylang->model->term->get_translations( $en->term_id ) );
		}
	}

} // file_exists
