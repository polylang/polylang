<?php

class Menu_Location_Test extends PLL_UnitTestCase {

	public static function wpSetUpBeforeClass( $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	public function setUp() {
		parent::setUp();

		$options = PLL_Install::get_default_options();
		$model = new PLL_Model( $options );
		$links_model = new PLL_Links_Default( $model );
		$pll = new PLL_Admin( $links_model );
		$this->nav_menu = new PLL_Admin_Nav_Menu( $pll );

		set_current_screen( 'nav-menus' );
	}

	/**
	 * @see https://github.com/polylang/polylang-pro/issues/921
	 */
	public function sample_data() {
		$request = 'GET
	http://localhost/wordpress-5/wp-admin/nav-menus.php?action=edit&menu=0';

		$menu_theme_locations = <<<HTML
<fieldset class="menu-settings-group menu-theme-locations">
	<legend class="menu-settings-group-name howto">Display location</legend>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" name="menu-locations[primary]" id="locations-primary" value="0">
	<label for="locations-primary">Primary menu English</label>
			<span class="theme-location-set">
	(Currently set to: Navigation principale)													</span>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[primary___fr]" id="locations-primary___fr" value="0">
	<label for="locations-primary___fr">Primary menu Français</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[primary___de]" id="locations-primary___de" value="0">
	<label for="locations-primary___de">Primary menu Deutsch</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[primary___fr-be]" id="locations-primary___fr-be" value="0">
	<label for="locations-primary___fr-be">Primary menu Français</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[primary___zh-hant-hk]" id="locations-primary___zh-hant-hk" value="0">
	<label for="locations-primary___zh-hant-hk">Primary menu Chinese (Hong-Kong) Traditionnal Script</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[primary___bo]" id="locations-primary___bo" value="0">
	<label for="locations-primary___bo">Primary menu བོད་ཡིག</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer]" id="locations-footer" value="0">
	<label for="locations-footer">Secondary menu English</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer___fr]" id="locations-footer___fr" value="0">
	<label for="locations-footer___fr">Secondary menu Français</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer___de]" id="locations-footer___de" value="0">
	<label for="locations-footer___de">Secondary menu Deutsch</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer___fr-be]" id="locations-footer___fr-be" value="0">
	<label for="locations-footer___fr-be">Secondary menu Français</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer___zh-hant-hk]" id="locations-footer___zh-hant-hk" value="0">
	<label for="locations-footer___zh-hant-hk">Secondary menu Chinese (Hong-Kong) Traditionnal Script</label>
	</div>
	<div class="menu-settings-input checkbox-input">
	<input type="checkbox" checked="checked" name="menu-locations[footer___bo]" id="locations-footer___bo" value="0">
	<label for="locations-footer___bo">Secondary menu བོད་ཡིག</label>
	</div>
</fieldset>
HTML;

		$expected_menu_locations = array(
			'primary' => 117,
		);

		$actual_menu_locations = array(
			'primary' => 117,
			'primary___fr' => 0,
			'primary___de' => 0,
			'primary___fr-be' => 0,
			'primary___zh-hant-hk' => 0,
			'primary___bo' => 0,
			'footer' => 0,
			'footer___fr' => 0,
			'footer___de' => 0,
			'footer___fr-be' => 0,
			'footer___zh-hant-hk' => 0,
			'footer___bo' => 0,
		);

	}

	public function test_menu_locations_are_not_unset() {
		global $_wp_registered_nav_menus;

		$_wp_registered_nav_menus = array(
			'primary' => 'Primary Navigation English',
			'primary___fr' => 'Primary Navigation Français',
			'primary___de' => 'Primary Navigation Deutsch',
		);
		$original_menu_locations = array(
			'primary' => 12,
			'primary___fr' => 13,
			'primary___de' => 14,
		);
		set_theme_mod( 'nav_menu_locations', $original_menu_locations );
		$nav_menu_selected_id = 12;

		// Copied from {@see https://github.com/WordPress/wordpress-develop/blob/2382765afa36e10bf3c74420024ad4e85763a47c/src/wp-admin/nav-menus.php#L47-L48}.
		$locations      = get_registered_nav_menus();
		$menu_locations = get_nav_menu_locations();

		// copied from {@see https://github.com/WordPress/wordpress-develop/blob/2382765afa36e10bf3c74420024ad4e85763a47c/src/wp-admin/nav-menus.php#L389-L395}.
		foreach ( $locations as $location => $description ) {
			if ( ( empty( $_POST['menu-locations'] ) || empty( $_POST['menu-locations'][ $location ] ) )
				 && isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] === $nav_menu_selected_id
			) {
				if ( $nav_menu_selected_id !== $menu_locations[ $location ] ) {
					$this->fail( 'Translated menu location should not be unchecked.' );
				}
			}
		}
	}

	public function test_translated_menu_should_not_be_unset_when_default_language_menu_is_unset() {
		$original_menu_locations = array(
			'primary' => 12,
			'primary___fr' => 13,
			'primary___de' => 14,
		);
		update_option( 'theme_mod_nav_menu_locations', $original_menu_locations );

		$new_menu_locations = array(
			'primary__fr' => 13,
			'primary__de' => 14,
		);
		set_theme_mod( 'nav_menu_locations', $new_menu_locations );

		$this->assertEquals( $new_menu_locations, get_option( 'theme_mod_nav_menu_locations' ) );
	}
}
