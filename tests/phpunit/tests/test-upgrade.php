<?php

class Upgrade_Test extends PLL_UnitTestCase {
	protected $options_backup;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	/**
	 * @ticket #1664 see {https://github.com/polylang/polylang-pro/issues/1664}.
	 */
	public function test_upgrade_to_3_4() {
		wp_set_current_user( 1 );
		update_user_meta( get_current_user_id(), 'pll_filter_content', 'en' );
		$links_model = self::$model->get_links_model();
		$admin = new PLL_Admin( $links_model );
		$admin->init();

		// Fake old transient.
		self::$model->set_languages_ready();
		self::$model->get_languages_list();
		$languages = get_transient( 'pll_languages_list' );
		foreach ( $languages as $i => $language ) {
			unset( $language['term_props'] );
			$languages[ $i ] = $language;
		}
		$admin->model->clean_languages_cache();
		set_transient( 'pll_languages_list', $languages );

		new PLL_Upgrade( $admin->options );

		try {
			do_action( 'setup_theme' ); // See the issue, `PLL_Admin_Base::init_user()` being hooked to `setup_theme`.
		} catch ( \Throwable $th ) {
			$this->assertTrue( false, "Polylang admin failed with error: {$th}" );
		}

		$this->assertTrue( true );
	}
}
