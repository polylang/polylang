<?php

if ( is_multisite() ) :

	class Multisite_Options_Test extends PLL_Multisites_TestCase {
		/**
		 * Tests that options return their default value when on a site where PLL is inactive.
		 *
		 * @return void
		 */
		public function test_option_returns_default_value_on_inactive_site(): void {
			$pll_admin = $this->get_pll_admin_env( array( 'media_support' => true ) );

			$this->assertTrue( $pll_admin->options->get( 'media_support' ) );
			$this->assertIsArray( $pll_admin->options->get( 'sync' ) );

			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			$this->assertFalse( $pll_admin->options->get( 'media_support' ), 'The option should return its default value on a site where Polylang is not activated on the site.' );
			$this->assertIsArray( $pll_admin->options->get( 'sync' ), 'The option should return its default value on a site where Polylang is not activated on the site.' );
		}
	}

endif;
