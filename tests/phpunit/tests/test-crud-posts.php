<?php

class CRUD_Posts_Test extends PLL_UnitTestCase {
	protected static $editor;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests.

		register_taxonomy( 'custom_tax', 'post' );

		$options = array( 'taxonomies' => array( 'custom_tax' ) );
		$this->pll_admin = ( new PLL_Context_Admin( array( 'options' => $options ) ) )->get();
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_taxonomy( 'custom_tax' );
	}

	/**
	 * @testWith ["post_tag", "tags_input"]
	 *           ["category", "post_category"]
	 *           ["custom_tax", "tax_input"]
	 *
	 * @param string $taxonomy     Taxonomy to test.
	 * @param string $post_tax_arg Post argument key used regarding the taxonomy in `wp_update_post()`.
	 */
	public function test_language_change_with_taxonomy( $taxonomy, $post_tax_arg ) {
		// Fixtures.
		$terms = self::factory()->term->create_translated(
			array( 'taxonomy' => $taxonomy, 'lang' => 'en' ),
			array( 'taxonomy' => $taxonomy, 'lang' => 'fr' )
		);

		$term_input = in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ? array( $terms['en'] ) : array( $taxonomy => array( $terms['en'] ) );  // Special case for custom taxonomies.
		$post       = self::factory()->post->create_and_get( array( $post_tax_arg => $term_input, 'lang' => 'en' ) );

		// Change post language and update it.
		$this->pll_admin->model->post->set_language( $post->ID, 'fr' );
		$postarr = $post->to_array();

		// Pass the term in previous language on purpose.
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			wp_set_current_user( 1 ); // Current user should have the proper capability to set custom taxonomy terms.
			$postarr[ $post_tax_arg ] = array( $taxonomy => array( $terms['en'] ) ); // Special case for custom taxonomies which expects an array of array.
		} elseif ( 'post_tag' === $taxonomy ) {
			$postarr[ $post_tax_arg ] = array( get_term( $terms['en'] )->name ); // Special case for tags where `wp_update_post()` removes existing one by names.
		} else {
			$postarr[ $post_tax_arg ] = array( $terms['en'] );
		}

		$result = wp_update_post( $postarr );

		$this->assertSame( $post->ID, $result, 'The post should be well updated.' );
		$this->assert_has_language( $post, 'fr', 'The post language should be French.' );

		$post         = get_post( $post->ID );
		$object_terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		$this->assertNotWPError( $object_terms );
		$this->assertCount( 1, $object_terms, "The post should have only one {$taxonomy}." );
		$this->assertSame( $terms['fr'], reset( $object_terms ), "The {$taxonomy} should have been translated." );
	}

	/**
	 * @ticket #1766
	 * @see https://github.com/polylang/polylang-pro/issues/1766.
	 *
	 * @testWith ["post_tag", "tax_input"]
	 *           ["category", "post_category"]
	 *
	 * @param string $taxonomy     Taxonomy to test.
	 * @param string $post_tax_arg Post argument key used regarding the taxonomy in `wp_update_post()`.
	 */
	public function test_terms_translation_on_post_save( $taxonomy, $post_tax_arg ) {
		/**
		 * EN: default language.
		 * FR: language that will be assigned to the post.
		 * DE: language the post will be switched to.
		 */
		$terms    = array_fill_keys( array( 'en', 'fr', 'de' ), array() );
		$taxonomy = get_taxonomy( $taxonomy );
		$prefix   = ! empty( $taxonomy->rewrite['slug'] ) ? $taxonomy->rewrite['slug'] : $taxonomy->query_var;

		if ( $taxonomy->hierarchical ) {
			$parent = 0;
			for ( $i = 1; $i <= 3; $i++ ) {
				$parent = self::factory()->term->create(
					array(
						'taxonomy' => $taxonomy->name,
						'name'     => "{$taxonomy->labels->singular_name} $i EN with no trad",
						'parent'   => $parent,
						'lang'     => 'en',
					)
				);
			}
			$terms['en']['create'] = $parent;
		} else {
			// Create a term in default language only.
			$term_id = self::factory()->term->create(
				array(
					'taxonomy' => $taxonomy->name,
					'name'     => "{$taxonomy->labels->singular_name} EN with no trad",
					'lang'     => 'en',
				)
			);
			$terms['en']['create'] = $term_id;
		}

		// Create terms with same name but different slug in all 3 languages.
		$translations = array();
		foreach ( array( 'en', 'fr', 'de' ) as $lang_slug ) {
			$translations[] = array(
				'taxonomy' => $taxonomy->name,
				'name'     => "{$taxonomy->labels->singular_name} same name",
				'slug'     => "{$prefix}-same-name-{$lang_slug}",
				'lang'     => $lang_slug,
			);
		}
		self::factory()->term->create_translated( ...$translations );

		// Create terms with their slug equals sanitize_title( name ) in all 3 languages.
		$translations = array();
		foreach ( array( 'en', 'fr', 'de' ) as $lang_slug ) {
			$translations[] = array(
				'taxonomy' => $taxonomy->name,
				'name'     => "{$taxonomy->labels->singular_name} slug from name $lang_slug",
				'slug'     => "{$prefix}-slug-from-name-{$lang_slug}",
				'lang'     => $lang_slug,
			);
		}
		self::factory()->term->create_translated( ...$translations );

		// Create a post.
		$post_id = self::factory()->post->create();

		// Simulate the request that assigns language and terms.
		$langs = array(
			'en' => 'fr', // The post doesn't have any term assigned yet.
			'fr' => 'de', // The post has terms assigned from previous loop.
		);
		foreach ( $langs as $from => $to ) {
			$data = array(
				'post_lang_choice' => $to, // Switch to new language.
				'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
				'post_ID'          => $post_id,
			);

			if ( $taxonomy->hierarchical ) {
				$data[ $post_tax_arg ] = $terms[ $from ]; // Provide terms in previous language.
			} else {
				$terms_names = get_terms(
					array(
						'taxonomy'   => $taxonomy->name,
						'include'    => $terms[ $from ], // Provide terms in previous language.
						'fields'     => 'names',
						'hide_empty' => false,
						'lang'       => '',
					)
				);
				$this->assertCount( count( $terms['en'] ), $terms_names, "Failed to retrieve the {$taxonomy->labels->singular_name} names." ); // Make sure the test is not screwed before starting.
				$data[ $post_tax_arg ] = array(
					$taxonomy->name => implode( ', ', $terms_names ),
				);
			}

			$_REQUEST = $_POST = $data;
			do_action( 'load-post.php' );
			edit_post();

			// Assert the post's language.
			$post_lang = $this->pll_admin->model->post->get_language( $post_id );

			$this->assertInstanceOf( PLL_Language::class, $post_lang, 'No language has been assigned to the post.' );
			$this->assertSame( $to, $post_lang->slug, 'The post doesn\'t have the correct language.' );

			// Assert missing terms in FR.
			$terms[ $to ]['create'] = $this->pll_admin->model->term->get( $terms['en']['create'], $to );
			$this->assertGreaterThan( 0, $terms[ $to ]['create'], sprintf( "The {$taxonomy->labels->singular_name} that was missing in %s has not been created.", strtoupper( $to ) ) );
			$this->assertNotSame( $terms[ $from ]['create'], $terms[ $to ]['create'], sprintf( "The {$taxonomy->labels->singular_name} that was missing in %s has not been translated.", strtoupper( $to ) ) );

			// Assert terms assigned to the post in new language.
			$assigned_post_terms_ids = get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'object_ids' => $post_id,
					'fields'     => 'ids',
					'lang'       => '',
				)
			);
			$this->assertSameSets( $terms[ $to ], $assigned_post_terms_ids, "The post doesn\'t have the correct {$taxonomy->labels->name}." );
		}
	}

	/**
	 * @ticket #1401
	 * @see https://github.com/polylang/polylang/issues/1401.
	 *
	 * The sequence typically occurs when assigning the "faulty" tag in the block editor
	 * due to the post being saved 2 times (once with the REST API, once with edit_post().
	 */
	public function test_simple_update_of_post_with_tag_mixing_slug_and_name() {
		self::factory()->tag->create( array( 'name' => 'Unique name', 'slug' => 'common', 'lang' => 'en' ) );
		$tag = self::factory()->tag->create( array( 'name' => 'Common', 'slug' => 'unique-slug', 'lang' => 'en' ) );

		$post_id = self::factory()->post->create( array( 'tax_input' => array( 'post_tag' => array( $tag ) ), 'lang' => 'en' ) );
		wp_update_post( array( 'ID' => $post_id ) );

		$tags = wp_get_post_tags( $post_id );
		$this->assertNotWPError( $tags );
		$this->assertCount( 1, $tags );
		$this->assertSame( 'Common', reset( $tags )->name );
	}

	/**
	 * @ticket #1401
	 * @see https://github.com/polylang/polylang/issues/1401.
	 *
	 * The sequence typically occurs when assigning the "faulty" tag in the classic editor.
	 */
	public function test_existing_post_updated_with_tag_mixing_slug_and_name() {
		self::factory()->tag->create( array( 'name' => 'Unique name', 'slug' => 'common', 'lang' => 'en' ) );
		$tag = self::factory()->tag->create( array( 'name' => 'Common', 'slug' => 'unique-slug', 'lang' => 'en' ) );

		$post_id = self::factory()->post->create( array( 'lang' => 'en' ) );
		wp_update_post( array( 'ID' => $post_id, 'tax_input' => array( 'post_tag' => array( $tag ) ) ) );

		$tags = wp_get_post_tags( $post_id );
		$this->assertNotWPError( $tags );
		$this->assertCount( 1, $tags );
		$this->assertSame( 'Common', reset( $tags )->name );
	}

	/**
	 * Test a post creation with a non-existent default category to check that no category has been assigned to this post.
	 *
	 * @ticket 2248.
	 * @see https://github.com/polylang/polylang-pro/issues/2249.
	 */
	public function test_save_post_when_no_default_category_set() {
		$this->pll_admin->posts = new PLL_CRUD_Posts( $this->pll_admin );

		// Create an english category.
		$en_cat = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'English category' ) );
		$this->pll_admin->model->term->set_language( $en_cat, 'en' );

		// Create a post with this category so that `get_terms()` returns terms (since `hide_empty` is `true` by default).
		self::factory()->post->create( array( 'post_category' => array( $en_cat ) ) );

		update_option( 'default_category', $en_cat + 999 ); // Use this number to make sure the category doesn't exist.

		// Simulate the request that create the post and assigns language.
		$post = get_default_post_to_edit( 'post', true );
		$_REQUEST = $_POST = array(
			'post_lang_choice' => 'en',
			'_pll_nonce'       => wp_create_nonce( 'pll_language' ),
			'post_ID'          => $post->ID,
		);
		edit_post();

		$this->assertEmpty( wp_get_post_categories( $post->ID ) );
	}
}
