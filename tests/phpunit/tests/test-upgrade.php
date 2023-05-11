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
		remove_all_actions( 'admin_init' ); // Avoid to send WP headers when calling `do_action( 'admin_init' )`.

		$options                 = PLL_Install::get_default_options();
		$options['version']      = '3.3';
		$options['default_lang'] = 'en';
		update_option( 'polylang', $options );
		$model       = new PLL_Admin_Model( $options );
		$links_model = new PLL_Links_Default( $model );
		$admin       = new PLL_Admin( $links_model );

		// Old transient from 3.3.
		$en = $admin->model->get_language( 'en' );
		$expected_transient = get_transient( 'pll_languages_list' );
		$transient_3_3      = array(
			array(
				'term_id'             => $en->term_id,
				'name'                => 'English',
				'slug'                => 'en',
				'term_group'          => 0,
				'term_taxonomy_id'    => $en->get_tax_prop( 'language', 'term_taxonomy_id' ),
				'count'               => $en->get_tax_prop( 'language', 'count' ),
				'tl_term_id'          => $en->get_tax_prop( 'term_language', 'term_id' ),
				'tl_term_taxonomy_id' => $en->get_tax_prop( 'term_language', 'term_taxonomy_id' ),
				'tl_count'            => $en->get_tax_prop( 'term_language', 'term_taxonomy_id' ),
				'locale'              => 'en_US',
				'is_rtl'              => 0,
				'w3c'                 => 'en-US',
				'facebook'            => 'en_US',
				'home_url'            => $en->get_home_url(),
				'search_url'          => $en->get_search_url(),
				'host'                => '',
				'mo_id'               => $en->mo_id,
				'page_on_front'       => '',
				'page_for_posts'      => '',
				'flag_code'           => 'us',
				'flag_url'            => $en->get_home_url() . '/wp-content/plugins/polylang/flags/us.png',
				'flag'                => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=" alt="English" width="16" height="11" style="width: 16px; height: 11px;" />',
				'custom_flag_url'     => '',
				'custom_flag'         => '',
			),
		);
		$admin->model->clean_languages_cache();
		set_transient( 'pll_languages_list', $transient_3_3 );

		$upgrade = new PLL_Upgrade( $admin->options );
		$upgrade->upgrade();
		$admin->init();

		try {
			do_action( 'setup_theme' ); // See the issue, `PLL_Admin_Base::init_user()` being hooked to `setup_theme`.
			do_action( 'admin_init' ); // `PLL_Upgrade::upgrade()` is hooked to this action.
		} catch ( \Throwable $th ) {
			$this->assertTrue( false, "Polylang admin failed with error: {$th}" );
		}

		$this->assertSameSets( $expected_transient, get_transient( 'pll_languages_list' ), 'Old pll_languages_list transient should have been deleted during upgrade.' );
		$this->assertSame( POLYLANG_VERSION, get_option( 'polylang' )['version'], 'Polylang version should have been updated.' );
	}
}
