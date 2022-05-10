<?php

class Model_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function test_languages_list() {
		self::$model->post->register_taxonomy(); // needed otherwise posts are not counted

		$this->assertSame( array( 'en', 'fr' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );
		$this->assertSame( array( 'English', 'Français' ), self::$model->get_languages_list( array( 'fields' => 'name' ) ) );
		$this->assertSame( array(), self::$model->get_languages_list( array( 'hide_empty' => true ) ) );

		$post_id = $this->factory->post->create();
		self::$model->post->set_language( $post_id, 'en' );

		$this->assertSame( array( 'en' ), self::$model->get_languages_list( array( 'fields' => 'slug', 'hide_empty' => true ) ) );
	}

	public function test_languages_list_order() {
		$languages = array(
			'it_IT' => array(
				'term_group' => 17,
			),
			'es_ES' => array(
				'term_group' => 6,
			),
		);

		foreach ( $languages as $locale => $data ) {
			self::create_language( $locale, $data );
		}

		$languages = self::$model->get_languages_list( array( 'fields' => 'slug' ) );
		$expected  = array( 'en', 'fr', 'es', 'it' );

		$this->assertSame( $expected, $languages, 'Expected the languages to be ordered by term_group and term_id.' );
	}

	public function test_term_exists() {
		$parent = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$model->term->set_language( $parent, 'en' );
		$child = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'child', 'parent' => $parent ) );
		self::$model->term->set_language( $child, 'en' );

		$this->assertSame( $parent, self::$model->term_exists( 'parent', 'category', 0, 'en' ) );
		$this->assertSame( $child, self::$model->term_exists( 'child', 'category', 0, 'en' ) );
		$this->assertSame( $child, self::$model->term_exists( 'child', 'category', $parent, 'en' ) );
		$this->assertEmpty( self::$model->term_exists( 'parent', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$model->term_exists( 'child', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$model->term_exists( 'child', 'category', $parent, 'fr' ) );
	}

	/**
	 * Bug fixed in 2.7
	 */
	public function test_term_exists_with_special_character() {
		$term = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'Cook & eat' ) );
		self::$model->term->set_language( $term, 'en' );
		$this->assertSame( $term, self::$model->term_exists( 'Cook & eat', 'category', 0, 'en' ) );
	}

	public function test_count_posts() {
		$en = $this->factory->post->create();
		self::$model->post->set_language( $en, 'en' );

		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $en, 'aside' );
		self::$model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1, 'post_status' => 'draft' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $fr, 'aside' );
		self::$model->post->set_language( $fr, 'fr' );

		$language = self::$model->get_language( 'fr' );
		$this->assertSame( 2, self::$model->count_posts( $language ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'post_format' => 'post-format-aside' ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'year' => 2007 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9, 'day' => 4 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'm' => 2007 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'm' => 200709 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'm' => 20070904 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'author' => 1 ) ) );
		$this->assertSame( 1, self::$model->count_posts( $language, array( 'author_name' => 'admin' ) ) );

		// Bug fixed in version 2.2.6
		$this->assertSame( 2, self::$model->count_posts( $language, array( 'post_type' => array( 'post', 'page' ) ) ) );
	}

	public function test_translated_post_types() {
		// deactivate the cache
		self::$model->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$model->cache->method( 'get' )->willReturn( false );

		self::$model->options['media_support'] = 0;

		$this->assertTrue( self::$model->is_translated_post_type( 'post' ) );
		$this->assertTrue( self::$model->is_translated_post_type( 'page' ) );
		$this->assertFalse( self::$model->is_translated_post_type( 'nav_menu_item' ) );
		$this->assertFalse( self::$model->is_translated_post_type( 'attachment' ) );

		self::$model->options['media_support'] = 1;
		$this->assertTrue( self::$model->is_translated_post_type( 'attachment' ) );

		self::$model->cache = new PLL_Cache();
	}

	public function test_translated_taxonomies() {
		$this->assertTrue( self::$model->is_translated_taxonomy( 'category' ) );
		$this->assertTrue( self::$model->is_translated_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$model->is_translated_taxonomy( 'language' ) );
	}

	public function test_filtered_taxonomies() {
		$this->assertTrue( self::$model->is_filtered_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'category' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( 'language' ) );
	}

	public function test_is_translated_post_type() {
		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);


		register_post_type( 'trcpt' ); // translated custom post type
		register_post_type( 'cpt' ); // *untranslated* custom post type

		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		$this->assertTrue( pll_is_translated_post_type( 'trcpt' ) );
		$this->assertFalse( pll_is_translated_post_type( 'cpt' ) );

		$this->assertTrue( pll_is_translated_post_type( array( 'trcpt' ) ) );
		$this->assertFalse( pll_is_translated_post_type( array( 'cpt' ) ) );

		$this->assertTrue( pll_is_translated_post_type( array( 'trcpt', 'cpt' ) ) );

		_unregister_post_type( 'cpt' );
		_unregister_post_type( 'trcpt' );
		unset( $GLOBALS['polylang'] );
	}

	public function test_is_translated_taxonomy() {
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		register_taxonomy( 'trtax', 'post' ); // translated custom tax
		register_taxonomy( 'tax', 'post' ); // *untranslated* custom tax

		$links_model = self::$model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );

		$this->assertTrue( pll_is_translated_taxonomy( 'trtax' ) );
		$this->assertFalse( pll_is_translated_taxonomy( 'tax' ) );

		$this->assertTrue( pll_is_translated_taxonomy( array( 'trtax' ) ) );
		$this->assertFalse( pll_is_translated_taxonomy( array( 'tax' ) ) );

		$this->assertTrue( pll_is_translated_taxonomy( array( 'trtax', 'tax' ) ) );

		_unregister_taxonomy( 'tax' );
		_unregister_taxonomy( 'trtax' );
		unset( $GLOBALS['polylang'] );
	}

	public function test_is_filtered_taxonomy() {
		$this->assertTrue( self::$model->is_filtered_taxonomy( array( 'post_format' ) ) );
		$this->assertFalse( self::$model->is_filtered_taxonomy( array( 'category' ) ) );
		$this->assertTrue( self::$model->is_filtered_taxonomy( array( 'post_format', 'category' ) ) );
	}
}

