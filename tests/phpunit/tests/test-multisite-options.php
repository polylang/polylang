<?php

use WP_Syntex\Polylang_Phpunit\TestCaseTrait;
use WP_Syntex\Polylang\Options\Abstract_Option;

if ( is_multisite() ) :

	class Multisite_Options_Test extends PLL_Multisites_TestCase {
		use TestCaseTrait;
		use PLL_Check_WP_Functions_Trait;

		public function test_option_should_return_correct_type_on_inactive_site() {
			$pll_admin = $this->get_pll_admin_env( array( 'media_support' => true ) );

			$this->assertTrue( $pll_admin->options->get( 'media_support' ) );
			$this->assertSame( array(), $pll_admin->options->get( 'sync' ) );

			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			$this->assertIsBool( $pll_admin->options->get( 'media_support' ), 'The option `media_support` should return a boolean value on a site where Polylang is not activated.' );
			$this->assertIsArray( $pll_admin->options->get( 'sync' ), 'The option `sync` should return an array value on a site where Polylang is not activated.' );
		}
	}

endif;
