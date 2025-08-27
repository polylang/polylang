<?php

use WP_Syntex\Polylang_Phpunit\TestCaseTrait;
use WP_Syntex\Polylang\Options\Abstract_Option;

if ( is_multisite() ) :

	class Multisite_Options_Test extends PLL_Multisites_TestCase {
		use TestCaseTrait;
		use PLL_Check_WP_Functions_Trait;

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
			$this->assertSame( 'pll_not_active', $result->get_error_code(), 'The option should return a blocking error on a site where Polylang is not activated.' );

			$result = $pll_admin->options->set( 'sync', array( 'taxonomies' => array( 'category' ) ) );
			$this->assertInstanceOf( WP_Error::class, $result, 'Setting an option should always return an error.' );
			$this->assertSame( 'pll_not_active', $result->get_error_code(), 'The option should return a blocking error on a site where Polylang is not activated.' );
		}

		public function test_options_class_properties() {
			$options = self::create_options( array( 'media_support' => true, 'sync' => array( 'taxonomies' ) ) );

			// Check properties.
			$this->assertSame( array(), self::getPropertyValue( $options, 'options' ), 'Since the options are not loaded yet, the `$options` property should be empty.' );
			$this->assertSame( array(), self::getPropertyValue( $options, 'default' ), 'Since the options are not loaded yet, the `$default` property should be empty.' );

			// Switch to a site where PLL is not activated.
			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			// Check properties.
			$this->assertSame( array(), self::getPropertyValue( $options, 'options' ), 'Since the options are not loaded yet, the `$options` property should still be empty.' );
			$this->assertSame( array(), self::getPropertyValue( $options, 'default' ), 'Since the options are not loaded yet, the `$default` property should still be empty.' );

			// Lazyload options.
			$this->assertFalse( $options->get( 'media_support' ), 'The option should return its default value.' );

			// Check properties.
			$this->assertSame( array(), self::getPropertyValue( $options, 'options' ), 'Since the options have been loaded on a site where PLL is not activated, the `$options` property should be empty.' );

			$default_prop = self::getPropertyValue( $options, 'default' );
			$this->assertArrayHasKey( 'sync', $default_prop, 'Since the options have been loaded, the `$default` property should not be empty.' );
			$this->assertSame( array(), $default_prop['sync'], 'Since the options are loaded, the `sync` option in the `$default` property should be empty (default value).' );

			// Switch to a site where PLL is activated.
			$blog_id = (int) $this->blog_with_pll_directory->blog_id;
			switch_to_blog( $blog_id );

			// Check properties.
			$this->assertSame( array(), self::getPropertyValue( $options, 'options' ), 'Since the options have been loaded on a site where PLL is not activated, the `$options` property should still be empty.' );

			// Lazyload options.
			$this->assertTrue( $options->get( 'media_support' ), 'The option should return its value.' );

			// Check properties.
			$options_prop = self::getPropertyValue( $options, 'options' );
			$this->assertArrayHasKey( $blog_id, $options_prop, 'Since the options have been loaded on a site where PLL is activated, the `$options` property should contain the current site\'s options.' );
			$this->assertArrayHasKey( 'sync', $options_prop[ $blog_id ], 'Since the options have been loaded on a site where PLL is activated, the current site\'s options should not be empty.' );
			$this->assertInstanceOf( Abstract_Option::class, $options_prop[ $blog_id ]['sync'] );
			$this->assertSame( array( 'taxonomies' ), $options_prop[ $blog_id ]['sync']->get(), 'Since the options have been loaded on a site where PLL is activated, the `sync` option in the `$options` property should not be empty.' );
		}
	}

endif;
