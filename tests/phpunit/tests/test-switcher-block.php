<?php

use WP_Syntex\Polylang\Blocks\Language_Switcher\Standard;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

class Switcher_Block_Test extends PLL_UnitTestCase {
	/**
	 * @var string
	 */
	protected $structure = '/%postname%/';

	/**
	 * @var PLL_Frontend
	 */
	protected $pll_env;

	/**
	 * @var WP_Post
	 */
	protected static $en;

	/**
	 * @var WP_Post
	 */
	protected static $fr;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		// Create posts to avoid empty languages in the tests.
		$posts    = $factory->post->create_translated( array( 'lang' => 'en' ), array( 'lang' => 'fr' ) );
		self::$en = $posts['en'];
		self::$fr = $posts['fr'];
	}

	public function set_up() {
		parent::set_up();

		global $wp_rewrite;

		// Mordern theme support for HTML5, allow to render `<nav>` tags.
		add_theme_support( 'html5', array( 'navigation-widgets' ) );

		self::$model->options['hide_default'] = 1;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		register_post_type( 'cpt', array( 'public' => true, 'label' => 'CPT', 'show_in_rest' => true ) ); // translated custom post type

		$links_model = self::$model->get_links_model();
		$links_model->init();
		$this->pll_env                   = new PLL_Frontend( $links_model );
		$GLOBALS['polylang']             = &$this->pll_env;
		$this->pll_env->links            = new PLL_Frontend_Links( $this->pll_env );
		$this->pll_env->switcher_block   = ( new Standard\Block( $this->pll_env ) )->init();
		$this->pll_env->navigation_block = ( new Navigation\Block( $this->pll_env ) )->init();

		// flush rules
		$wp_rewrite->flush_rules();

		self::require_api();

		do_action( 'init' );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$en, true );
		wp_delete_post( self::$fr, true );
		parent::wpTearDownAfterClass();
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
		$rendered = $this->render_switcher_block( $block_name );

		$this->assertNotEmpty( $rendered );

		$doc = new DomDocument();
		@$doc->loadHTML( $rendered );
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
		$posts = $this->factory()->post->create_translated_and_get(
			array(
				'lang'         => 'en',
				'post_content' => '<!-- wp:' . $block_name . ' /-->',
			),
			array(
				'lang'         => 'fr',
				'post_content' => '<!-- wp:' . $block_name . ' /-->',
			)
		);
		$en = $posts['en'];
		$fr = $posts['fr'];

		self::$model->clean_languages_cache(); // To get an exact count of posts for the languages.

		$rendered = $this->render_post_blocks( $fr );
		$doc      = new DomDocument();
		@$doc->loadHTML( $rendered );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//li/a[@lang="en-US"]' );
		$this->assertEquals( get_permalink( $en ), $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' );
		$this->assertEquals( get_permalink( $fr ), $a->item( 0 )->getAttribute( 'href' ) );
	}

	public function test_multiple_switcher_block_dropdown_in_page() {
		// Create a post with three dropdown language switcher blocks.
		$en = self::factory()->post->create_and_get(
			array(
				'post_content' => '<!-- wp:polylang/language-switcher {"dropdown":true} /--><!-- wp:polylang/language-switcher {"dropdown":true} /--><!-- wp:polylang/language-switcher {"dropdown":true} /-->',
				'lang'         => 'en',
			)
		);

		libxml_use_internal_errors( true );
		$rendered = $this->render_post_blocks( $en );
		$doc      = new DomDocument();
		$doc->loadHTML( $rendered );
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
		$rendered = $this->render_switcher_block(
			$block_name,
			array( 'className' => 'test-class' )
		);

		$doc = new DomDocument();
		@$doc->loadHTML( $rendered ); // libxml doesn't like `<nav>` tags...
		$xpath = new DOMXpath( $doc );
		$node = $xpath->query( $class_query_path );
		$classes = explode( ' ', $node->item( 0 )->getAttribute( 'class' ) );

		$this->assertNotEmpty( array_intersect( array( 'test-class', $block_class ), $classes ) );
	}

	public function test_switcher_block_list_is_wrapped_in_nav_tag() {
		$rendered = $this->render_switcher_block(
			'polylang/language-switcher',
			array( 'dropdown' => 0 )
		);
		$rendered = rtrim( ltrim( $rendered ) ); // Normalize the output.

		$processor = new WP_HTML_Tag_Processor( $rendered );

		$this->assertTrue( $processor->next_tag(), 'The rendered output should start with a tag.' );
		$this->assertSame( 'NAV', $processor->get_tag(), 'The first tag should be a nav element.' );
		$this->assertFalse( $processor->is_tag_closer(), 'The first tag should be an opening nav tag.' );

		$id = $processor->get_attribute( 'id' );
		$this->assertIsString( $id, 'The nav tag should have an id attribute.' );
		$this->assertStringStartsWith( 'pll-switcher-', $id, 'The nav id should start with pll-switcher-.' );

		$classes = array();
		foreach ( $processor->class_list() as $class_name ) {
			$classes[] = $class_name;
		}

		$this->assertEqualSets(
			array( 'pll-switcher', 'pll-layout-vertical', 'pll-alignment-none', 'wp-block-polylang-language-switcher' ),
			$classes,
			'The nav class list should match the expected values.'
		);

		$this->assertSame( 'Choose a language', $processor->get_attribute( 'aria-label' ), 'The nav tag should have the expected aria-label.' );

		$this->assertTrue(
			$processor->next_tag(
				array(
					'tag_name'    => 'NAV',
					'tag_closers' => 'visit',
				)
			),
			'The nav tag should have a closing tag.'
		);
		$this->assertTrue( $processor->is_tag_closer(), 'The closing tag should be a nav tag.' );
		$this->assertFalse( $processor->next_tag(), 'Nothing should follow the closing nav tag.' );
	}

	public function test_switcher_block_dropdown_has_aria_label() {
		$rendered = $this->render_switcher_block(
			'polylang/language-switcher',
			array( 'dropdown' => 1 )
		);

		$this->assertStringNotContainsString( '<nav>', $rendered );
		$this->assertStringContainsString(
			'<label class="screen-reader-text" for="pll-switcher-',
			$rendered
		);
		$this->assertStringContainsString(
			'">Choose a language</label>',
			$rendered
		);
	}

	public function test_custom_class_dropdown_switcher_block() {
		$rendered = $this->render_switcher_block(
			'polylang/language-switcher',
			array(
				'className' => 'test-class',
				'dropdown'  => 'true',
			)
		);

		$doc = new DomDocument();
		$doc->loadHTML( $rendered );
		$xpath = new DOMXpath( $doc );
		$node = $xpath->query( '//div[@class]' );

		$this->assertEqualSets(
			array( 'pll-switcher', 'test-class', 'pll-layout-select', 'pll-alignment-none', 'wp-block-polylang-language-switcher' ),
			explode( ' ', $node->item( 0 )->getAttribute( 'class' ) ),
			'The class list should be the same.'
		);
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
			array( 'polylang/language-switcher', '//nav[@class]', 'wp-block-polylang-language-switcher' ),
			array( 'polylang/navigation-language-switcher', '//li[@class]', 'wp-block-polylang-navigation-language-switcher' ),
		);
	}

	/**
	 * Renders a Polylang switcher block using WP_Block.
	 *
	 * @param string $block_name Block name.
	 * @param array  $attributes Block attributes.
	 * @param array  $context    Block context (for the navigation language switcher).
	 * @return string
	 */
	protected function render_switcher_block( $block_name, $attributes = array(), $context = array() ) {
		$block = new WP_Block(
			array(
				'blockName'    => $block_name,
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			$context
		);

		return $block->render();
	}

	/**
	 * Renders the blocks contained in a post. Sets the global `WP_Post` and `WP_Query` objects to mimic a frontend request.
	 *
	 * @global WP_Post  $post     The global post object.
	 * @global WP_Query $wp_query The global WP_Query object.
	 *
	 * @param WP_Post $post_to_render The post to render.
	 * @return string
	 */
	protected function render_post_blocks( WP_Post $post_to_render ) {
		global $post, $wp_query;

		$post                        = $post_to_render;
		$wp_query->queried_object    = $post_to_render;
		$wp_query->queried_object_id = $post_to_render->ID;
		$wp_query->is_single         = true;

		return do_blocks( $post_to_render->post_content );
	}
}
