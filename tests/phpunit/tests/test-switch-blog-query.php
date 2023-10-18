<?php

if ( is_multisite() ) :

	class Switch_Blog_Urls_Test extends PLL_Multisites_TestCase {
		protected function get_plugin_names() {
			return array( POLYLANG_BASENAME );
		}

		protected function get_pll_env( $options ) {
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();

			return new PLL_Admin( $links_model );
		}

		/**
		 * @testWith ["http://polylang-dir.org", "en"]
		 *           ["http://polylang-dir.org", "fr"]
		 *
		 * @param string $url  URL to test.
		 * @param string $lang Current language slug.
		 */
		public function test_queries_blog_pll_dir( $url, $lang ) {
			global $wp_rewrite;

			switch_to_blog( self::$blog_with_pll_directory->blog_id );

			$options = array_merge(
				PLL_Install::get_default_options(),
				array(
					'force_lang'   => 1,
					'default_lang' => 'en',
				)
			);
			$model       = new PLL_Model( $options );
			$links_model = $model->get_links_model();
			$links_model->init();
			$frontend = new PLL_Frontend( $links_model );
			$frontend->init();

			$post = $this->factory()->post->create();
			$frontend->model->post->set_language( $post, $lang );

			$wp_rewrite->init();
			$wp_rewrite->extra_rules_top = array();
			$frontend->model->post->register_taxonomy(); // needs this for 'lang' query var
			create_initial_taxonomies();

			flush_rewrite_rules();

			$url .= 'en' === $lang ? '' : $lang;

			$frontend->curlang = $frontend->model->get_language( $lang ); // Force current language.

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
		public function test_queries_blog_pll_domains( $url, $lang ) {
			global $wp_rewrite;

			switch_to_blog( self::$blog_with_pll_domains->blog_id );

			$options = array_merge(
				PLL_Install::get_default_options(),
				array(
					'force_lang'   => 3,
					'domains' => array(
						'en' => 'polylang-domains.en',
						'de' => 'polylang-domains.de',
					),
					'default_lang' => 'en',
				)
			);
			$model = new PLL_Model( $options );
			$links_model = $model->get_links_model();
			$links_model->init();
			$frontend = new PLL_Frontend( $links_model );
			$frontend->init();

			$wp_rewrite->init();
			$wp_rewrite->extra_rules_top = array();
			$frontend->model->post->register_taxonomy(); // needs this for 'lang' query var
			create_initial_taxonomies();

			flush_rewrite_rules();

			$frontend->curlang = $frontend->model->get_language( $lang ); // Force current language.

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}
	}

endif;
