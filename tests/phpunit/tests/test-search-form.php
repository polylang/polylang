<?php

class Search_Form_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		require_once PLL_INC . '/api.php';
		$GLOBALS['polylang'] = &self::$polylang;
	}

	function setUp() {
		parent::setUp();

		global $wp_rewrite;

		self::$polylang->options['hide_default'] = 1;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$polylang->model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();

		self::$polylang->links_model = self::$polylang->model->get_links_model();
		self::$polylang->links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();
	}

	function test_admin_bar_search_form() {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$admin_bar = new WP_Admin_Bar;
		$admin_bar->add_menus();
		do_action_ref_array( 'admin_bar_menu', array( &$admin_bar ) ); // ndeed add menus to the admin bar
		$node = $admin_bar->get_node( 'search' );

		$this->assertContains( home_url( '/fr/' ), $node->title );
	}

	function test_get_search_form() {
		global $wp_rewrite;

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );
		$form = get_search_form( false ); // don't echo

		$this->assertContains( home_url( '/fr/' ), $form );

		$wp_rewrite->set_permalink_structure( '' );
		self::$polylang->links_model = self::$polylang->model->get_links_model();

		$form = get_search_form( false );
		$this->assertContains( '<input type="hidden" name="lang" value="fr" />', $form );
	}
}
