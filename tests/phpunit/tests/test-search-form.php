<?php

class Search_Form_Test extends PLL_UnitTestCase {
	public $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function set_up() {
		parent::set_up();

		global $wp_rewrite;

		self::$model->options['hide_default'] = 1;

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
		$wp_rewrite->set_permalink_structure( $this->structure );

		self::$model->post->register_taxonomy(); // needs this for 'lang' query var
		create_initial_taxonomies();

		$this->links_model = self::$model->get_links_model();
		$this->links_model->init();

		// flush rules
		$wp_rewrite->flush_rules();

		$this->frontend = new PLL_Frontend( $this->links_model );
		$this->frontend->init();
	}

	function test_admin_bar_search_form() {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		$this->frontend->curlang = self::$model->get_language( 'fr' );

		$admin_bar = new WP_Admin_Bar();
		$admin_bar->add_menus();
		do_action_ref_array( 'admin_bar_menu', array( &$admin_bar ) ); // ndeed add menus to the admin bar
		$node = $admin_bar->get_node( 'search' );

		$this->assertStringContainsString( home_url( '/fr/' ), $node->title );
	}

	function test_get_search_form() {
		global $wp_rewrite;

		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$form = get_search_form( false ); // don't echo

		$this->assertStringContainsString( 'action="' . home_url( '/fr/' ) . '"', $form );

		$wp_rewrite->set_permalink_structure( '' );
		$this->frontend->links_model = self::$model->get_links_model();

		$form = get_search_form( false );
		$this->assertStringContainsString( '<input type="hidden" name="lang" value="fr" />', $form );
	}

	/**
	 * Issue #829
	 */
	function test_get_search_form_with_wrong_inital_url() {
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$form = '<form role="search" method="get" class="search-form" action="http://example.org/fr/accueil/">
				<label>
					<span class="screen-reader-text">Search for:</span>
					<input type="search" class="search-field" placeholder="Search &hellip;" value="test" name="s" />
				</label>
				<input type="submit" class="search-submit" value="Search" />
			</form>';
		$form = apply_filters( 'get_search_form', $form );
		$this->assertContains( 'action="' . home_url( '/fr/' ) . '"', $form );
	}

	/**
	 * PR #780
	 */
	function test_search_form_is_not_emptied() {
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$form = '<form action="http://example.org" method="get"><input type="submit" value="Search" /></form>';
		$form = apply_filters( 'get_search_form', $form );
		$this->assertEquals( '<form action="http://example.org/fr/" method="get"><input type="submit" value="Search" /></form>', $form );
	}

	/**
	 * PR #780
	 */
	function test_search_form_with_simple_quotes_in_html() {
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$form = "<form action='http://example.org' method='get'><input type='submit' value='Search' /></form>";
		$form = apply_filters( 'get_search_form', $form );
		$this->assertEquals( "<form action=\"http://example.org/fr/\" method='get'><input type='submit' value='Search' /></form>", $form );
	}

	/**
	 * PR #780
	 */
	function test_search_form_with_no_quotes_in_html() {
		$this->frontend->curlang = self::$model->get_language( 'fr' );
		$form = '<form action=http://example.org method=get><input type=submit value=Search /></form>';
		$form = apply_filters( 'get_search_form', $form );
		$this->assertEquals( '<form action="http://example.org/fr/" method=get><input type=submit value=Search /></form>', $form );
	}
}
