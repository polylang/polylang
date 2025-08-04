<?php

use WP_Syntex\Polylang\Options\Inactive_Option;

if ( is_multisite() ) :

	class Multisite_Options_Test extends PLL_Multisites_TestCase {
		public function test_option_should_return_default_value_on_inactive_site() {
			$pll_admin = $this->get_pll_admin_env( array( 'media_support' => true ) );

			$this->assertTrue( $pll_admin->options->get( 'media_support' ) );
			$this->assertSame( array(), $pll_admin->options->get( 'sync' ) );

			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			$this->assertFalse( $pll_admin->options->get( 'media_support' ), 'The option should return its default value on a site where Polylang is not activated on the site.' );
			$this->assertSame( array(), $pll_admin->options->get( 'sync' ), 'The option should return its default value on a site where Polylang is not activated on the site.' );
		}

		public function test_option_should_return_error_on_set_on_inactive_site() {
			$pll_admin = $this->get_pll_admin_env( array( 'media_support' => true ) );

			$this->assertTrue( $pll_admin->options->get( 'media_support' ) );
			$this->assertSame( array(), $pll_admin->options->get( 'sync' ) );

			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			$result = $pll_admin->options->set( 'media_support', false );
			$this->assertInstanceOf( WP_Error::class, $result, 'Setting an option should always return an error.' );
			$this->assertSame( Inactive_Option::ERROR_CODE, $result->get_error_code(), 'The option should return a blocking error on a site where Polylang is not activated.' );

			$result = $pll_admin->options->set( 'sync', array( 'taxonomies' => array( 'category' ) ) );
			$this->assertInstanceOf( WP_Error::class, $result, 'Setting an option should always return an error.' );
			$this->assertSame( Inactive_Option::ERROR_CODE, $result->get_error_code(), 'The option should return a blocking error on a site where Polylang is not activated.' );
		}
	}

endif;
