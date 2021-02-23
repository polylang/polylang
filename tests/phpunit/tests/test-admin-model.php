<?php

class Admin_Model_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function update_language( $lang, $args ) {
		foreach ( array( 'name', 'slug', 'locale', 'term_group' ) as $key ) {
			$defaults[ $key ] = $lang->$key;
		}
		$args['rtl'] = $lang->is_rtl;
		$args['flag'] = $lang->flag_code;
		$args['lang_id'] = $lang->term_id;
		$args = wp_parse_args( $args, $defaults );
		self::$model->update_language( $args );
	}

	function test_change_language_slug() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'en', 'fr' ) );

		$this->update_language( self::$model->get_language( 'en' ), array( 'slug' => 'eng' ) );
		$this->assertFalse( self::$model->get_language( 'en' ) );
		$this->assertEquals( 'eng', self::$model->options['default_lang'] );
		$this->assertEquals( 'eng', self::$model->post->get_language( $en )->slug );

		$this->update_language( self::$model->get_language( 'fr' ), array( 'slug' => 'fra' ) );
		$this->assertEquals( $fr, self::$model->post->get( $en, 'fra' ) );
		$this->assertEquals( $en, self::$model->post->get( $fr, 'eng' ) );

		// FIXME test widgets, menu locations and domains
	}

	function test_get_objects_with_no_lang() {
		register_post_type( 'cpt' ); // add untranslated custom post type
		register_taxonomy( 'tax', 'cpt' ); // add untranslated taxonomy

		// 2 posts with language
		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );

		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'fr' );

		// 2 posts in non translated post types
		$this->factory->post->create( array( 'post_type' => 'nav_menu_item' ) );
		$this->factory->post->create( array( 'post_type' => 'cpt' ) );

		// 2 posts without language
		$expected['posts'][] = $this->factory->post->create();
		$expected['posts'][] = $this->factory->post->create( array( 'post_type' => 'page' ) );

		// 2 terms with language
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$model->term->set_language( $term_id, 'en' );

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );
		self::$model->term->set_language( $term_id, 'fr' );

		// 2 terms in non translated taxonomies
		$this->factory->term->create( array( 'taxonomy' => 'nav_menu' ) );
		$this->factory->term->create( array( 'taxonomy' => 'tax' ) );

		// 2 terms without language
		$expected['terms'][] = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		$expected['terms'][] = $this->factory->term->create( array( 'taxonomy' => 'post_tag' ) );

		$nolang = self::$model->get_objects_with_no_lang();

		// sort arrays as values don't have necessarily the same keys and order
		$this->assertTrue( sort( $expected['posts'] ), sort( $nolang['posts'] ) );
		$this->assertTrue( sort( $expected['terms'] ), sort( $nolang['terms'] ) );

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
	}

	function test_set_language_in_mass_for_posts() {
		foreach ( $this->factory->post->create_many( 2, array() ) as $p ) {
			self::$model->post->set_language( $p, 'en' );
		}

		foreach ( $this->factory->post->create_many( 2, array() ) as $p ) {
			self::$model->post->set_language( $p, 'fr' );
		}

		$posts = $this->factory->post->create_many( 2 );
		self::$model->set_language_in_mass( 'post', $posts, 'fr' );

		$posts = get_posts( array( 'fields' => 'ids', 'posts_per_page' => -1 ) );
		$languages = wp_list_pluck( array_map( array( self::$model->post, 'get_language' ), $posts ), 'slug' );
		$this->assertEquals( array( 'fr' => 4, 'en' => 2 ), array_count_values( $languages ) );
		$this->assertEmpty( get_terms( 'post_translations' ) ); // no translation group for posts
	}

	function test_set_language_in_mass_for_terms() {
		foreach ( $this->factory->tag->create_many( 2 ) as $t ) {
			self::$model->term->set_language( $t, 'en' );
		}

		foreach ( $this->factory->tag->create_many( 2 ) as $t ) {
			self::$model->term->set_language( $t, 'fr' );
		}

		$tags = $this->factory->tag->create_many( 2 );
		self::$model->set_language_in_mass( 'term', $tags, 'fr' );

		$terms = get_terms( 'post_tag', array( 'hide_empty' => false, 'fields' => 'ids' ) );
		$languages = wp_list_pluck( array_map( array( self::$model->term, 'get_language' ), $terms ), 'slug' );
		$this->assertEquals( array( 'fr' => 4, 'en' => 2 ), array_count_values( $languages ) );
		$this->assertCount( 6, get_terms( 'term_translations' ) ); // one translation group per tag and none for default categories
	}
}
