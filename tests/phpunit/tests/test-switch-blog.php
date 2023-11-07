<?php

if ( is_multisite() ) :

	class Switch_Blog_Test extends PLL_Multisites_TestCase {

		/**
		 * Languages data for their creation keyed by language slug.
		 * The French language has the same locale as the English one ('en_US').
		 *
		 * @var array
		 */
		protected $languages = array(
			'en' => array(
				'name'       => 'English',
				'slug'       => 'en',
				'locale'     => 'en_US',
				'rtl'        => 0,
				'flag'       => 'us',
				'term_group' => 0,
			),
			'fr' => array(
				'name'       => 'Français',
				'slug'       => 'fr',
				'locale'     => 'en_US',
				'rtl'        => 0,
				'flag'       => 'fr',
				'term_group' => 1,
			),
			'de' => array(
				'name'       => 'Deutsch',
				'slug'       => 'de',
				'locale'     => 'de_DE',
				'rtl'        => 0,
				'flag'       => 'de',
				'term_group' => 2,
			),
		);

		public function set_up() {
			parent::set_up();

			$pll_admin = $this->get_pll_admin_env();

			$mo = new PLL_MO();
			$mo->add_entry( $mo->make_entry( 'Test en', 'Test fr' ) );
			$mo->export_to_db( $pll_admin->model->get_language( 'fr' ) );
		}

		/**
		 * Checks that the `pll__` function gets the right string translation when two languages are sharing the same locale, with multisite.
		 *
		 * @ticket #1870
		 * @see https://github.com/polylang/polylang-pro/issues/1870.
		 */
		public function test_strings_translation_with_same_language_locale_with_multisite() {
			$pll_frontend          = $this->get_pll_frontend_env( array( 'force_lang' => 1 ) );
			$pll_frontend->curlang = $pll_frontend->model->get_language( 'en' );
			do_action( 'pll_language_defined' );

			switch_to_blog( (int) $this->blog_with_pll_plain_links->blog_id );

			restore_current_blog();
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$this->assertSame( 'Test en', pll__( 'Test en' ) );
		}
	}

endif;
