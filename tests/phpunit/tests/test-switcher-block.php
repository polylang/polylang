<?php

use WP_Syntex\Polylang\Blocks\Language_Switcher\Standard;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

class Switcher_Block_Test extends PLL_UnitTestCase {
	protected static $administrator;
	protected $server;
	protected $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );

		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = array();
		self::require_api();
	}

	public function set_up() {
		parent::set_up();

		global $wp_rewrite, $wp_rest_server;
		$this->server   = new Spy_REST_Server();
		$wp_rest_server = $this->server;

		self::$model->options['hide_default'] = 1;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		register_post_type( 'cpt', array( 'public' => true, 'label' => 'CPT', 'show_in_rest' => true ) ); // translated custom post type

		$links_model = self::$model->get_links_model();
		$links_model->init();
		$pll_env                   = new PLL_REST_Request( $links_model );
		$GLOBALS['polylang']       = &$pll_env;
		$pll_env->links            = new PLL_Admin_Links( $pll_env );
		$pll_env->switcher_block   = ( new Standard\Block( $pll_env ) )->init();
		$pll_env->navigation_block = ( new Navigation\Block( $pll_env ) )->init();

		// flush rules
		$wp_rewrite->flush_rules();

		do_action( 'init' );
		do_action( 'rest_api_init' );
	}

	public function tear_down() {
		_unregister_post_type( 'cpt' );
		WP_Block_Type_Registry::get_instance()->unregister( 'polylang/language-switcher' );
		WP_Block_Type_Registry::get_instance()->unregister( 'polylang/navigation-language-switcher' );

		parent::tear_down();
	}

	/**
	 * @dataProvider polylang_block_name_provider
	 *
	 * @param string $block_name The name of the tested Polylang block.
	 * @return void
	 */
	public function test_switcher_block( $block_name ) {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/' . $block_name );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'lang', 'fr' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$switcher = $response->get_data();

		$this->assertNotEmpty( $switcher['rendered'] );

		$doc = new DomDocument();
		@$doc->loadHTML( $switcher['rendered'] );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//li/a[@lang="en-US"]' );
		$this->assertEquals( pll_home_url( 'en' ), $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' );
		$this->assertEquals( pll_home_url( 'fr' ), $a->item( 0 )->getAttribute( 'href' ) );
	}

	/**
	 * Bug #892 fixed in 3.0.
	 *
	 * @dataProvider polylang_block_name_provider
	 *
	 * @param string $block_name The name of the tested Polylang block.
	 * @return void
	 */
	public function test_switcher_block_in_get_posts( $block_name ) {
		$en = self::factory()->post->create( array( 'post_content' => '<!-- wp:' . $block_name . ' /-->' ) );
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create( array( 'post_content' => '<!-- wp:' . $block_name . ' /-->' ) );
		self::$model->post->set_language( $fr, 'fr' );

		self::$model->post->save_translations( $en, compact( 'fr' ) );

		self::$model->clean_languages_cache(); // To get an exact count of posts for the languages.

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'lang', 'fr' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$post = reset( $data );
		$doc = new DomDocument();
		@$doc->loadHTML( $post['content']['rendered'] );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//li/a[@lang="en-US"]' );
		$this->assertEquals( get_permalink( $en ), $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' );
		$this->assertEquals( get_permalink( $fr ), $a->item( 0 )->getAttribute( 'href' ) );
	}

	public function test_multiple_switcher_block_dropdown_in_page() {
		// Create a post with three dropdown language switcher blocks.
		$en = self::factory()->post->create( array( 'post_content' => '<!-- wp:polylang/language-switcher {"dropdown":true} /--><!-- wp:polylang/language-switcher {"dropdown":true} /--><!-- wp:polylang/language-switcher {"dropdown":true} /-->' ) );
		self::$model->post->set_language( $en, 'en' );

		libxml_use_internal_errors( true );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $en );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$en_post = $response->get_data();
		$this->assertNotEmpty( $en_post['content'] );
		$doc = new DomDocument();
		$doc->loadHTML( $en_post['content']['rendered'] );
		$xpath = new DOMXpath( $doc );
		libxml_clear_errors();

		$node_list = $xpath->query( '//div/select[@id]/@id' );

		$dropdown_ids = array();
		foreach ( $node_list as $node ) {
			$dropdown_ids[] = $node->value;
		}
		// Asserts that each id attribute is unique.
		$this->assertEquals( count( $dropdown_ids ), count( array_unique( $dropdown_ids ) ) );
	}

	/**
	 * @dataProvider polylang_block_name_provider
	 *
	 * @param string $block_name       The name of the tested Polylang block.
	 * @param string $class_query_path The dom Xpath to get the classes.
	 * @param string $block_class      The class name of the block wrapper.
	 * @return void
	 */
	public function test_custom_class_list_switcher_block( $block_name, $class_query_path, $block_class ) {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/' . $block_name );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'lang', 'fr' );
		$request->set_param( 'attributes', array( 'className' => 'test-class' ) );
		$response = $this->server->dispatch( $request );
		$switcher = $response->get_data();

		$doc = new DomDocument();
		$doc->loadHTML( $switcher['rendered'] );
		$xpath = new DOMXpath( $doc );
		$node = $xpath->query( $class_query_path );
		$classes = explode( ' ', $node->item( 0 )->getAttribute( 'class' ) );

		$this->assertNotEmpty( array_intersect( array( 'test-class', $block_class ), $classes ) );
	}

	public function test_switcher_block_list_is_wrapped_in_nav_tag() {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/polylang/language-switcher' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'attributes', array( 'dropdown' => 0 ) );
		$response = $this->server->dispatch( $request );
		$switcher = $response->get_data();

		$this->assertStringStartsWith( '<nav role="navigation" aria-label="Choose a language">', $switcher['rendered'] );
		$this->assertStringEndsWith( '</nav>', $switcher['rendered'] );
	}

	public function test_switcher_block_dropdown_has_aria_label() {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/polylang/language-switcher' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'attributes', array( 'dropdown' => 1 ) );
		$response = $this->server->dispatch( $request );
		$switcher = $response->get_data();

		$this->assertStringNotContainsString( '<nav>', $switcher['rendered'] );
		$this->assertStringContainsString(
			'<label class="screen-reader-text" for="lang_choice_',
			$switcher['rendered']
		);
		$this->assertStringContainsString(
			'">Choose a language</label>',
			$switcher['rendered']
		);
	}

	public function test_custom_class_dropdown_switcher_block() {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/polylang/language-switcher' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'lang', 'fr' );
		$request->set_param(
			'attributes',
			array(
				'className' => 'test-class',
				'dropdown'  => 'true',
			)
		);
		$response = $this->server->dispatch( $request );
		$switcher = $response->get_data();

		$doc = new DomDocument();
		$doc->loadHTML( $switcher['rendered'] );
		$xpath = new DOMXpath( $doc );
		$node = $xpath->query( '//div[@class]' );

		$this->assertEquals( 'test-class wp-block-polylang-language-switcher', $node->item( 0 )->getAttribute( 'class' ) );
	}

	/**
	 * Provides data about the Polylang's blocks.
	 *
	 * @return array (
	 *      $block_name               The block name as it is registered.
	 *      $class_query_path         The dom query path to get the classes.
	 *      $block_wrapper_class_name The wrapper class name of the block.
	 * )
	 */
	public function polylang_block_name_provider() {
		return array(
			array( 'polylang/language-switcher', '//ul[@class]', 'wp-block-polylang-language-switcher' ),
			array( 'polylang/navigation-language-switcher', '//li[@class]', 'wp-block-polylang-navigation-language-switcher' ),
		);
	}
}
