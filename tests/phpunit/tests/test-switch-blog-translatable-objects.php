<?php

if ( is_multisite() ) :

	/**
	 * Test class for registered translatable objects retrieval in multisite.
	 */
	class Switch_Blog_Translatable_Objects_Test extends PLL_Multisites_TestCase {
		private $translatable_objects = array();

		/**
		 * Sets translatable objects then lets parent managing setup.
		 *
		 * @param WP_Site $blog      Blog to set up.
		 * @param array   $languages Languages to create.
		 * @param array   $options   Polylang options to use.
		 * @param string  $structure Permalink structure to use.
		 * @return void
		 */
		protected function set_up_blog_with_pll( WP_Site $blog, array $languages, array $options, string $structure ) {
			$cpt = md5( (string) wp_rand( 1, 20 ) );
			$tax = md5( (string) wp_rand( 21, 40 ) );
			$this->translatable_objects[ (int) $blog->blog_id ] = array(
				'post_types' => array( $cpt => $cpt ),
				'taxonomies' => array( $tax => $tax ),
			);

			$options = array_merge( $options, $this->translatable_objects[ (int) $blog->blog_id ] );

			parent::set_up_blog_with_pll( $blog, $languages, $options, $structure );
		}

		public function test_switch_blog_with_custom_translatable_objects() {
			$pll_admin = $this->get_pll_admin_env();
			do_action_ref_array( 'pll_init', array( &$pll_admin ) );

			/*
			 * Test blog with Polylang activated and pretty permalink with language as directory.
			 */
			switch_to_blog( (int) $this->blog_with_pll_directory->blog_id );

			// Check current blog translatable objects are set.
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);

			// Check other blogs translatable objects aren't set.
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);

			restore_current_blog();

			/*
			* Test blog with Polylang activated and pretty permalink with language as domains.
			*/
			switch_to_blog( (int) $this->blog_with_pll_domains->blog_id );

			// Check current blog translatable objects are set.
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);

			// Check other blogs translatable objects aren't set.
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);

			restore_current_blog();

			/*
			 * Test blog with Polylang activated and plain permalink.
			 */
			switch_to_blog( (int) $this->blog_with_pll_plain_links->blog_id );

			// Check current blog translatable objects are set.
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_plain_links->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);

			// Check other blogs translatable objects aren't set.
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_domains->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['post_types'] ),
				$pll_admin->model->post->get_translated_object_types( false )
			);
			$this->assertNotContains(
				reset( $this->translatable_objects[ (int) $this->blog_with_pll_directory->blog_id ]['taxonomies'] ),
				$pll_admin->model->term->get_translated_object_types( false )
			);
		}
	}

endif;
