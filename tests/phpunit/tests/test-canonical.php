<?php

class Canonical_Test extends PLL_Canonical_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	public function post_canonical_url_provider() {
		return array(
			'post with name and language'  => array(
				'/en/post-format-test-audio/',
				array(
					'url' => '/en/post-format-test-audio/',
					'qv'  => array( 'lang' => 'en', 'name' => 'post-format-test-audio', 'page' => '' ),
				),
			),
			'post with incorrect language' => array( '/fr/post-format-test-audio/', '/en/post-format-test-audio/' ),
			'post without language'        => array( '/post-format-test-audio/', '/en/post-format-test-audio/' ),
		);
	}

	/**
	 * @dataProvider post_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_post( $test_url, $expected_url ) {
		$post_en = $this->factory->post->create( array( 'post_title' => 'post-format-test-audio' ) );
		self::$polylang->model->post->set_language( $post_en, 'en' );

		$this->assertCanonical( $test_url, $expected_url );
	}

	public function page_canonical_url_provider() {
		return array(
			'page with name and language'  => array(
				'/en/parent-page/',
				array(
					'url' => '/en/parent-page/',
					'qv'  => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
				),
			),
			'page with incorrect language' => array( '/fr/parent-page/', '/en/parent-page/' ),
			'page without language' => array( '/parent-page/', '/en/parent-page/' ),
		);
	}

	/**
	 * @dataProvider page_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_page( $test_url, $expected_url ) {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertCanonical( $test_url, $expected_url );
	}

	public function custom_post_type_canonical_url_provider() {
		return array(
			'custom post type with name and language' => array(
				'/en/pllcanonical/custom-post/',
				array(
					'url' => '/en/pllcanonical/custom-post/',
					'qv'  => array( 'lang' => 'en', 'pllcanonical' => 'custom-post', 'name' => 'custom-post', 'post_type' => 'pllcanonical', 'page' => '' ),
				),
			),
			'custom post type with incorrect language' => array( '/fr/pllcanonical/custom-post/', '/en/pllcanonical/custom-post/' ),
			'custom post type without language' => array( '/pllcanonical/custom-post/', '/en/pllcanonical/custom-post/' ),
		);
	}

	/**
	 * @dataProvider custom_post_type_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_cpt( $test_url, $expected_url ) {
		add_action(
			'registered_taxonomy',
			function( $taxonomy ) {
				if ( 'post_format' === $taxonomy && ! post_type_exists( 'pllcanonical' ) ) { // Last taxonomy registered in {@see https://github.com/WordPress/wordpress-develop/blob/36ef9cbca96fca46e7daf1ee687bb6a20788385c/src/wp-includes/taxonomy.php#L158-L174 create_initial_taxonomies()}.
					register_post_type( 'pllcanonical', array( 'public' => true ) );
				}
			}
		);

		$post_id = $this->factory->post->create( array( 'import_id' => 416, 'post_type' => 'pllcanonical', 'post_title' => 'custom-post' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertCanonical( $test_url, $expected_url );

		_unregister_post_type( 'pllcanonical' );
	}

	public function category_canonical_url_provider() {
		return array(
			'category with name and language' => array(
				'/en/category/parent/',
				array(
					'url' => '/en/category/parent/',
					'qv'  => array( 'lang' => 'en', 'category_name' => 'parent' ),
				),
			),
			'category with incorrect language' => array( '/fr/category/parent/', '/en/category/parent/' ),
			'category without language' => array( '/category/parent/', '/en/category/parent/' ),
		);
	}

	/**
	 * @dataProvider category_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_category( $test_url, $expected_url ) {
		$term_en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$polylang->model->term->set_language( $term_en, 'en' );

		$this->assertCanonical( $test_url, $expected_url );
	}

	public function posts_page_canonical_url_provider() {
		return array(
			'page for posts with name and language' => array(
				'/en/posts/',
				array(
					'url' => '/en/posts/',
					'qv'  => array( 'lang' => 'en', 'pagename' => 'posts', 'page' => '' ),
				),
			),
			'page for posts should match page_for_post option when language is incorrect' => array( '/fr/posts/', '/en/posts/' ),
			'page for should mathc page_for_post option posts without language' => array( '/posts/', '/en/posts/' ),
			'page_for_post option should be translated when language is incorrect' => array( '/en/articles/', '/fr/articles/' ),
			'page_for_post option should be translated when no language is set' => array( '/articles/', '/fr/articles/' ),
		);
	}

	/**
	 * @dataProvider posts_page_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_posts_page( $test_url, $expected_url ) {
		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'page' );

		$this->posts_en = $en = $this->factory->post->create( array( 'post_title' => 'posts', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$this->posts_fr = $fr = $this->factory->post->create( array( 'post_title' => 'articles', 'post_type' => 'page' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		update_option( 'page_for_posts', $fr );

		$this->assertCanonical( $test_url, $expected_url );
	}

	public function static_front_page_canonical_url_provider() {
		return array(
			'static front page with name and language' => array(
				'/en/parent-page/',
				array(
					'url' => '/en/parent-page/',
					'qv'  => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
				),
			),
			'static front page with incorrect language' => array( '/fr/parent-page/', '/en/parent-page/' ),
			'static front page without language' => array( '/parent-page/', '/en/parent-page/' ),
		);
	}

	/**
	 * Bug introduced in 1.8.2 and fixed in 1.8.3
	 *
	 * @dataProvider static_front_page_canonical_url_provider()
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_page_when_static_front_page_displays_posts( $test_url, $expected_url ) {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_title' => 'parent-page' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );

		self::$polylang->static_pages = new PLL_Admin_Static_Pages( self::$polylang );
		update_option( 'show_on_front', 'posts' );

		$this->assertCanonical( $test_url, $expected_url );
	}
}
