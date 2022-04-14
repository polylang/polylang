<?php

class Admin_Notices_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	public static function wp_redirect() {
		throw new Exception( 'Call to wp_redirect' );
	}

	public function test_hide_notice() {
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

		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );
		$this->pll_admin->admin_notices->hide_notice();

		$this->assertEquals( array( 'review' ), get_user_meta( 1, 'pll_dismissed_notices', true ) );
	}

	public function test_no_review_notice_for_old_users() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'review' ) );
	}

	public function test_review_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestanp
		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		if ( defined( 'POLYLANG_PRO' ) ) {
			$this->assertFalse( strpos( $out, 'review' ) );
		} else {
			$this->assertNotFalse( strpos( $out, 'review' ) );
		}
	}

	public function test_hidden_review_notice() {
		wp_set_current_user( 1 );
		update_user_meta( 1, 'pll_dismissed_notices', array( 'review' ) );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestanp
		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertFalse( strpos( $out, 'review' ) );
	}

	public function test_no_review_notice_for_non_admin() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestanp
		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	public function test_pllwc_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		if ( ! defined( 'WOOCOMMERCE_VERSION' ) ) {
			define( 'WOOCOMMERCE_VERSION', '3.4.0' );
		}
		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'pllwc' ) );
	}

	public function test_lingotek_notice() {
		wp_set_current_user( 1 );

		$_GET['page'] = 'mlang';
		$GLOBALS['hook_suffix'] = 'plugins.php';
		set_current_screen();

		if ( class_exists( 'PLL_Lingotek' ) ) {
			$this->pll_admin->add_shared( 'lingotek', PLL_Lingotek::class );
			$this->pll_admin->get( 'lingotek' )->init();
		}

		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		ob_start();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		if ( defined( 'POLYLANG_PRO' ) ) {
			$this->assertFalse( strpos( $out, 'lingotek' ) );
		} else {
			$this->assertNotFalse( strpos( $out, 'lingotek' ) );
		}
	}

	public function test_legacy_user_meta() {
		wp_set_current_user( 1 );
		update_user_meta( 1, 'pll_dismissed_notices', array( 'test_notice' ) );

		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );
		$this->assertTrue( $this->pll_admin->admin_notices->is_dismissed( 'test_notice' ) );
		$this->assertEquals( array( 'test_notice' ), get_option( 'pll_dismissed_notices' ) );
		if ( is_multisite() ) {
			$this->assertEquals( array( 'test_notice' ), get_user_meta( 1, 'pll_dismissed_notices', true ) );
		} else {
			$this->assertEmpty( get_user_meta( 1, 'pll_dismissed_notices', true ) );
		}
	}
}
