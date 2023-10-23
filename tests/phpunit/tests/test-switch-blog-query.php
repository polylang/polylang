<?php

if ( is_multisite() ) :

	class Switch_Blog_Urls_Test extends PLL_Multisites_TestCase {
		/**
		 * Returns Polylang's plugin basenames.
		 *
		 * @return string[]
		 */
		protected function get_plugin_names(): array {
			return array( POLYLANG_BASENAME );
		}

		/**
		 * Returns an instance of the main Polylang object along required instanciated classes for the tests.
		 *
		 * @param array $options Plugin options.
		 * @return PLL_Admin_Base Polylang main class instance.
		 */
		protected function get_pll_env( array $options ): PLL_Admin_Base {
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();

			return new PLL_Admin( $links_model );
		}

		/**
		 * @testWith ["http://example.org", "en"]
		 *           ["http://example.org", "fr"]
		 *
		 * @param string $url  URL to test.
		 * @param string $lang Current language slug.
		 */
		public function test_queries_blog_pll_dir( string $url, string $lang ) {
			$this->clean_up_filters();

			switch_to_blog( (int) self::$blog_with_pll_directory->blog_id );

			$options     = get_option( 'polylang' );
			$model       = new PLL_Model( $options );
			$links_model = $model->get_links_model();
			$links_model->init();
			$frontend = new PLL_Frontend( $links_model );
			$frontend->init();
			$frontend->curlang = $frontend->model->get_language( $lang ); // Force current language.

			$url .= 'en' === $lang ? '' : $lang;

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}

		/**
		 * @testWith ["http://polylang-domains.en", "en"]
		 *           ["http://polylang-domains.de", "de"]
		 *
		 * @param string $url URL to test.
		 * @param string $lang Current language slug.
		 */
		public function test_queries_blog_pll_domains( string $url, string $lang ) {
			$this->clean_up_filters();

			switch_to_blog( (int) self::$blog_with_pll_domains->blog_id );

			$options = get_option( 'polylang' );
			$model = new PLL_Model( $options );
			$links_model = $model->get_links_model();
			$links_model->init();
			$frontend = new PLL_Frontend( $links_model );
			$frontend->init();
			$frontend->curlang = $frontend->model->get_language( $lang ); // Force current language.

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}

		/**
		 * @ticket #1855
		 * @see https://github.com/polylang/polylang-pro/issues/1855.
		 */
		public function test_queries_blog_pll_dir_switched_twice() {
			global $wp_rewrite;

			$this->clean_up_filters();

			switch_to_blog( (int) self::$blog_with_pll_directory->blog_id );

			$options = get_option( 'polylang' );
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();
			$links_model->init();
			$admin = new PLL_Admin( $links_model );
			$admin->init();
			do_action_ref_array( 'pll_init', array( &$admin ) );

			$post = $this->factory()->post->create();
			$admin->model->post->set_language( $post, 'fr' );

			$wp_rewrite->init();
			flush_rewrite_rules();

			switch_to_blog( self::$blog_with_pll_domains->blog_id );
			restore_current_blog(); // Restore to switch back, to ensure rewrite rules filters are set back correctly.

			$wp_rewrite->init();
			flush_rewrite_rules();

			$admin->curlang = $admin->model->get_language( 'fr' ); // Force current language.

			$this->go_to( 'http://example.org/fr' );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}
	}

endif;
