<?php

if ( is_multisite() ) :

	class Switch_Blog_Test extends PLL_Multisites_TestCase {

		public function set_up() {
			// Sets the same locale as English for the French language ('en_US').
			$this->languages['fr']['locale'] = 'en_US';

			parent::set_up();
		}

		/**
		 * Checks that the `pll__` function gets the right string translation when two languages are sharing the same locale, with multisite.
		 *
		 * @ticket #1870
		 * @see https://github.com/polylang/polylang-pro/issues/1870.
		 */
		public function test_strings_translation_with_same_language_locale_with_multisite() {
			$pll_admin = $this->get_pll_admin_env();

			$mo = new PLL_MO();
			$mo->add_entry( $mo->make_entry( 'Test en', 'Test fr' ) );
			$mo->export_to_db( $pll_admin->model->get_language( 'fr' ) );

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
