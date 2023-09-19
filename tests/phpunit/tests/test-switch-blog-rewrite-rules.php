<?php

if ( is_multisite() ) :

	class Switch_Blog_Rewrite_Rules_Test extends WP_UnitTestCase {
		protected static $blog_without_pll;
		protected static $blog_with_pll_directory;
		protected static $blog_with_pll_domains;
		protected $structure = '/%postname%/';
		protected $languages = array(
			'en' => array(
				'name'       => 'English',
				'slug'       => 'en',
				'locale'     => 'en_US',
				'rtl'        => 0,
				'flag'       => 'us',
				'term_group' => 0,
			),
			'fr' => array(
				'name'       => 'Français',
				'slug'       => 'fr',
				'locale'     => 'fr_FR',
				'rtl'        => 0,
				'flag'       => 'fr',
				'term_group' => 1,
			),
			'de' => array(
				'name'       => 'Deutsch',
				'slug'       => 'de',
				'locale'     => 'de_DE',
				'rtl'        => 0,
				'flag'       => 'de',
				'term_group' => 2,
			),
		);

		/**
		 * Initialization before all tests run.
		 *
		 * @param WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
		 */
		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			self::$blog_without_pll = $factory->blog->create_and_get(
				array(
					'domain' => 'wordpress.org',
				)
			);

			self::$blog_with_pll_directory = $factory->blog->create_and_get(
				array(
					'domain' => 'polylang-dir.org',
				)
			);

			self::$blog_with_pll_domains = $factory->blog->create_and_get(
				array(
					'domain' => 'polylang-domains.org',
				)
			);
		}

		public static function wpTearDownAfterClass() {
			wp_delete_site( self::$blog_without_pll->blog_id );
			wp_delete_site( self::$blog_with_pll_directory->blog_id );
			wp_delete_site( self::$blog_with_pll_domains->blog_id );

			wp_update_network_site_counts();
		}

		public function set_up() {
			global $wp_rewrite;

			// Set up blog with Polylang not activated and pretty permalinks.
			switch_to_blog( self::$blog_without_pll->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->set_permalink_structure( $this->structure );

			$plugins = get_option( 'active_plugins', array() );
			update_option( 'active_plugins', array_diff( $plugins, array( POLYLANG_BASENAME ) ) );

			restore_current_blog();

			// Set up blog with Polylang activated, permalinks as directory, English and French created.
			switch_to_blog( self::$blog_with_pll_directory->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->set_permalink_structure( $this->structure );

			$this->set_up_polylang_for_site(
				self::$blog_with_pll_directory,
				array( $this->languages['en'], $this->languages['fr'] ),
				array( 'force_lang' => 1 )
			);

			restore_current_blog();

			// Set up blog with Polylang activated, permalinks with domains, English and German created.
			switch_to_blog( self::$blog_with_pll_domains->blog_id );

			$wp_rewrite->init();
			$wp_rewrite->set_permalink_structure( $this->structure );

			$this->set_up_polylang_for_site(
				self::$blog_with_pll_domains,
				array( $this->languages['en'], $this->languages['de'] ),
				array(
					'force_lang' => 3,
					'domains' => array(
						'en' => 'polylang-domains.org',
						'de' => 'polylang-domains.de',
					)
				)
			);

			restore_current_blog();
		}

		public function tear_down() {
			$options     = array_merge( PLL_Install::get_default_options() );
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();
			$pll_admin   = new PLL_Admin( $links_model );

			foreach ( $pll_admin->model->get_languages_list() as $lang ) {
				$pll_admin->model->delete_language( $lang->term_id );
			}

			parent::tear_down();
		}

		protected function set_up_polylang_for_site( $site, $languages, $options ) {
			$plugins = get_option( 'active_plugins', array() );
			update_option( 'active_plugins', array_merge( $plugins, array( POLYLANG_BASENAME ) ) );

			$options = array_merge(
				PLL_Install::get_default_options(),
				$options
			);

			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();
			$pll_admin   = new PLL_Admin( $links_model );
			$pll_admin->init();

			foreach( $languages as $language ) {
				$pll_admin->model->add_language( $language );
			}

			unset( $model, $links_model, $pll_admin );
		}

		public function test_rewrites_rule_when_switching_blog() {
			global $wp_rewrite;

			$options     = PLL_Install::get_default_options();
			$model       = new PLL_Admin_Model( $options );
			$links_model = $model->get_links_model();
			$pll_admin   = new PLL_Admin( $links_model );
			$pll_admin->init();
			do_action( 'pll_init', $pll_admin );

			switch_to_blog( self::$blog_with_pll_directory->blog_id );

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

			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertArrayNotHasKey( '(en)/?$', $rules );
			// @todo Find why the following assertion doesn't work...
			// $this->assertArrayNotHasKey( '(de)/?$', $rules );

			$this->assertNotEmpty( $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 2, $languages );
			$this->assertSame( 'en', $languages[0]->slug );
			$this->assertSame( 'polylang-domains.org/', $languages[0]->get_home_url() );
			$this->assertSame( 'de', $languages[1]->slug );
			$this->assertSame( 'polylang-domains.de/', $languages[1]->get_home_url() );

			restore_current_blog();

			switch_to_blog( self::$blog_without_pll->blog_id );

			$wp_rewrite->flush_rules();
			$rules = $wp_rewrite->wp_rewrite_rules();

			$this->assertNotEmpty( $rules );
			$this->assertArrayNotHasKey( '(fr)/?$', $rules );
			$this->assertArrayNotHasKey( '(en)/?$', $rules );
			$this->assertArrayNotHasKey( '(de)/?$', $rules );

			$languages = $pll_admin->model->get_languages_list();

			$this->assertCount( 0, $languages );

			restore_current_blog();
		}
	}

endif;
