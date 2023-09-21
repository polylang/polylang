<?php

if ( is_multisite() ) :

	class Switch_Blog_Rewrite_Rules_Test extends PLL_Multisites_TestCase {
		protected function get_plugin_names() {
			return array( POLYLANG_BASENAME );
		}

		protected function get_pll_env( $options ) {
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();

			return new PLL_Admin( $links_model );
		}

		public function test_rewrite_rules_when_switching_blog() {
			global $wp_rewrite;

			$options     = PLL_Install::get_default_options();
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();
			$pll_admin   = new PLL_Admin( $links_model );
			$pll_admin->init();
			do_action_ref_array( 'pll_init', array( &$pll_admin ) );

			switch_to_blog( self::$blog_with_pll_directory->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertNotEmpty( $rules );
			$this->assertArrayNotHasKey( '(en)/?$', $rules );
			$this->assertArrayHasKey( '(fr)/?$', $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 2, $languages );
			$this->assertSame( 'en', $languages[0]->slug );
			$this->assertSame( 'http://' . self::$blog_with_pll_directory->domain . self::$blog_with_pll_directory->path, $languages[0]->get_home_url() );
			$this->assertSame( 'fr', $languages[1]->slug );
			$this->assertSame( 'http://' . self::$blog_with_pll_directory->domain . self::$blog_with_pll_directory->path . 'fr/', $languages[1]->get_home_url() );

			restore_current_blog();

			switch_to_blog( self::$blog_with_pll_domains->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertNotEmpty( $rules );
			$this->assertArrayNotHasKey( '(en)/?$', $rules );
			// $this->assertArrayNotHasKey( '(de)/?$', $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 2, $languages );
			$this->assertSame( 'en', $languages[0]->slug );
			$this->assertSame( 'polylang-domains.en/', $languages[0]->get_home_url() );
			$this->assertSame( 'de', $languages[1]->slug );
			$this->assertSame( 'polylang-domains.de/', $languages[1]->get_home_url() );

			restore_current_blog();

			switch_to_blog( self::$blog_with_pll_default_links->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertEmpty( $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 2, $languages );
			$this->assertSame( 'en', $languages[0]->slug );
			$this->assertSame( 'http://' . self::$blog_with_pll_default_links->domain . self::$blog_with_pll_default_links->path, $languages[0]->get_home_url() );
			$this->assertSame( 'fr', $languages[1]->slug );
			$this->assertSame( 'http://' . self::$blog_with_pll_default_links->domain . self::$blog_with_pll_default_links->path . '?lang=fr', $languages[1]->get_home_url() );

			restore_current_blog();

			switch_to_blog( self::$blog_without_pll_pretty_links->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertNotEmpty( $rules );
			$this->assertArrayNotHasKey( '(fr)/?$', $rules );
			$this->assertArrayNotHasKey( '(en)/?$', $rules );
			$this->assertArrayNotHasKey( '(de)/?$', $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 0, $languages );

			restore_current_blog();

			$this->assertSame( self::$blog_without_pll_plain_links->blog_id, get_blog_details()->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertEmpty( $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 0, $languages );
			$this->assertArrayNotHasKey( POLYLANG_BASENAME, get_option( 'active_plugins', array() ) );
		}
	}

endif;
