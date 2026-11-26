<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Options_Nav_Menus_Test extends TestCase {

	/**
	 * Registered nav menus before this test registered extras.
	 *
	 * @var array
	 */
	private $registered_nav_menus = array();

	public function set_up() {
		parent::set_up();

		$this->registered_nav_menus = isset( $GLOBALS['_wp_registered_nav_menus'] ) ? $GLOBALS['_wp_registered_nav_menus'] : array();

		if ( empty( $this->registered_nav_menus ) ) {
			register_nav_menu( 'primary', 'Primary menu' );
			register_nav_menu( 'secondary', 'Secondary menu' );
		}
	}

	public function tear_down() {
		unset( $GLOBALS['polylang'] );

		remove_theme_mod( 'nav_menu_locations' );

		if ( empty( $this->registered_nav_menus ) ) {
			unset( $GLOBALS['_wp_registered_nav_menus'] ); // `unset` after `global` only drops the local alias.
		} else {
			$GLOBALS['_wp_registered_nav_menus'] = $this->registered_nav_menus;
		}

		$this->pll_admin->options->reset( 'nav_menus' );

		parent::tear_down();
	}

	public function test_nav_menus_omitted_when_current_theme_empty() {
		$this->pll_admin->options['nav_menus'] = array(
			'other-theme' => array(
				'primary' => array(
					'en' => 1,
				),
			),
		);

		$fields = $this->get_pll_options_fields();

		$this->assertArrayNotHasKey( 'nav_menus', $fields, 'nav_menus should be omitted when the current theme has no locations.' );
	}

	public function test_nav_menus_unused_location() {
		$theme            = get_stylesheet();
		$locations        = array_keys( get_registered_nav_menus() );
		$primary_location = reset( $locations );

		$this->pll_admin->options['nav_menus'] = array(
			$theme => array(
				$primary_location => array(),
			),
		);

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field(
			$fields,
			'nav_menus',
			'Nav_menus',
			sprintf( '%1$s: %2$s', $primary_location, 'Not used' )
		);
	}

	public function test_nav_menus_formats_language_map() {
		$theme            = get_stylesheet();
		$locations        = array_keys( get_registered_nav_menus() );
		$primary_location = reset( $locations );
		$menu_en          = wp_create_nav_menu( 'menu_en_site_health' );
		$menu_fr          = wp_create_nav_menu( 'menu_fr_site_health' );

		$lang_map = array(
			'en' => (int) $menu_en,
			'fr' => (int) $menu_fr,
		);

		$this->pll_admin->options['nav_menus'] = array(
			$theme => array(
				$primary_location => $lang_map,
			),
		);

		$expected = sprintf(
			'%1$s: [en => %2$d, fr => %3$d]',
			$primary_location,
			$lang_map['en'],
			$lang_map['fr']
		);

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'nav_menus', 'Nav_menus', $expected );
	}

	public function test_nav_menus_combines_locations() {
		$theme     = get_stylesheet();
		$locations = array_keys( get_registered_nav_menus() );

		if ( count( $locations ) < 2 ) {
			register_nav_menu( 'footer', 'Footer menu' );
			$locations = array_keys( get_registered_nav_menus() );
		}

		$primary_location   = $locations[0];
		$secondary_location = $locations[1];
		$menu_en            = wp_create_nav_menu( 'menu_en_combo' );

		$lang_map = array( 'en' => (int) $menu_en );

		$this->pll_admin->options['nav_menus'] = array(
			$theme => array(
				$primary_location   => array(),
				$secondary_location => $lang_map,
			),
		);

		$expected = sprintf(
			'%1$s: %2$s | %3$s: [en => %4$d]',
			$primary_location,
			'Not used',
			$secondary_location,
			$lang_map['en']
		);

		$fields = $this->get_pll_options_fields();

		$this->assert_option_field( $fields, 'nav_menus', 'Nav_menus', $expected );
	}
}
