<?php

class Admin_Notice_Test extends PLL_UnitTestCase {

	static function wp_redirect() {
		throw new Exception( 'Call to wp_redirect' );
	}

	function test_hide_notice() {
		// Allows to continue the execution after wp_redirect + exit.
		add_filter( 'wp_redirect', array( __CLASS__, 'wp_redirect' ) );
		if ( method_exists( $this, 'setExpectedException' ) ) {
			// setExpectedException has been deprecated in recent versions of phpunit
			$this->setExpectedException( 'Exception', 'Call to wp_redirect' );
		} else {
			$this->expectException( 'Exception' );
			$this->expectExceptionMessage( 'Call to wp_redirect' );
		}

		wp_set_current_user( 1 );

		$_GET = $_REQUEST = array(
			'pll-hide-notice'   => 'review',
			'_pll_notice_nonce' => wp_create_nonce( 'review' ),
		);

		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );
		self::$polylang->admin_notices->hide_notice();

		$this->assertEquals( array( 'review' ), get_user_meta( 1, 'pll_dismissed_notices', true ) );
	}

	function test_no_review_notice_for_old_users() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'review' ) );
	}

	function test_review_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		self::$polylang->options['first_activation'] = 1; // Some very old timestanp
		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		if ( defined( 'POLYLANG_PRO' ) ) {
			$this->assertFalse( strpos( $out, 'review' ) );
		} else {
			$this->assertNotFalse( strpos( $out, 'review' ) );
		}
	}

	function test_hidden_review_notice() {
		wp_set_current_user( 1 );
		update_user_meta( 1, 'pll_dismissed_notices', array( 'review' ) );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		self::$polylang->options['first_activation'] = 1; // Some very old timestanp
		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'review' ) );
	}

	function test_no_review_notice_for_non_admin() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		self::$polylang->options['first_activation'] = 1; // Some very old timestanp
		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	function test_pllwc_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		if ( ! defined( 'WOOCOMMERCE_VERSION' ) ) {
			define( 'WOOCOMMERCE_VERSION', '3.4.0' );
		}
		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'pllwc' ) );
	}

	function test_lingotek_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		self::$polylang = new PLL_Admin( self::$polylang->links_model );

		if ( class_exists( 'PLL_Lingotek' ) ) {
			$l = new PLL_Lingotek();
			$l->init();
		}

		self::$polylang->admin_notices = new PLL_Admin_Notices( self::$polylang );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		if ( defined( 'POLYLANG_PRO' ) ) {
			$this->assertFalse( strpos( $out, 'lingotek' ) );
		} else {
			$this->assertNotFalse( strpos( $out, 'lingotek' ) );
		}
	}
}
