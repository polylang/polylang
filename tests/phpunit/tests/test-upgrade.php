<?php

class Upgrade_Test extends PLL_UnitTestCase {
	protected $options_backup;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
	}

	/**
	 * @ticket #1664 see {https://github.com/polylang/polylang-pro/issues/1664}.
	 */
	public function test_delete_transient_at_upgrade_to_3_4() {
		wp_set_current_user( 1 );
		update_user_meta( get_current_user_id(), 'pll_filter_content', 'en' );

		$options                 = PLL_Install::get_default_options();
		$options['default_lang'] = 'en';
		$model                   = new PLL_Admin_Model( $options );
		$links_model             = new PLL_Links_Default( $model );
		$admin                   = new PLL_Admin( $links_model );

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

		$upgrade = new PLL_Upgrade( $admin->options );
		$upgrade->upgrade();
		$admin->init();
		$this->assertFalse( get_transient( 'pll_languages_list' ), 'Languages lsit transient should have been deleted during upgrade.' );
		do_action( 'setup_theme' ); // See the issue, `PLL_Admin_Base::init_user()` being hooked to `setup_theme`.
	}
}
