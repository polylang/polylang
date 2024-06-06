<?php

class Filters_Links_Test extends PLL_UnitTestCase {
	protected $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		global $wp_rewrite;

		self::$model->options['hide_default'] = 1;
		self::$model->options['post_types'] = array(
			'trcpt' => 'trcpt',
		);
		self::$model->options['taxonomies'] = array(
			'trtax' => 'trtax',
		);

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );

		create_initial_taxonomies();
		register_post_type( 'trcpt', array( 'public' => true, 'has_archive' => true ) ); // translated custom post type with archives
		register_taxonomy( 'trtax', 'trcpt' ); // translated custom tax
		register_post_type( 'cpt', array( 'public' => true, 'has_archive' => true ) ); // *untranslated* custom post type with archives
		register_taxonomy( 'tax', 'cpt' ); // *untranslated* custom tax
		$links_model = self::$model->get_links_model();
		$links_model->init();

		$wp_rewrite->flush_rules();

		// add links filter and de-activate the cache
		$this->frontend = new PLL_Frontend( $links_model );
		$this->frontend->filters_links = new PLL_Frontend_Filters_Links( $this->frontend );
		$this->frontend->filters_links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		$this->frontend->filters_links->cache->method( 'get' )->willReturn( false );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
		_unregister_post_type( 'trcpt' );
		_unregister_taxonomy( 'trtax' );
	}

	public function test_get_permalink_for_posts() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $post_id, 'en' );
		$this->assertEquals( home_url( '/test/' ), get_permalink( $post_id ) );

		$post_id = self::factory()->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		$this->assertEquals( home_url( '/fr/essai/' ), get_permalink( $post_id ) );
	}

	public function test_get_permalink_for_pages() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'page-test', 'post_type' => 'page' ) );
		self::$model->post->set_language( $post_id, 'en' );
		$this->assertEquals( home_url( '/page-test/' ), get_permalink( $post_id ) );

		$post_id = self::factory()->post->create( array( 'post_title' => 'page-essai', 'post_type' => 'page' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		$this->assertEquals( home_url( '/fr/page-essai/' ), get_permalink( $post_id ) );
	}

	public function test_get_permalink_for_cpt() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'test', 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $post_id, 'en' );
		$this->assertEquals( home_url( '/trcpt/test/' ), get_permalink( $post_id ) );

		$post_id = self::factory()->post->create( array( 'post_title' => 'essai', 'post_type' => 'trcpt' ) );
		self::$model->post->set_language( $post_id, 'fr' );
		$this->assertEquals( home_url( '/fr/trcpt/essai/' ), get_permalink( $post_id ) );
	}

	public function test_get_permalink_for_untranslated_cpt() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'test', 'post_type' => 'cpt' ) );
		$this->assertEquals( home_url( '/cpt/test/' ), get_permalink( $post_id ) );
	}

	public function test_attached_attachment() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $post_id, 'en' );

		$args = array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
			'post_title'     => 'image-en',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
			'file'           => 'image.jpg',
		);

		$attachment_id = self::factory()->attachment->create_object( $args );
		self::$model->post->set_language( $attachment_id, 'en' );
		$this->assertEquals( home_url( '/test/image-en/' ), get_permalink( $attachment_id ) );

		$post_id = self::factory()->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $post_id, 'fr' );

		$args = array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
			'post_title'     => 'image-fr',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
			'file'           => 'image.jpg',
		);

		$attachment_id = self::factory()->attachment->create_object( $args );
		self::$model->post->set_language( $attachment_id, 'fr' );
		$this->assertEquals( home_url( '/fr/essai/image-fr/' ), get_permalink( $attachment_id ) );
	}

	public function test_unattached_attachment() {
		$attachment_id = self::factory()->attachment->create_object(
			'image.jpg',
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_title'     => 'image-en',
				'post_status'    => 'inherit',
			)
		);
		self::$model->post->set_language( $attachment_id, 'en' );
		$this->assertEquals( home_url( '/image-en/' ), get_permalink( $attachment_id ) );

		$attachment_id = self::factory()->attachment->create_object(
			'image.jpg',
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_title'     => 'image-fr',
				'post_status'    => 'inherit',
			)
		);
		self::$model->post->set_language( $attachment_id, 'fr' );
		$this->assertEquals( home_url( '/fr/image-fr/' ), get_permalink( $attachment_id ) );
	}

	public function test_translated_term_link() {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'cats' ) );
		self::$model->term->set_language( $term_id, 'en' );
		$this->assertEquals( home_url( '/category/cats/' ), get_term_link( $term_id, 'category' ) );

		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'chats' ) );
		self::$model->term->set_language( $term_id, 'fr' );
		$this->assertEquals( home_url( '/fr/category/chats/' ), get_term_link( $term_id, 'category' ) );
	}

	public function test_untranslated_term_link() {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'tax', 'name' => 'cats' ) );
		$this->assertEquals( home_url( '/tax/cats/' ), get_term_link( $term_id, 'tax' ) );
	}

	public function test_language_term_link() {
		$term_id = self::$model->get_language( 'en' )->term_id;
		$this->assertEquals( home_url( '/' ), get_term_link( $term_id, 'language' ) );
		$term_id = self::$model->get_language( 'fr' )->term_id;
		$this->assertEquals( home_url( '/fr/' ), get_term_link( $term_id, 'language' ) );
	}

	public function test_post_format_link() {
		self::factory()->term->create( array( 'taxonomy' => 'post_format', 'name' => 'post-format-aside' ) ); // shouldn't WP do that ?

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( home_url( '/type/aside/' ), get_post_format_link( 'aside' ) );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( home_url( '/fr/type/aside/' ), get_post_format_link( 'aside' ) );
	}

	public function test_archive_link() {
		$this->frontend->curlang = self::$model->get_language( 'en' );
		$this->assertEquals( home_url( '/trcpt/' ), get_post_type_archive_link( 'trcpt' ) );
		$this->assertEquals( home_url( '/feed/' ), get_feed_link() );
		$this->assertEquals( home_url( '/author/' . get_userdata( 1 )->user_nicename . '/' ), get_author_posts_url( 1 ) );
		$this->assertEquals( home_url( '/search/test/' ), get_search_link( 'test' ) );
		$this->assertEquals( home_url( '/2015/' ), get_year_link( 2015 ) );
		$this->assertEquals( home_url( '/2015/10/' ), get_month_link( 2015, 10 ) );
		$this->assertEquals( home_url( '/2015/10/05/' ), get_day_link( 2015, 10, 5 ) );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$this->assertEquals( home_url( '/fr/trcpt/' ), get_post_type_archive_link( 'trcpt' ) );
		$this->assertEquals( home_url( '/fr/feed/' ), get_feed_link() );
		$this->assertEquals( home_url( '/fr/author/' . get_userdata( 1 )->user_nicename . '/' ), get_author_posts_url( 1 ) );
		$this->assertEquals( home_url( '/fr/search/test/' ), get_search_link( 'test' ) );
		$this->assertEquals( home_url( '/fr/2015/' ), get_year_link( 2015 ) );
		$this->assertEquals( home_url( '/fr/2015/10/' ), get_month_link( 2015, 10 ) );
		$this->assertEquals( home_url( '/fr/2015/10/05/' ), get_day_link( 2015, 10, 5 ) );
	}

	public function test_get_custom_logo() {
		// Setup logo
		$filename = __DIR__ . '/../data/image.jpg';
		$contents = file_get_contents( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$upload   = wp_upload_bits( basename( $filename ), null, $contents );

		$custom_logo_id = $this->_make_attachment( $upload );
		set_theme_mod( 'custom_logo', $custom_logo_id );

		// For home_url filter
		$this->frontend->links = new PLL_Frontend_Links( $this->frontend );
		$GLOBALS['wp_actions']['template_redirect'] = 1;

		$this->frontend->curlang = self::$model->get_language( 'en' );
		$doc = new DomDocument();
		$doc->loadHTML( get_custom_logo() );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//a' );
		$this->assertEquals( 'http://example.org/', $a->item( 0 )->getAttribute( 'href' ) );

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$doc = new DomDocument();
		$doc->loadHTML( get_custom_logo() );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//a' );
		$this->assertEquals( 'http://example.org/fr/', $a->item( 0 )->getAttribute( 'href' ) );

		remove_theme_mod( 'custom_logo' );
		unset( $GLOBALS['wp_actions']['template_redirect'] );
	}
}
