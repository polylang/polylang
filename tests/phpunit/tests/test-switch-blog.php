<?php

if ( is_multisite() ) :

	class Switch_Blog_Test extends PLL_Multisites_TestCase {

		/**
		 * Checks that the `pll__` function gets the right string translation when two languages are sharing the same locale, with multisite.
		 *
		 * @ticket #1870
		 * @see https://github.com/polylang/polylang-pro/issues/1870.
		 */
		public function test_strings_translation_with_same_language_locale_with_multisite() {
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$new_blog = $this->factory()->blog->create_and_get();
			// Set up a blog with Polylang activated, permalinks as directory, English and French created.
			// The French language has the same locale as the English one ('en_US').
			$this->languages['fr']['locale'] = 'en_US';
			$this->set_up_blog_with_pll(
				$new_blog,
				array( $this->languages['en'], $this->languages['fr'] ),
				array( 'force_lang' => 1 ),
				$this->pretty_structure
			);

			restore_current_blog();
			switch_to_blog( (int) $new_blog->blog_id );

			$pll_frontend = $this->get_pll_frontend_env( array( 'force_lang' => 1 ) );

			$mo = new PLL_MO();
			$mo->add_entry( $mo->make_entry( 'Test en', 'Test fr' ) );
			$mo->export_to_db( $pll_frontend->model->get_language( 'fr' ) );

			$pll_frontend->curlang = $pll_frontend->model->get_language( 'en' );
			do_action( 'pll_language_defined' );

			restore_current_blog();
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			restore_current_blog();
			switch_to_blog( (int) $new_blog->blog_id );

			$this->assertSame( 'Test en', pll__( 'Test en' ) );

			$this->languages['fr']['locale'] = 'fr_FR';
			wp_delete_site( $new_blog->blog_id );
		}
	}

endif;
