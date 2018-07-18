<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( file_exists( $_tests_dir . '../jetpack/jetpack.php' ) ) {

	require_once $_tests_dir . '../jetpack/functions.opengraph.php';

	class Jetpack_Test extends PLL_UnitTestCase {

		static function wpSetUpBeforeClass() {
			parent::wpSetUpBeforeClass();

			self::create_language( 'en_US' );
			self::create_language( 'fr_FR' );
		}

		function setUp() {
			parent::setUp();

			global $_tests_dir;
			require_once PLL_INC . '/api.php'; // usually loaded only if an instance of Polylang exists
			require_once $_tests_dir . '../jetpack/jetpack.php';

			$GLOBALS['polylang'] = &self::$polylang; // we still use the global $polylang
			self::$polylang = new PLL_Frontend( self::$polylang->links_model );
			self::$polylang->init();
		}

		function tearDown() {
			parent::tearDown();

			unset( $GLOBALS['polylang'] );
		}

		function test_opengraph() {
			// create posts to get something  on home page
			$en = $this->factory->post->create();
			self::$polylang->model->post->set_language( $en, 'en' );

			$fr = $this->factory->post->create();
			self::$polylang->model->post->set_language( $fr, 'fr' );

			$this->go_to( home_url( '/?lang=fr' ) );
			self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

			do_action_ref_array( 'pll_init', array( &self::$polylang ) );

			ob_start();
			jetpack_og_tags();
			$output = ob_get_clean();

			$this->assertNotFalse( strpos( $output, '<meta property="og:locale" content="fr_FR" />' ) );
			$this->assertFalse( strpos( $output, '<meta property="og:locale:alternate" content="fr_FR" />' ) ); // only for alternate languages
			$this->assertNotFalse( strpos( $output, '<meta property="og:locale:alternate" content="en_US" />' ) );
		}
	}

} // file_exists
