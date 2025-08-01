<?php

class Admin_Notices_Test extends PLL_UnitTestCase {
	use PLL_Handle_WP_Redirect_Trait;

	public function set_up() {
		parent::set_up();

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	public function test_hide_notice() {
		wp_set_current_user( 1 );

		$_GET = array(
			'pll-hide-notice'   => 'review',
			'_pll_notice_nonce' => wp_create_nonce( 'review' ),
		);
		$_REQUEST = $_GET;

		$this->pll_admin->admin_notices = new PLL_Admin_Notices( $this->pll_admin );

		$this->assert_redirect( array( $this->pll_admin->admin_notices, 'hide_notice' ) );
		$this->assertSame( array( 'review' ), get_option( 'pll_dismissed_notices' ) );
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

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestamp
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

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestamp
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

		$this->pll_admin->options['first_activation'] = 1; // Some very old timestamp
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
