<?php

class Ajax_On_Front_Test extends PLL_Ajax_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'es_ES' );
	}

	public function set_up() {
		parent::set_up();
		remove_all_actions( 'admin_init' ); // to save ( a lot of ) time as WP will attempt to update core and plugins
	}

	public function tear_down() {
		parent::tear_down();

		unload_textdomain( 'default' );
	}

	public function _ajax_test_locale() {
		load_default_textdomain();
		_e( 'Invalid parameter.' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
	}

	public function test_locale_for_logged_in_user() {
		wp_set_current_user( 1 );
		update_user_meta( 1, 'locale', 'en_US' );

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->curlang = self::$model->get_language( 'es' );
		new PLL_Frontend_Filters( $frontend );

		add_action( 'wp_ajax_test_locale', array( $this, '_ajax_test_locale' ) );

		$_REQUEST['action'] = 'test_locale';

		$this->_handleAjax( 'test_locale' );

		$this->assertEquals( 'Parámetro no válido. ', $this->_last_response );
	}
}
