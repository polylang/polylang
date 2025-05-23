<?php

class Upgrade_Test extends PLL_UnitTestCase {
	protected $options_backup;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
	}

	public function tear_down() {
		delete_option( 'pll_language_from_content_available' );

		parent::tear_down();
	}

	/**
	 * @ticket #1664 see {https://github.com/polylang/polylang-pro/issues/1664}.
	 */
	public function test_delete_transient_at_upgrade_to_3_4() {
		wp_set_current_user( 1 );
		update_user_meta( get_current_user_id(), 'pll_filter_content', 'en' );
		remove_all_actions( 'admin_init' ); // Avoid to send WP headers when calling `do_action( 'admin_init' )`.

		$options = self::create_options(
			array(
				'default_lang' => 'en',
				'version'      => '3.3',
			)
		);
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

		$this->assertFalse( get_transient( 'pll_languages_list' ), 'Old pll_languages_list transient should have been deleted during upgrade.' );
		$this->assertSame( POLYLANG_VERSION, $admin->options['version'], 'Polylang version should have been updated.' );

		$admin->model->languages->clean_cache();
		$admin->model->languages->get_list(); // Warm the cache.

		$this->assertSameSets( $expected_transient, get_transient( 'pll_languages_list' ), 'Old pll_languages_list transient should have been deleted during upgrade.' );
	}

	public function test_should_hide_language_defined_from_content_option_on_upgrade_to_3_7() {
		$options = self::create_options(
			array(
				'force_lang' => 1,
				'version'    => '3.6',
			)
		);

		( new PLL_Upgrade( $options ) )->_upgrade();

		$this->assertSame( 'no', get_option( 'pll_language_from_content_available' ) );
		$this->assertSame( 1, $options->get( 'force_lang' ) );
	}

	public function test_should_not_hide_language_defined_from_content_option_on_upgrade_to_3_7() {
		$options = self::create_options(
			array(
				'force_lang' => 0,
				'version'    => '3.6',
			)
		);

		( new PLL_Upgrade( $options ) )->_upgrade();

		$this->assertSame( 'yes', get_option( 'pll_language_from_content_available' ) );
		$this->assertSame( 0, $options->get( 'force_lang' ) );
	}

	public function test_should_clean_up_strings_translations_on_upgrade_to_3_7() {
		$fr = $this->factory()->language->create_and_get(
			array( 'locale' => 'fr_FR' )
		);
		update_term_meta(
			$fr->term_id,
			'_pll_strings_translations',
			array(
				array( 'Hello', 'Hello' ),
				array( 'World', 'World' ),
			)
		);

		$en = $this->factory()->pll_model->languages->get( 'en' );
		update_term_meta(
			$en->term_id,
			'_pll_strings_translations',
			array(
				array( 'Hello', 'Hello' ),
				array( 'World', 'World' ),
			)
		);

		$options = self::create_options(
			array( 'version' => '3.6' )
		);

		( new PLL_Upgrade( $options ) )->_upgrade();

		$raw_strings_fr = get_term_meta( $fr->term_id, '_pll_strings_translations', true );
		$raw_strings_en = get_term_meta( $en->term_id, '_pll_strings_translations', true );

		$this->assertSame(
			array(
				array( 'Hello', '' ),
				array( 'World', '' ),
			),
			$raw_strings_fr,
			'Strings translations should have been cleaned up.'
		);
		$this->assertSame(
			array(
				array( 'Hello', '' ),
				array( 'World', '' ),
			),
			$raw_strings_en,
			'Strings translations should have been cleaned up.'
		);
	}
}
