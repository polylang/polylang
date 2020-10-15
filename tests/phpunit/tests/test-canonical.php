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

	/**
	 * @param $test_url
	 * @param $expected_url
	 */
	public function _test_canonical_redirect( $test_url, $expected_url ) {
		global $wp_rewrite;

		// Needed by {@see pll_requested_url()}.
		$_SERVER['REQUEST_URI'] = $test_url;

		$options = array_merge(
			PLL_Install::get_default_options(),
			array(
				'default_lang' => 'en',
				'hide_default' => 0,
				'post_types' => array(
					'cpt' => 'cpt', // translate the cpt // FIXME /!\ 'after_setup_theme' already fired and the list of translated post types is already cached :(
				),
			)
		);
		$model = new PLL_Model( $options );
		$links_model = new PLL_Links_Directory( $model );
		self::$polylang = new PLL_Frontend( $links_model );
		self::$polylang->init();


		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );

		// register post types and taxonomies
		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();
		register_post_type( 'cpt', array( 'public' => true ) ); // add custom post type

		// reset the links model according to the permalink structure
		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		// flush rules
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->flush_rules();

		$this->assertCanonical( $test_url, $expected_url );
	}

	function tearDown() {
		parent::tearDown();

		_unregister_post_type( 'cpt' );
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
			'post from plain permalink'    => array( '/en/?p={post_id}', '/en/post-format-test-audio/' ),
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
		$test_url = str_replace( '{post_id}', $post_en, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
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
			'page without language'        => array( '/parent-page/', '/en/parent-page/' ),
			'page from plain permalink'    => array( '/en/?p={page_id}', '/en/parent-page/' ),
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
		$test_url = str_replace( '{page_id}', $post_id, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
	}

	public function custom_post_type_canonical_url_provider() {
		return array(
			'custom post type with name and language' => array(
				'/en/cpt/custom-post/',
				array(
					'url' => '/en/cpt/custom-post/',
					'qv'  => array( 'lang' => 'en', 'cpt' => 'custom-post', 'name' => 'custom-post', 'post_type' => 'cpt', 'page' => '' ),
				),
			),
			'custom post type with incorrect language' => array( '/fr/cpt/custom-post/', '/en/cpt/custom-post/' ),
			'custom post type without language'        => array( '/cpt/custom-post/', '/en/cpt/custom-post/' ),
			'custom post type from plain permalink'    => array( '/en/?p={cpt_id}', '/en/cpt/custom-post/' ),
		);
	}

	/**
	 * @dataProvider custom_post_type_canonical_url_provider
	 *
	 * @param string       $test_url
	 * @param string|array $expected_url
	 */
	function test_cpt( $test_url, $expected_url ) {
		// custom post type
		$post_id = $this->factory->post->create( array( 'import_id' => 416, 'post_type' => 'cpt', 'post_title' => 'custom-post' ) );
		self::$polylang->model->post->set_language( $post_id, 'en' );
		$test_url = str_replace( '{cpt_id}', $post_id, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
	}

	public function category_canonical_url_provider() {
		return array(
			'category with name and language'  => array(
				'/en/category/parent/',
				array(
					'url' => '/en/category/parent/',
					'qv'  => array( 'lang' => 'en', 'category_name' => 'parent' ),
				),
			),
			'category with incorrect language' => array( '/fr/category/parent/', '/en/category/parent/' ),
			'category without language'        => array( '/category/parent/', '/en/category/parent/' ),
			'category from plain permalink'    => array( '/?cat={category_id}', '/en/category/parent/' ),
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
		$test_url = str_replace( '{category_id}', $term_en, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
	}

	public function posts_page_canonical_url_provider() {
		return array(
			'page for posts with name and language'  => array(
				'/en/posts/',
				array(
					'url' => '/en/posts/',
					'qv'  => array( 'lang' => 'en', 'pagename' => 'posts', 'page' => '' ),
				),
			),
			'page for posts with incorrect language' => array( '/fr/posts/', '/en/posts/' ),
			'page for poests without language'       => array( '/posts/', '/en/posts/' ),
			'page for post from plain permalink'     => array( '/?p={page_id}', '/en/posts/' ),
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
		$test_url = str_replace( '{page_id}', $en, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
	}

	public function static_front_page_canonical_url_provider() {
		return array(
			'static front page with name and language'  => array(
				'/en/parent-page/',
				array(
					'url' => '/en/parent-page/',
					'qv'  => array( 'lang' => 'en', 'pagename' => 'parent-page', 'page' => '' ),
				),
			),
			'static front page with incorrect language' => array( '/fr/parent-page/', '/en/parent-page/' ),
			'static front page without language'        => array( '/parent-page/', '/en/parent-page/' ),
			'static front page from plain permalink'    => array( '/?p={page_id}', '/en/parent-page/' ),
		);
	}

	/**
	 * bug introduced in 1.8.2 and fixed in 1.8.3
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
		$test_url = str_replace( '{page_id}', $post_id, $test_url );

		$this->_test_canonical_redirect( $test_url, $expected_url );
	}
}
