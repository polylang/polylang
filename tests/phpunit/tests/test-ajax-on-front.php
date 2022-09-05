<?php

class Ajax_On_Front_Test extends PLL_Ajax_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		// Copy the language file.
		@mkdir( DIR_TESTDATA );
		@mkdir( WP_LANG_DIR );
		copy( dirname( __FILE__ ) . '/../data/fr_FR.mo', WP_LANG_DIR . '/fr_FR.mo' );
	}

	public function tear_down() {
		parent::tear_down();

		$_REQUEST = array();
		$_COOKIE  = array();
		unload_textdomain( 'default' );
	}

	public function _ajax_test_locale() {
		load_default_textdomain();
		wp_send_json( __( 'Dashboard' ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
	}

	/**
	 * @dataProvider ajax_on_front_provider
	 *
	 * @param string $language    Language code of the ajax request.
	 * @param string $translation "Dashboard" translated in the current language.
	 * @param int    $user_id     1 of logged in user, 0 for anonymous user.
	 * @param string $source      Source to set the language: 'param' or 'cookie'.
	 */
	public function test_ajax_on_front( $language, $translation, $user_id, $source ) {
		update_user_meta( 1, 'locale', 'en_US' );

		wp_set_current_user( $user_id );

		$_REQUEST = array( 'action' => 'test' );

		if ( 'cookie' === $source ) {
			$_COOKIE['pll_language'] = $language;
		} else {
			$_REQUEST['lang'] = $language;
		}

		add_action( 'wp_ajax_test', array( $this, '_ajax_test_locale' ) );

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		$frontend->init();

		try {
			$this->_handleAjax( 'test' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$this->assertEquals( $language, $frontend->curlang->slug );
		$this->assertEquals( $translation, json_decode( $this->_last_response, true ) );
	}

	public function ajax_on_front_provider() {
		$users = array(
			'anonymous' => 0,
			'logged in' => 1,
		);

		$sources = array(
			'cookie',
			'param',
		);

		$languages = array(
			'en' => 'default',
			'fr' => 'secondary',
		);

		$translations = array(
			'en' => 'Dashboard',
			'fr' => 'Tableau de bord',
		);

		$provider = array();

		foreach ( $users as $user => $user_id ) {
			foreach ( $sources as $source ) {
				foreach ( $languages as $code => $language ) {
					$test = "Request in $language language by $source and $user user";
					$provider[ $test ] = array(
						'language'    => $code,
						'translation' => $translations[ $code ],
						'user'        => $user_id,
						'source'      => $source,
					);
				}
			}
		}

		return $provider;
	}
}
