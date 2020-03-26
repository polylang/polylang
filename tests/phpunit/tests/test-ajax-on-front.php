<?php

class Ajax_On_Front_Test extends PLL_Ajax_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		// Copy language file
		@mkdir( DIR_TESTDATA );
		@mkdir( WP_LANG_DIR );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.mo', WP_LANG_DIR . '/fr_FR.mo' );
	}

	function setUp() {
		parent::setUp();
		remove_all_actions( 'admin_init' ); // to save ( a lot of ) time as WP will attempt to update core and plugins
	}

	function tearDown() {
		parent::tearDown();

		unload_textdomain( 'default' );
	}

	function _ajax_test_locale() {
		load_default_textdomain();
		wp_die( wp_json_encode( __( 'Dashboard' ) ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
	}

	function test_locale_for_logged_in_user() {
		wp_set_current_user( 1 );
		update_user_meta( 1, 'locale', 'en_US' );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		new PLL_Frontend_Filters( self::$polylang );

		add_action( 'wp_ajax_test_locale', array( $this, '_ajax_test_locale' ) );

		$_REQUEST['action'] = 'test_locale';

		try {
			$this->_handleAjax( 'test_locale' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $e->getMessage(), true );
			unset( $e );
		}

		$this->assertEquals( 'Tableau de bord', $response );
	}
}
