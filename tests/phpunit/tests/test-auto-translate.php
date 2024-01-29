<?php

class Auto_Translate_Test extends PLL_UnitTestCase {

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // Translated custom post type with archives.
		register_taxonomy( 'trtax', 'trcpt' ); // Translated custom tax.

		$options = array(
			'post_types' => array( 'trcpt' => 'trcpt' ),
			'taxonomies' => array( 'trtax' => 'trtax' ),
		);

		$frontend = ( new PLL_Context_Frontend( array( 'options' => $options ) ) )->get();

		$frontend->curlang = $frontend->model->get_language( 'fr' );
	}

	public function test_category() {
		$cats = self::factory()->category->create_translated(
			array( 'name' => 'test', 'lang' => 'en' ),
			array( 'name' => 'essai', 'lang' => 'fr' )
		);

		$post_fr = self::factory()->post->create( array( 'lang' => 'fr' ) );
		wp_set_post_terms( $post_fr, array( $cats['fr'] ), 'category' );

		$post_en = self::factory()->post->create( array( 'lang' => 'en' ) );
		wp_set_post_terms( $post_en, array( $cats['en'] ), 'category' );

		$expected = array( get_post( $post_fr ) );

		$this->assertEquals( $expected, get_posts( array( 'cat' => $cats['en'] ) ) );
		$this->assertEquals( $expected, get_posts( array( 'category_name' => 'test' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'category__in' => array( $cats['en'] ) ) ) );
	}

	public function test_tag() {
		$tags = self::factory()->tag->create_translated(
			array( 'name' => 'test', 'lang' => 'en' ),
			array( 'name' => 'essai', 'lang' => 'fr' )
		);

		self::factory()->tag->create_translated(
			array( 'name' => 'test2', 'lang' => 'en' ),
			array( 'name' => 'essai2', 'lang' => 'fr' )
		);

		self::factory()->post->create( array( 'tags_input' => array( 'test', 'test2' ), 'lang' => 'en' ) );
		$post_fr = self::factory()->post->create( array( 'tags_input' => array( 'essai', 'essai2' ), 'lang' => 'fr' ) );

		$expected = array( get_post( $post_fr ) );

		$this->assertEquals( $expected, get_posts( array( 'tag_id' => $tags['en'] ) ) );
		$this->assertEquals( $expected, get_posts( array( 'tag' => 'test' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'tag' => 'test,test2' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'tag' => 'test+test2' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'tag' => array( 'test', 'test2' ) ) ) );
		$this->assertEquals( $expected, get_posts( array( 'tag_slug__in' => array( 'test' ) ) ) );
	}

	public function test_custom_tax() {
		$terms = self::factory()->term->create_translated(
			array( 'taxonomy' => 'trtax', 'name' => 'test', 'lang' => 'en' ),
			array( 'taxonomy' => 'trtax', 'name' => 'essai', 'lang' => 'fr' )
		);

		self::factory()->term->create_translated(
			array( 'taxonomy' => 'trtax', 'name' => 'test2', 'lang' => 'en' ),
			array( 'taxonomy' => 'trtax', 'name' => 'essai2', 'lang' => 'fr' )
		);

		$terms3 = self::factory()->term->create_translated(
			array( 'taxonomy' => 'trtax', 'name' => 'test3', 'lang' => 'en' ),
			array( 'taxonomy' => 'trtax', 'name' => 'essai3', 'lang' => 'fr' )
		);

		$post_fr = self::factory()->post->create( array( 'post_type' => 'trcpt', 'lang' => 'fr' ) );
		wp_set_post_terms( $post_fr, array( 'essai', 'essai2' ), 'trtax' ); // Don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post.

		$post_en = self::factory()->post->create( array( 'post_type' => 'trcpt', 'lang' => 'en' ) );
		wp_set_post_terms( $post_en, array( 'test', 'test2' ), 'trtax' ); // Don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post.

		$expected = array( get_post( $post_fr ) );

		// Legacy way.
		$this->assertEquals( $expected, get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test,test2' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test+test2' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'post_type' => 'trcpt', 'trtax' => array( 'test', 'test2' ) ) ) );

		// Tax query.
		$args = array(
			'post_type' => 'trcpt',
			'tax_query' => array(
				array(
					'taxonomy' => 'trtax',
					'terms'    => 'test',
					'field'    => 'slug',
				),
			),
		);

		$this->assertEquals( $expected, get_posts( $args ) );

		// Nested tax query.
		$args = array(
			'post_type' => 'trcpt',
			'tax_query' => array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'trtax',
					'field'    => 'term_id',
					'terms'    => array( $terms3['en'] ),
				),
				array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'trtax',
						'field'    => 'term_id',
						'terms'    => array( $terms['en'] ),
					),
					array(
						'taxonomy' => 'trtax',
						'field'    => 'slug',
						'terms'    => array( 'test2' ),
					),
				),
			),
		);
		$query = new WP_Query( $args );

		$this->assertEquals( $terms3['fr'], $query->tax_query->queries[0]['terms'][0] );
		$this->assertEquals( $terms['fr'], $query->tax_query->queries[1][0]['terms'][0] );
		$this->assertEquals( 'essai2', $query->tax_query->queries[1][1]['terms'][0] );

		// #223.
		$args = array(
			'post_type' => 'trcpt',
			'lang'      => '',
			'tax_query' => array(
				array(
					'taxonomy' => 'trtax',
					'terms'    => array_values( $terms ),
					'field'    => 'term_id',
				),
			),
		);

		$query = new WP_Query( $args );

		$this->assertEqualSets( array( $post_en, $post_fr ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	public function test_post() {
		$posts = self::factory()->post->create_translated(
			array( 'post_title' => 'test', 'lang' => 'en' ),
			array( 'post_title' => 'essai', 'lang' => 'fr' )
		);

		$expected = array( get_post( $posts['fr'] ) );

		$this->assertEquals( $expected, get_posts( array( 'p' => $posts['en'] ) ) );
		$this->assertEquals( $expected, get_posts( array( 'name' => 'test' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'name' => 'test', 'post_type' => 'post' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'name' => 'test', 'post_type' => 'any' ) ) );
		$this->assertEquals( $expected, get_posts( array( 'name' => 'test', 'post_type' => array( 'post', 'page' ) ) ) );
		$this->assertEquals( $expected, get_posts( array( 'post__in' => array( $posts['en'] ) ) ) );
	}

	public function test_page() {
		$parents = self::factory()->post->create_translated(
			array( 'post_type' => 'page', 'post_title' => 'test_parent', 'lang' => 'en' ),
			array( 'post_type' => 'page', 'post_title' => 'essai_parent', 'lang' => 'fr' )
		);

		$pages = self::factory()->post->create_translated(
			array( 'post_type' => 'page', 'post_title' => 'test', 'post_parent' => $parents['en'], 'lang' => 'en' ),
			array( 'post_type' => 'page', 'post_title' => 'essai', 'post_parent' => $parents['fr'], 'lang' => 'fr' )
		);

		$expected = array( get_post( $pages['fr'] ) );

		$query = new WP_Query( array( 'page_id' => $pages['en'] ) );
		$this->assertEquals( $expected, $query->posts );

		$query = new WP_Query( array( 'pagename' => 'test_parent' ) ); // Top page.
		$this->assertEquals( array( get_post( $parents['fr'] ) ), $query->posts );

		$query = new WP_Query( array( 'pagename' => 'test_parent/test' ) ); // Child page.
		$this->assertEquals( $expected, $query->posts );

		$query = new WP_Query( array( 'post_parent' => $parents['en'], 'post_type' => 'page' ) );
		$this->assertEquals( $expected, $query->posts );

		$query = new WP_Query( array( 'post_parent__in' => array( $parents['en'] ), 'post_type' => 'page' ) );
		$this->assertEquals( $expected, $query->posts );
	}

	public function test_get_terms() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$expected = get_term( $fr, 'category' );
		$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => 0, 'include' => array( $en ) ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );

		$terms = get_terms( array( 'hide_empty' => 0, 'include' => array( $en ) ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );

		$expected = get_term( $en, 'category' );
		$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => 0, 'include' => array( $en ), 'lang' => '' ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );
	}
}
