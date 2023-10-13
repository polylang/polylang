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
		 * @testWith ["polylang-dir.org"]
		 *           ["polylang-dir.org/fr"]
		 *
		 * @param string $url URL to test.
		 */
		public function test_queries_blog_pll_dir( $url ) {
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

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}

		/**
		 * @testWith ["polylang-domains.en"]
		 *           ["polylang-domains.de"]
		 *
		 * @param string $url URL to test.
		 */
		public function test_queries_blog_pll_domains( $url ) {
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

			$this->go_to( $url );

			$this->assertQueryTrue( 'is_home', 'is_front_page' );
		}
	}

endif;
