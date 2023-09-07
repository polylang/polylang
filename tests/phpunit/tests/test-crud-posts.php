<?php

class CRUD_Posts_Test extends PLL_UnitTestCase {
	protected static $editor;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		$links_model     = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$admin_default_term = new PLL_Admin_Default_Term( $pll_admin );
		$admin_default_term->add_hooks();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
		self::create_language( 'es_ES' );

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can tests.

		register_taxonomy( 'custom_tax', 'post' );

		$options = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
				'taxonomies'   => array( 'custom_tax' => 'custom_tax' ),
			)
		);

		$model                         = new PLL_Admin_Model( $options );
		$links_model                   = new PLL_Links_Default( $model );
		$this->pll_admin               = new PLL_Admin( $links_model );
		$this->pll_admin->posts        = new PLL_CRUD_Posts( $this->pll_admin );
		$this->pll_admin->terms        = new PLL_CRUD_Terms( $this->pll_admin );
		$this->pll_admin->filters_post = new PLL_Admin_Filters_Post( $this->pll_admin );
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
		$term_en = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$this->pll_admin->model->term->set_language( $term_en, 'en' );
		$term_fr = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$this->pll_admin->model->term->set_language( $term_fr, 'fr' );
		$this->pll_admin->model->term->save_translations(
			$term_en,
			array(
				'en' => $term_en,
				'fr' => $term_fr,
			)
		);

		$term_input = in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ? array( $term_en ) : array( $taxonomy => array( $term_en ) );  // Special case fo custom taxonomies.
		$post       = self::factory()->post->create_and_get( array( $post_tax_arg => $term_input ) );
		$this->pll_admin->model->post->set_language( $post->ID, 'en' );

		// Change post language and update it.
		$this->pll_admin->model->post->set_language( $post->ID, 'fr' );
		$postarr = $post->to_array();

		// Pass the term in previous language on purpose.
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			wp_set_current_user( 1 ); // Current user should have the proper capability to set custom taxonomy terms.
			$postarr[ $post_tax_arg ] = array( $taxonomy => array( $term_en ) ); // Special case fo custom taxonomies wich expects an array of array.
		} elseif ( 'post_tag' === $taxonomy ) {
			$postarr[ $post_tax_arg ] = array( get_term( $term_en, $taxonomy )->name ); // Special case for tags where `wp_update_post()` removes existing one by names.
		} else {
			$postarr[ $post_tax_arg ] = array( $term_en ); // Pass the term in previous language on purpose.
		}

		$result = wp_update_post( $postarr );

		$this->assertSame( $post->ID, $result, 'The post should be well updated.' );
		$this->assert_has_language( $post, 'fr', 'The post language should be French.' );

		$post  = get_post( $post->ID );
		$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		$this->assertNotWPError( $terms );
		$this->assertCount( 1, $terms, "The post should have only one {$taxonomy}." );
		$this->assertSame( $term_fr, reset( $terms ), "The {$taxonomy} should have been translated." );
	}

	/**
	 * @ticket #1766 see {https://github.com/polylang/polylang-pro/issues/1766}.
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
			// Create a term with parents in default language only.
			$translations = array();
			$parent       = 0;

			for ( $i = 1; $i <= 3; $i++ ) {
				$parent = self::factory()->term->create(
					array(
						'taxonomy' => $taxonomy->name,
						'name'     => "{$taxonomy->labels->singular_name} $i EN with no trad",
						'parent'   => $parent,
					)
				);
				self::$model->term->set_language( $parent, 'en' );
				$translations[ $i ]['en'] = $parent;
			}
			foreach ( $translations as $translation_group ) {
				self::$model->term->save_translations(
					reset( $translation_group ),
					$translation_group
				);
			}

			$terms['en']['create'] = end( $translations )['en'];
		} else {
			// Create a term in default language only.
			$translations = array();
			$term_id      = self::factory()->term->create(
				array(
					'taxonomy' => $taxonomy->name,
					'name'     => "{$taxonomy->labels->singular_name} EN with no trad",
				)
			);
			self::$model->term->set_language( $term_id, 'en' );
			$translations['en'] = $term_id;

			self::$model->term->save_translations(
				reset( $translations ),
				$translations
			);
			$terms['en']['create'] = $translations['en'];
		}

		// Create terms with same name but different slug in all 3 languages.
		$translations = array();
		foreach ( array( 'en', 'fr', 'de' ) as $lang_slug ) {
			$term_id = self::factory()->term->create(
				array(
					'taxonomy' => $taxonomy->name,
					'name'     => "{$taxonomy->labels->singular_name} same name",
					'slug'     => "{$prefix}-same-name-{$lang_slug}",
				)
			);
			self::$model->term->set_language( $term_id, $lang_slug );
			$translations[ $lang_slug ] = $term_id;
			$terms[ $lang_slug ][]      = $term_id;
		}
		self::$model->term->save_translations(
			reset( $translations ),
			$translations
		);

		// Create terms with their slug equals sanitize_title( name ) in all 3 languages.
		$translations = array();
		foreach ( array( 'en', 'fr', 'de' ) as $lang_slug ) {
			$term_id = self::factory()->term->create(
				array(
					'taxonomy' => $taxonomy->name,
					'name'     => "{$taxonomy->labels->singular_name} slug from name $lang_slug",
					'slug'     => "{$prefix}-slug-from-name-{$lang_slug}",
				)
			);
			self::$model->term->set_language( $term_id, $lang_slug );
			$translations[ $lang_slug ] = $term_id;
			$terms[ $lang_slug ][]      = $term_id;
		}
		self::$model->term->save_translations(
			reset( $translations ),
			$translations
		);

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
			$post_lang = self::$model->post->get_language( $post_id );

			$this->assertInstanceOf( PLL_Language::class, $post_lang, 'No language has been assigned to the post.' );
			$this->assertSame( $to, $post_lang->slug, 'The post doesn\'t have the correct language.' );

			// Assert missing terms in FR.
			$terms[ $to ]['create'] = self::$model->term->get( $terms['en']['create'], $to );
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
}
