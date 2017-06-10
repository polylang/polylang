<?php

class Auto_Translate_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		self::$polylang->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$polylang->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives
		register_taxonomy( 'trtax', 'trcpt' ); // translated custom tax

		self::$polylang->auto_translate = new PLL_Frontend_Auto_Translate( self::$polylang );
		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
	}

	function tearDown() {
		parent::tearDown();

		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	function test_category() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_fr, 'fr' );
		wp_set_post_terms( $post_fr, array( $fr ), 'category' );

		$post_en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_en, 'en' );
		wp_set_post_terms( $post_en, array( $en ), 'category' );

		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'cat' => $en ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'category_name' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'category__in' => array( $en ) ) ) );
	}

	function test_tag() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai2' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test2' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create( array( 'tags_input' => array( 'essai', 'essai2' ) ) );
		self::$polylang->model->post->set_language( $post_fr, 'fr' );

		$post_en = $this->factory->post->create( array( 'tags_input' => array( 'test', 'test2' ) ) );
		self::$polylang->model->post->set_language( $post_en, 'en' );

		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag_id' => $en ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test,test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag' => 'test+test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'tag_slug__in' => array( 'test' ) ) ) );
	}

	function test_custom_tax() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'essai2' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'trtax', 'name' => 'test2' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$post_fr = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_fr, array( 'essai', 'essai2' ), 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post
		self::$polylang->model->post->set_language( $post_fr, 'fr' );

		$post_en = $this->factory->post->create( array( 'post_type' => 'trcpt' ) );
		wp_set_post_terms( $post_en, array( 'test', 'test2' ), 'trtax' ); // don't use 'tax_input' above as we don't pass current_user_can test in wp_insert_post
		self::$polylang->model->post->set_language( $post_en, 'en' );

		// old way
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test,test2' ) ) );
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( array( 'post_type' => 'trcpt', 'trtax' => 'test+test2' ) ) );

		// tax query
		$args = array(
			'post_type' => 'trcpt',
			'tax_query' => array( array(
				'taxonomy' => 'trtax',
				'terms'    => 'test',
				'field'    => 'slug',
			) ),
		);
		$this->assertEquals( array( get_post( $post_fr ) ), get_posts( $args ) );
	}

	function test_post() {
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'p' => $en ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'name' => 'test' ) ) );
		$this->assertEquals( array( get_post( $fr ) ), get_posts( array( 'post__in' => array( $en ) ) ) );
	}

	function test_page() {
		$parent = $en = $this->factory->post->create( array( 'post_title' => 'test_parent', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai_parent', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$en = $this->factory->post->create( array( 'post_title' => 'test', 'post_type' => 'page', 'parent' => $en ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai', 'post_type' => 'page', 'parent' => $fr ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$query = new WP_Query( array( 'page_id' => $en ) );
		$this->assertEquals( array( get_post( $fr ) ),  $query->posts );

		$this->markTestIncomplete();
		// FIXME the tests below do not work

		$query = new WP_Query( array( 'pagename' => 'test' ) );
		$this->assertEquals( array( get_post( $fr ) ),  $query->posts );
		$query = new WP_Query( array( 'post_parent' => $parent ) );
		$this->assertEquals( array( get_post( $fr ) ),  $query->posts );
		$query = new WP_Query( array( 'post_parent__in' => array( $parent ) ) );
		$this->assertEquals( array( get_post( $fr ) ),  $query->posts );
	}

	function test_get_terms() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		self::$polylang->model->term->save_translations( $en, compact( 'en', 'fr' ) );

		$expected = get_term( $fr, 'category' );
		$terms = get_terms( 'category', array( 'hide_empty' => 0, 'include' => array( $en ) ) );
		$this->assertEquals( array( $expected->term_id ), wp_list_pluck( $terms, 'term_id' ) );
	}
}
