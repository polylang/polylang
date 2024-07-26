<?php

if ( is_multisite() ) :

	class Switch_Blog_Query_Test extends PLL_Multisites_TestCase {
		/**
		 * @testWith ["http://example.org", "en"]
		 *           ["http://example.org", "fr"]
		 *
		 * @param string $url  URL to test.
		 * @param string $lang Current language slug.
		 */
		public function test_queries_blog_pll_dir( string $url, string $lang ) {
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$pll_frontend = $this->get_pll_frontend_env();
			$pll_frontend->curlang = $pll_frontend->model->get_language( $lang ); // Force current language.

			$url .= 'en' === $lang ? '' : "/$lang";

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
			switch_to_blog( (int) $this->blog_with_pll_domains->blog_id );

			$pll_frontend = $this->get_pll_frontend_env();
			$pll_frontend->curlang = $pll_frontend->model->get_language( $lang ); // Force current language.

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}

		/**
		 * @ticket #1855
		 * @see https://github.com/polylang/polylang-pro/issues/1855.
		 */
		public function test_queries_blog_pll_dir_switched_twice() {
			global $wp_rewrite;

			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$pll_frontend = $this->get_pll_frontend_env();
			do_action_ref_array( 'pll_init', array( &$pll_frontend ) );

			$wp_rewrite->init();
			flush_rewrite_rules();

			restore_current_blog();
			switch_to_blog( (int) $this->blog_with_pll_domains->blog_id );
			restore_current_blog(); // Restore to switch back, to ensure rewrite rules filters are set back correctly.

			$wp_rewrite->init();
			flush_rewrite_rules();

			$pll_frontend->curlang = $pll_frontend->model->get_language( 'fr' ); // Force current language.

			$this->go_to( 'http://example.org/fr' );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}

		/**
		 * @ticket #1867
		 * @see https://github.com/polylang/polylang-pro/issues/1867.
		 */
		public function test_queries_blog_pll_dir_switched_same() {
			global $wp_rewrite;

			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$pll_admin = $this->get_pll_admin_env();
			do_action_ref_array( 'pll_init', array( &$pll_admin ) );

			$post = $this->factory()->post->create();
			$pll_admin->model->post->set_language( $post, 'fr' );
			$url = get_permalink( $post );

			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$wp_rewrite->init();
			flush_rewrite_rules();

			$pll_admin->curlang = $pll_admin->model->get_language( 'fr' ); // Force current language.

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_single', 'is_singular' );
		}
	}

endif;
