<?php

if ( is_multisite() ) :

	/**
	 * Test class for custom code in multisite.
	 */
	class Switch_Blog_Custom_Code_Test extends PLL_Multisites_TestCase {
		/**
		 * Posts by blogs, stores only posts in default language if applicable.
		 *
		 * @var WP_Post[]
		 */
		private $posts = array();

		/**
		 * Creates posts for a given blog.
		 *
		 * @param WP_Site        $blog      Current site object.
		 * @param PLL_Admin|null $pll_admin Polylang admin object, null if deactivated.
		 * @param array          $languages Array of blog languages data, empty if none.
		 * @return void
		 */
		protected function create_fixtures_for_blog( WP_Site $blog, $pll_admin = null, array $languages = array() ) {
			$post = $this->factory()->post->create_and_get();

			$this->posts[ (int) $blog->blog_id ] = $post;

			if ( $pll_admin instanceof PLL_Admin && ! empty( $languages ) ) {
				// Current blog has Polylang activated, let's create multilingual fixtures.
				$pll_admin->model->post->set_language( $post->ID, $languages[0]['slug'] );
				$tr_post = $this->factory()->post->create_and_get();
				$pll_admin->model->post->save_translations(
					$post->ID,
					array(
						$languages[0]['slug'] => $post->ID,
						$languages[1]['slug'] => $tr_post->ID,
					)
				);
			}
		}

		public function test_switch_blog_with_custom_code() {
			$pll_frontend = $this->get_pll_frontend_env();
			$pll_frontend->curlang = $pll_frontend->model->get_language( 'en' ); // English is a common language for blogs with Polylang activated, @see {PLL_Multisites_TestCase::set_up()}.
			do_action( 'pll_language_defined' );
			do_action_ref_array( 'pll_init', array( &$pll_frontend ) );
			$GLOBALS['polylang'] = $pll_frontend;

			/*
			 * Test blog with Polylang activated and pretty permalink with language as directory.
			 */
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			$home = get_home_url();

			$this->assertNotEmpty( $home );

			$this->assertInstanceOf( PLL_Language::class, $pll_frontend->curlang );

			$post_id = pll_get_post( $this->posts[ (int) $this->blog_with_pll_directory->blog_id ]->ID );

			$this->assertNotFalse( $post_id );
			$this->assertSame( $this->posts[ (int) $this->blog_with_pll_directory->blog_id ]->ID, $post_id );

			restore_current_blog();

			/*
			 * Test blog with Polylang activated and pretty permalink with language as domains.
			 */
			switch_to_blog( (int) $this->blog_with_pll_domains->blog_id );

			$home = get_home_url();

			$this->assertNotEmpty( $home );

			$this->assertInstanceOf( PLL_Language::class, $pll_frontend->curlang );

			$post_id = pll_get_post( $this->posts[ (int) $this->blog_with_pll_domains->blog_id ]->ID );

			$this->assertNotFalse( $post_id );
			$this->assertSame( $this->posts[ (int) $this->blog_with_pll_domains->blog_id ]->ID, $post_id );

			restore_current_blog();

			/*
			 * Test blog with Polylang activated and plain permalink.
			 */
			switch_to_blog( (int) $this->blog_with_pll_plain_links->blog_id );

			$home = get_home_url();

			$this->assertNotEmpty( $home );

			$this->assertInstanceOf( PLL_Language::class, $pll_frontend->curlang );

			$post_id = pll_get_post( $this->posts[ (int) $this->blog_with_pll_plain_links->blog_id ]->ID );

			$this->assertNotFalse( $post_id );
			$this->assertSame( $this->posts[ (int) $this->blog_with_pll_plain_links->blog_id ]->ID, $post_id );

			restore_current_blog();

			/*
			 * Test blog with Polylang deactivated and pretty permalink.
			 */
			switch_to_blog( (int) $this->blog_without_pll_pretty_links->blog_id );

			$home = get_home_url();

			$this->assertNotEmpty( $home );

			// Even though Polylang is deactivated on the current blog, the current language must be kept.
			$this->assertInstanceOf( PLL_Language::class, $pll_frontend->curlang );

			$post_id = pll_get_post( $this->posts[ (int) $this->blog_without_pll_pretty_links->blog_id ]->ID );

			$this->assertNotFalse( $post_id );
			// No language, no post.
			$this->assertSame( 0, $post_id );

			restore_current_blog();

			/*
			 * Test blog with Polylang deactivated and plain permalink.
			 */

			switch_to_blog( (int) $this->blog_without_pll_plain_links->blog_id );

			$home = get_home_url();

			$this->assertNotEmpty( $home );

			// Even though Polylang is deactivated on the current blog, the current language must be kept.
			$this->assertInstanceOf( PLL_Language::class, $pll_frontend->curlang );

			$post_id = pll_get_post( $this->posts[ (int) $this->blog_without_pll_plain_links->blog_id ]->ID );

			$this->assertNotFalse( $post_id );
			// No language, no post.
			$this->assertSame( 0, $post_id );
		}
	}

endif;
