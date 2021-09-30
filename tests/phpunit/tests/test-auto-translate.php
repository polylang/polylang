<?php

class Auto_Translate_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function set_up() {
		parent::set_up();

		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives
		register_taxonomy( 'trtax', 'trcpt' ); // translated custom tax

		$links_model = self::$model->get_links_model();
		$frontend = new PLL_Frontend( $links_model );
		new PLL_Frontend_Auto_Translate( $frontend );
		$frontend->curlang = self::$model->get_language( 'fr' );
		new PLL_Frontend_Filters( $frontend );
		new PLL_CRUD_Terms( $frontend );
	}

	function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	function test_category() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create();
		self::$model->post->set_language( $post_fr, 'fr' );
		wp_set_post_terms( $post_fr, array( $fr ), 'category' );

		$post_en = $this->factory->post->create();
		self::$model->post->set_language( $post_en, 'en' );
		wp_set_post_terms( $post_en, array( $en ), 'category' );

		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'cat' => $en ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'category_name' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'category__in' => array( $en ) ) ) );
	}

	function test_tag() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai2' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test2' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create( array( 'tags_input' => array( 'essai', 'essai2' ) ) );
		self::$model->post->set_language( $post_fr, 'fr' );

		$post_en = $this->factory->post->create( array( 'tags_input' => array( 'test', 'test2' ) ) );
		self::$model->post->set_language( $post_en, 'en' );

		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag_id' => $en ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test,test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test+test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag_slug__in' => array( 'test' ) ) ) );
	}

	function test_custom_tax() {
		$term_fr = $fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$term_en = $en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai2' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test2' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai3' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test3' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_fr, array( 'essai', 'essai2' ), 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post
		self::$model->post->set_language( $post_fr, 'fr' );

		$post_en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_en, array( 'test', 'test2' ), 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post
		self::$model->post->set_language( $post_en, 'en' );

		// old way
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test,test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test+test2' ) ) );

		// tax query
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

		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( $args ) );

		// Nested tax query
		$args = array(
			'post_type' => 'trcpt',
			'tax_query' => array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'trtax',
					'field'    => 'term_id',
					'terms'    => array( $en ),
				),
				array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'trtax',
						'field'    => 'term_id',
						'terms'    => array( $term_en ),
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

		$this->assertEquals( $fr, $query->tax_query->queries[0]['terms'][0] );
		$this->assertEquals( $term_fr, $query->tax_query->queries[1][0]['terms'][0] );
		$this->assertEquals( 'essai2', $query->tax_query->queries[1][1]['terms'][0] );

		// #223
		$args = array(
			'post_type' => 'trcpt',
			'lang'      => '',
			'tax_query' => array(
				array(
					'taxonomy' => 'trtax',
					'terms'    => array( $term_en, $term_fr ),
					'field'    => 'term_id',
				),
			),
		);

		$query = new WP_Query( $args );

		$this->assertEqualSets( array( $post_en, $post_fr ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	function test_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'p' => $en ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'name' => 'test' ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'name' => 'test', 'post_type' => 'post' ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'name' => 'test', 'post_type' => 'any' ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'name' => 'test', 'post_type' => array( 'post', 'page' ) ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post__in' => array( $en ) ) ) );
	}

	function test_page() {
		$parent_en = $en = $this->factory->post->create( array( 'post_title' => 'test_parent', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$parent_fr = $fr = $this->factory->post->create( array( 'post_title' => 'essai_parent', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page', 'post_parent' => $parent_en ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page', 'post_parent' => $parent_fr ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$query = new WP_Query( array( 'page_id' => $en ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
		$query = new WP_Query( array( 'pagename' => 'test_parent' ) ); // Top page
		$this->assertEquals( array( get_post( $parent_fr ) ), $query->posts );
		$query = new WP_Query( array( 'pagename' => 'test_parent/test' ) ); // Child page
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
		$query = new WP_Query( array( 'post_parent' => $parent_en, 'post_type' => 'page' ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
		$query = new WP_Query( array( 'post_parent__in' => array( $parent_en ), 'post_type' => 'page' ) );
		$this->assertEquals( array( get_post( $fr ) ), $query->posts );
	}

	function test_get_terms() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );
		self::$model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$expected = get_term( $fr, 'category' );
		$terms = get_terms( 'category', array( 'hide_empty' => 0, 'include' => array( $en ) ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );

		$terms = get_terms( array( 'hide_empty' => 0, 'include' => array( $en ) ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );

		$expected = get_term( $en, 'category' );
		$terms = get_terms( 'category', array( 'hide_empty' => 0, 'include' => array( $en ), 'lang' => '' ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );
	}
}
