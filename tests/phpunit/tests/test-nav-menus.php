<?php

class Nav_Menus_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function setUp() {
		parent::setUp();

		$registered_nav_menus = get_registered_nav_menus();

		if ( empty( $registered_nav_menus ) ) {
			register_nav_menu( 'primary', 'Primary menu' );
		}
	}

	function test_nav_menu_locations() {
		// get the primary location of the current theme
		$locations = array_keys( get_registered_nav_menus() );
		$primary_location = reset( $locations );

		// create 3 menus
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Hello World' ) );
		$item_id = wp_update_nav_menu_item( $menu_en, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'post',
			'menu-item-object-id' => $post_id,
			'menu-item-title'     => 'Hello World',
			'menu-item-status'    => 'publish',
		) );

		$menu_fr = wp_create_nav_menu( 'menu_fr' );
		$post_id = $this->factory->post->create( array( 'post_title' => 'Bonjour' ) );
		$item_id = wp_update_nav_menu_item( $menu_fr, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'post',
			'menu-item-object-id' => $post_id,
			'menu-item-title'     => 'Bonjour',
			'menu-item-status'    => 'publish',
		) );

		$menu_0 = wp_create_nav_menu( 'menu_0' );
		$post_id = $this->factory->post->create( array( 'post_title' => 'No language' ) );
		$item_id = wp_update_nav_menu_item( $menu_0, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'post',
			'menu-item-object-id' => $post_id,
			'menu-item-title'     => 'No language',
			'menu-item-status'    => 'publish',
		) );

		// assign our menus to locations
		$nav_menu = new PLL_Admin_Nav_Menu( self::$polylang );
		$nav_menu->create_nav_menu_locations();

		$locations = array_keys( get_registered_nav_menus() );
		$nav_menu_locations = array(
			$locations[0] => $menu_en,
			$locations[1] => $menu_fr,
		);

		set_theme_mod( 'nav_menu_locations', $nav_menu_locations );
		$nav_menu->update_nav_menu_locations( $nav_menu_locations ); // our filter update_nav_menu_locations does not run due to security checks

		// test nav menus on frontend when using theme locations
		self::$polylang->nav_menu = new PLL_Frontend_Nav_Menu( self::$polylang );

		$args = array( 'theme_location' => $primary_location, 'echo' => false );
		$this->assertContains( 'Hello World', wp_nav_menu( $args ) );

		self::$polylang->curlang = self::$polylang->model->get_language( 'fr' );

		$args = array( 'theme_location' => $primary_location, 'echo' => false );
		$this->assertContains( 'Bonjour', wp_nav_menu( $args ) );

		// test with hardcoded menu
		$args = array( 'menu' => 'menu_en', 'echo' => false );
		$this->assertContains( 'Bonjour', wp_nav_menu( $args ) );

		// test with wrong hardcoded menu
		$args = array( 'menu' => $primary_location, 'echo' => false ); // this is what we often see in themes
		$this->assertContains( 'Bonjour', wp_nav_menu( $args ) );

		// test without theme location or hardcoded menu
		$args = array( 'echo' => false );
		$this->assertContains( 'Bonjour', wp_nav_menu( $args ) );

		// test with untranslated menu hardcoded
		$args = array( 'menu' => 'menu_0', 'echo' => false );
		$this->assertContains( 'No language', wp_nav_menu( $args ) );
	}

	function test_delete_nav_menu() {
		$theme = get_option( 'stylesheet' );

		// get the primary location of the current theme
		$locations = array_keys( get_registered_nav_menus() );
		$primary_location = reset( $locations );

		// create 2 menus
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$menu_fr = wp_create_nav_menu( 'menu_fr' );

		// assign our menus to locations
		$nav_menu = new PLL_Admin_Nav_Menu( self::$polylang );
		$nav_menu->create_nav_menu_locations();

		$locations = array_keys( get_registered_nav_menus() );
		$nav_menu_locations = array(
			$locations[0] => $menu_en,
			$locations[1] => $menu_fr,
		);

		set_theme_mod( 'nav_menu_locations', $nav_menu_locations );
		$nav_menu->update_nav_menu_locations( $nav_menu_locations ); // our filter update_nav_menu_locations does not run due to security checks

		$options = get_option( 'polylang' );
		$this->assertEquals( array( 'en' => $menu_en, 'fr' => $menu_fr ), $options['nav_menus'][ $theme ][ $primary_location ] );

		// setup filters
		$nav_menu->admin_init();

		// delete a nav_menu
		wp_delete_nav_menu( $menu_en );

		$options = get_option( 'polylang' );
		$this->assertEquals( array( 'fr' => $menu_fr ), $options['nav_menus'][ $theme ][ $primary_location ] );
	}

	function test_auto_add_pages_to_menu() {
		// create 2 menus
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$menu_fr = wp_create_nav_menu( 'menu_fr' );

		// add our 2 menus to auto added pages
		update_option( 'nav_menu_options', array( 'auto_add' => array( $menu_en, $menu_fr ) ) );

		$locations = array_keys( get_registered_nav_menus() );
		$nav_menu_locations = array(
			$locations[0] => $menu_en,
			$locations[1] => $menu_fr,
		);

		// assign our menus to locations
		$nav_menu = new PLL_Admin_Nav_Menu( self::$polylang );
		$nav_menu->create_nav_menu_locations();

		set_theme_mod( 'nav_menu_locations', $nav_menu_locations );
		$nav_menu->update_nav_menu_locations( $nav_menu_locations ); // our filter update_nav_menu_locations does not run due to security checks

		// add the filter we want to test
		add_action( 'transition_post_status', array( &$nav_menu, 'auto_add_pages_to_menu' ), 5, 3 ); // before _wp_auto_add_pages_to_menu

		// create a draft as we want to set the language *before* publication
		$post_id = $this->factory->post->create( array( 'post_type' => 'page', 'post_status' => 'draft' ) );
		self::$polylang->model->post->set_language( $post_id, 'fr' );
		wp_publish_post( $post_id );

		$this->assertEquals( array(), wp_get_nav_menu_items( $menu_en ) ); // no menu item in menu_en

		$menu_items = wp_get_nav_menu_items( $menu_fr );
		$this->assertEquals( $post_id, reset( $menu_items )->object_id ); // our page menu item in menu_fr
	}

	function test_combine_location() {
		$nav_menu = new PLL_Nav_Menu( self::$polylang );

		$this->assertEquals( 'primary', $nav_menu->combine_location( 'primary', self::$polylang->model->get_language( 'en' ) ) ); // default language
		$this->assertEquals( 'primary___fr', $nav_menu->combine_location( 'primary', self::$polylang->model->get_language( 'fr' ) ) );
		$this->assertEquals( 'primary___fr', $nav_menu->combine_location( 'primary___fr', self::$polylang->model->get_language( 'fr' ) ) );
	}

	function test_explode_location() {
		$nav_menu = new PLL_Nav_Menu( self::$polylang );

		$this->assertEquals( array( 'location' => 'primary', 'lang' => 'en' ), $nav_menu->explode_location( 'primary' ) );
		$this->assertEquals( array( 'location' => 'primary', 'lang' => 'fr' ), $nav_menu->explode_location( 'primary___fr' ) );
	}

	function setup_nav_menus( $options ) {
		// create posts
		$en = $this->factory->post->create( array( 'post_title' => 'test' ) );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create( array( 'post_title' => 'essai' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'en', 'fr' ) );

		// create 1 menu
		$menu_en = wp_create_nav_menu( 'menu_en' );
		$item_id = wp_update_nav_menu_item( $menu_en, 0, array(
			'menu-item-type'   => 'custom',
			'menu-item-title'  => 'Language switcher',
			'menu-item-url'    => '#pll_switcher',
			'menu-item-status' => 'publish',
		) );

		$options['hide_if_empty'] = 0; // FIXME for some reason the languages counts are 0 even if I manually call clean_languages_cache()
		update_post_meta( $item_id, '_pll_menu_item', $options );

		// get the primary location of the current theme
		$locations = array_keys( get_registered_nav_menus() );
		$primary_location = reset( $locations );

		// assign our menus to locations
		$nav_menu = new PLL_Admin_Nav_Menu( self::$polylang );
		$nav_menu->create_nav_menu_locations();

		$locations = array_keys( get_registered_nav_menus() );
		$nav_menu_locations = array(
			$locations[0] => $menu_en,
		);

		set_theme_mod( 'nav_menu_locations', $nav_menu_locations );
		$nav_menu->update_nav_menu_locations( $nav_menu_locations ); // our filter update_nav_menu_locations does not run due to security checks

		return $primary_location;
	}

	function test_nav_menu_language_switcher() {
		$options = array( 'hide_if_no_translation' => 0, 'hide_current' => 0,'force_home' => 0 ,'show_flags' => 0 ,'show_names' => 1 ); // default values
		$primary_location = $this->setup_nav_menus( $options );

		// test nav menus on frontend when using theme locations
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		self::$polylang->links = new PLL_Frontend_Links( self::$polylang );
		self::$polylang->nav_menu = new PLL_Frontend_Nav_Menu( self::$polylang );

		require_once PLL_INC . '/api.php'; // usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang; // FIXME we still use PLL() in PLL_Frontend_Nav_Menu

		$args = array( 'theme_location' => $primary_location, 'echo' => false );
		$this->assertContains( 'Français', wp_nav_menu( $args ) );
		$this->assertContains( 'English', wp_nav_menu( $args ) );

		unset( $GLOBALS['polylang'] );
	}

	function test_nav_menu_language_switcher_as_dropdown() {
		$options = array( 'hide_if_no_translation' => 0, 'hide_current' => 1,'force_home' => 0 ,'show_flags' => 0 ,'show_names' => 1, 'dropdown' => 1 );
		$primary_location = $this->setup_nav_menus( $options );

		// test nav menus on frontend when using theme locations
		self::$polylang->curlang = self::$polylang->model->get_language( 'en' );
		self::$polylang->links = new PLL_Frontend_Links( self::$polylang );
		self::$polylang->nav_menu = new PLL_Frontend_Nav_Menu( self::$polylang );

		require_once PLL_INC . '/api.php'; // usually loaded only if an instance of Polylang exists
		$GLOBALS['polylang'] = self::$polylang; // FIXME we still use PLL() in PLL_Frontend_Nav_Menu

		$args = array( 'theme_location' => $primary_location, 'echo' => false );
		$xml = '<root>' . wp_nav_menu( $args ) . '</root>'; // add a root xml tag to get a valid xml doc
		$xml = preg_replace( '#<svg(.+)</svg>#', '', $xml ); // Remove SVG Added by Twenty Seventeen to avoid an error in simplexml_load_string()
		$xml = simplexml_load_string( $xml ); // add a root xml tag to get a valid xml doc

		$this->assertNotEmpty( $xml->xpath( '//div/ul/li/a[.="English"]' ) );
		$this->assertEmpty( $xml->xpath( '//div/ul/li/ul/li/a[.="English"]' ) ); // current language is hidden
		$this->assertNotEmpty( $xml->xpath( '//div/ul/li/ul/li/a[.="Français"]' ) );

		unset( $GLOBALS['polylang'] );
	}
}
