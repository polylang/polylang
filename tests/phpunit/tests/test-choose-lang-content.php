<?php

class Choose_Lang_Content_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once POLYLANG_DIR . '/include/api.php';
	}

	public function set_up() {
		parent::set_up();

		global $wp_rewrite;

		$options = self::create_options(
			array(
				'hide_default' => 1,
				'force_lang'   => 0,
				'browser'      => 0,
				'default_lang' => 'en',
			)
		);

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		create_initial_taxonomies();

		$model = new PLL_Model( $options );
		$links_model = $model->get_links_model();
		$links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		$this->frontend = new PLL_Frontend( $links_model );
	}

	/**
	 * Overrides WP_UnitTestCase::go_to().
	 *
	 * @param string $url The URL for the request.
	 */
	public function go_to( $url ) {
		// copy paste of WP_UnitTestCase::go_to
		$_GET = $_POST = array();
		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = $parts['path'] ?? '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// parse the url query vars into $_GET
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset( $_SERVER['PATH_INFO'] );

		$this->flush_cache();
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );

		// insert Polylang specificity
		unset( $GLOBALS['wp_actions']['pll_language_defined'] );
		unset( $this->frontend->curlang );
		$this->frontend->init();

		// restart copy paste of WP_UnitTestCase::go_to
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();
		/*
		 * Instead of using `_cleanup_query_vars()` to cleanup and repopulate query vars, trigger `setup_theme`.
		 * See `PLL_Translated_Post::add_language_taxonomy_query_var()`.
		 */
		do_action( 'setup_theme' );

		$GLOBALS['wp']->main( $parts['query'] );
	}

	public function test_home_latest_posts() {
		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );
	}

	public function test_home_latest_posts_with_hide_default() {
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$this->go_to( home_url( '/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_single_post() {
		$en = self::factory()->post->create( array( 'post_title' => 'test' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/essai/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );

		$this->go_to( home_url( '/test/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_page() {
		$en = self::factory()->post->create( array( 'post_title' => 'test', 'post_type' => 'page' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_title' => 'essai', 'post_type' => 'page' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/essai/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );

		$this->go_to( home_url( '/test/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_category_default_lang() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$this->go_to( home_url( '/category/test/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_category_non_default_lang() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/category/essai/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );
	}

	public function test_post_tag_default_lang() {
		$en = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$this->go_to( home_url( '/tag/test/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_post_tag_non_default_lang() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'post_tag', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/tag/essai/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );
	}

	public function test_archive() {
		$en = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '/fr/2007/' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );

		$this->go_to( home_url( '/2007/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	/**
	 * @see https://github.com/polylang/polylang-pro/issues/2294
	 */
	public function test_archive_not_initialized() {
		$en = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $fr, 'fr' );

		// Remove the language taxonomy.
		unregister_taxonomy( 'language' );
		$this->remove_hooks( 'setup_theme', array( \PLL_Translated_Post::class, 'add_language_taxonomy_query_var' ) );

		// Recreate the language taxonomy, in the same context as it would be created normally (understand: `$wp` is not set yet).
		unset( $GLOBALS['wp'] );
		$this->frontend->model->post = new \PLL_Translated_Post( $this->frontend->model );

		// Remove the hook that adds the query var.
		remove_action( 'setup_theme', array( $this->frontend->model->post, 'add_language_taxonomy_query_var' ) );

		// Watch it fail: FR is asked but EN is the current language.
		$this->go_to( home_url( '/fr/2007/' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	public function test_archive_with_default_permalinks() {
		$GLOBALS['wp_rewrite']->set_permalink_structure( '' );

		$en = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->term->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_date' => '2007-09-04 00:00:00' ) );
		self::$model->post->set_language( $fr, 'fr' );

		$this->go_to( home_url( '?year=2007&lang=fr' ) );
		$this->assertEquals( 'fr', $this->frontend->curlang->slug );

		$this->go_to( home_url( '?year=2007' ) );
		$this->assertEquals( 'en', $this->frontend->curlang->slug );
	}

	/**
	 * @see https://github.com/polylang/polylang/issues/356
	 */
	public function test_update_stylesheet_text_direction_when_language_is_set_by_content() {
		self::create_language( 'ar' );
		$this->frontend->init();

		// Usually happens on 'setup_theme'
		wp_style_add_data( 'default', 'key', 'value' );

		// Happens on 'wp'
		$set_language = new ReflectionMethod( PLL_Choose_Lang::class, 'set_language' );
		$set_language->setAccessible( true );
		$set_language->invokeArgs( $this->frontend->choose_lang, array( $this->frontend->model->get_language( 'ar' ) ) );

		$this->assertEquals( 'rtl', wp_styles()->text_direction );
	}

	private function remove_hooks( $hook, $callback_to_remove ): void {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) ) {
			return;
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $prio => $callbacks ) {
			foreach ( $callbacks as $uniqid => $callback ) {
				if ( ! is_array( $callback['function'] ) ) {
					if ( $callback['function'] === $callback_to_remove ) {
						unset( $wp_filter[ $hook ]->callbacks[ $prio ][ $uniqid ] );
					}
					continue;
				}

				if ( ! is_array( $callback_to_remove ) || $callback['function'][1] !== $callback_to_remove[1] ) {
					continue;
				}

				if ( is_object( $callback['function'][0] ) && get_class( $callback['function'][0] ) === $callback_to_remove[0] ) {
					unset( $wp_filter[ $hook ]->callbacks[ $prio ][ $uniqid ] );
					continue;
				}

				if ( is_string( $callback['function'][0] ) && $callback['function'][0] === $callback_to_remove[0] ) {
					unset( $wp_filter[ $hook ]->callbacks[ $prio ][ $uniqid ] );
					continue;
				}
			}

			if ( empty( $wp_filter[ $hook ]->callbacks[ $prio ] ) ) {
				unset( $wp_filter[ $hook ]->callbacks[ $prio ] );
			}
		}
	}
}
