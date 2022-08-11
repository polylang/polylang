<?php

class Widget_Nav_Menu_Test extends PLL_UnitTestCase {
	/**
	 * @var string
	 */
	public $structure = '/%postname%/';

	/**
	 * @var PLL_REST_Request
	 */
	private $pll_rest;

	/**
	 * @param  WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	/**
	 * @global $wp_widget_factory
	 *
	 * @return void
	 */
	public function set_up() {
		// global $wp_widget_factory;

		parent::set_up();

		// We need to register the nav menu ourselves since widget globals are cleaned up in test-strings.php.
		// @see https://github.com/polylang/polylang/blob/3.2.5/tests/phpunit/tests/test-strings.php#L24-L37.
		wp_widgets_init();

		$links_model         = self::$model->get_links_model();
		$this->pll_rest      = new PLL_REST_Request( $links_model );
		$GLOBALS['polylang'] = &$this->pll_rest;
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		unset( $GLOBALS['polylang'] );
	}

	public function clean_up_global_scope() {
		global $_wp_sidebars_widgets, $wp_widget_factory, $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

		$_wp_sidebars_widgets = array();
		$wp_registered_sidebars = array();
		$wp_registered_widgets = array();
		$wp_registered_widget_controls = array();
		$wp_registered_widget_updates = array();
		$wp_widget_factory->widgets = array();

		parent::clean_up_global_scope();
	}

	/**
	 * @global $wp_version
	 */
	public function test_widget_nav_menu_rest_render() {
		global $wp_version;

		// Widgets routes are available since WordPress 5.8.
		// @see https://github.com/WordPress/wordpress-develop/blob/6.0.1/src/wp-includes/rest-api/endpoints/class-wp-rest-widget-types-controller.php#L10-L16.
		if ( version_compare( $wp_version, '5.8.0', '<' ) ) {
			$this->markTestSkipped( 'This test requires WordPress 5.8 or higher.' );
		}

		// Let's create a menu.
		$menu_id = wp_create_nav_menu( 'menu_test' );
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-type'   => 'custom',
				'menu-item-title'  => 'Language switcher',
				'menu-item-url'    => '#pll_switcher',
				'menu-item-status' => 'publish',
			)
		);
		$options = array(
			'hide_if_no_translation' => 0,
			'hide_current'           => 0,
			'force_home'             => 0,
			'show_flags'             => 0,
			'show_names'             => 1,
			'hide_if_empty'          => 0,
		); // Default values.
		update_post_meta( $item_id, '_pll_menu_item', $options );

		// Let's set the environment for the REST request.
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->pll_rest->init();
		$request = new WP_REST_Request( 'POST', '/wp/v2/widget-types/nav_menu/render' );
		$params = array(
			'nav_menu' => $menu_id,
			'pll_lang' => 'en',
		);
		$body = array(
			'instance' => array(
				'encoded' => base64_encode( serialize( $params ) ),
				'hash'    => wp_hash( serialize( $params ) ),
				'raw'     => $params,
			),
			'lang'     => 'en',
		);
		$request->set_body_params( $body );
		$response = rest_do_request( $request );

		$this->assertInstanceOf( 'PLL_Language', $this->pll_rest->curlang, 'The current language property should be set.' );
		$this->assertSame( 'en', $this->pll_rest->curlang->slug, 'The current language should be English.' );

		$data     = $response->get_data();
		$document = new DOMDocument();
		$document->loadHTML( $data['preview'] );
		$xpath = new DOMXPath( $document );

		$widget_query = '//div[@class="widget widget_nav_menu"]';
		$menu_container_query = '//div[@class="menu-menu_test-container"]';
		$menu_list_query = '//ul[@id="menu-menu_test"]';
		$this->assertSame( 1, $xpath->query( $widget_query )->length, 'The widget container is not rendered.' );
		$this->assertSame( 1, $xpath->query( $menu_container_query )->length, 'The navigation menu container is not rendered.' );
		$this->assertSame( 1, $xpath->query( $menu_list_query )->length, 'The navigation menu list is not rendered.' );

		$en_query = '//a[@lang="en-US"]';
		$fr_query = '//a[@lang="fr-FR"]';
		$en_node_list = $xpath->query( $en_query );
		$fr_node_list = $xpath->query( $fr_query );
		$this->assertSame( 1, $en_node_list->length, 'The English link is not rendered.' );
		$this->assertSame( 'English', $en_node_list->item( 0 )->nodeValue, 'The English link label is not correct.' );
		$this->assertSame( 1, $fr_node_list->length, 'The French link is not rendered.' );
		$this->assertSame( 'Français', $fr_node_list->item( 0 )->nodeValue, 'The French link label is not correct.' );
	}
}
