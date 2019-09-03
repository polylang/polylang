<?php

/**
 * Manages custom menus translations as well as the language switcher menu item on admin side
 *
 * @since 1.2
 */
class PLL_Admin_Nav_Menu extends PLL_Nav_Menu {

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		// Populates nav menus locations
		// Since WP 4.4, must be done before customize_register is fired
		add_filter( 'theme_mod_nav_menu_locations', array( $this, 'theme_mod_nav_menu_locations' ), 20 );

		// Integration in the WP menu interface
		add_action( 'admin_init', array( $this, 'admin_init' ) ); // after Polylang upgrade
	}

	/**
	 * Setups filters and terms
	 * adds the language switcher metabox and create new nav menu locations
	 *
	 * @since 1.1
	 */
	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_update_nav_menu_item', array( $this, 'wp_update_nav_menu_item' ), 10, 2 );

		// Translation of menus based on chosen locations
		add_filter( 'pre_update_option_theme_mods_' . $this->theme, array( $this, 'pre_update_option_theme_mods' ) );
		add_action( 'delete_nav_menu', array( $this, 'delete_nav_menu' ) );

		// FIXME is it possible to choose the order ( after theme locations in WP3.5 and older ) ?
		// FIXME not displayed if Polylang is activated before the first time the user goes to nav menus http://core.trac.wordpress.org/ticket/16828
		add_meta_box( 'pll_lang_switch_box', __( 'Language switcher', 'polylang' ), array( $this, 'lang_switch' ), 'nav-menus', 'side', 'high' );

		$this->create_nav_menu_locations();
	}

	/**
	 * Language switcher metabox
	 * The checkbox and all hidden fields are important
	 * Thanks to John Morris for his very interesting post http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/
	 *
	 * @since 1.1
	 */
	public function lang_switch() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
		?>
		<div id="posttype-lang-switch" class="posttypediv">
			<div id="tabs-panel-lang-switch" class="tabs-panel tabs-panel-active">
				<ul id="lang-switch-checklist" class="categorychecklist form-no-clear">
					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-object-id]" value="-1"> <?php esc_html_e( 'Languages', 'polylang' ); ?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php esc_html_e( 'Languages', 'polylang' ); ?>">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-url]" value="#pll_switcher">
					</li>
				</ul>
			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit" <?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'polylang' ); ?>" name="add-post-type-menu-item" id="submit-posttype-lang-switch">
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Prepares javascript to modify the language switcher menu item
	 *
	 * @since 1.1
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'nav-menus' != $screen->base ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'pll_nav_menu', plugins_url( '/js/nav-menu' . $suffix . '.js', POLYLANG_FILE ), array( 'jquery' ), POLYLANG_VERSION );

		$data = array(
			'strings' => PLL_Switcher::get_switcher_options( 'menu', 'string' ), // The strings for the options
			'title'   => __( 'Languages', 'polylang' ), // The title
			'val'     => array(),
		);

		// Get all language switcher menu items
		$items = get_posts(
			array(
				'numberposts' => -1,
				'nopaging'    => true,
				'post_type'   => 'nav_menu_item',
				'fields'      => 'ids',
				'meta_key'    => '_pll_menu_item',
			)
		);

		// The options values for the language switcher
		foreach ( $items as $item ) {
			$data['val'][ $item ] = get_post_meta( $item, '_pll_menu_item', true );
		}

		// Send all these data to javascript
		wp_localize_script( 'pll_nav_menu', 'pll_data', $data );
	}

	/**
	 * Save our menu item options
	 *
	 * @since 1.1
	 *
	 * @param int $menu_id not used
	 * @param int $menu_item_db_id
	 */
	public function wp_update_nav_menu_item( $menu_id = 0, $menu_item_db_id = 0 ) {
		if ( empty( $_POST['menu-item-url'][ $menu_item_db_id ] ) || '#pll_switcher' !== $_POST['menu-item-url'][ $menu_item_db_id ] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Security check as 'wp_update_nav_menu_item' can be called from outside WP admin
		if ( current_user_can( 'edit_theme_options' ) ) {
			check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

			$options = array( 'hide_if_no_translation' => 0, 'hide_current' => 0, 'force_home' => 0, 'show_flags' => 0, 'show_names' => 1, 'dropdown' => 0 ); // Default values
			// Our jQuery form has not been displayed
			if ( empty( $_POST['menu-item-pll-detect'][ $menu_item_db_id ] ) ) {
				if ( ! get_post_meta( $menu_item_db_id, '_pll_menu_item', true ) ) { // Our options were never saved
					update_post_meta( $menu_item_db_id, '_pll_menu_item', $options );
				}
			}
			else {
				foreach ( array_keys( $options ) as $opt ) {
					$options[ $opt ] = empty( $_POST[ 'menu-item-' . $opt ][ $menu_item_db_id ] ) ? 0 : 1;
				}
				update_post_meta( $menu_item_db_id, '_pll_menu_item', $options ); // Allow us to easily identify our nav menu item
			}
		}
	}

	/**
	 * Assign menu languages and translations based on ( temporary ) locations
	 *
	 * @since 1.8
	 *
	 * @param array $locations nav menu locations
	 * @return array
	 */
	public function update_nav_menu_locations( $locations ) {
		// Extract language and menu from locations
		foreach ( $locations as $loc => $menu ) {
			$infos = $this->explode_location( $loc );
			$this->options['nav_menus'][ $this->theme ][ $infos['location'] ][ $infos['lang'] ] = $menu;
			if ( $this->options['default_lang'] != $infos['lang'] ) {
				unset( $locations[ $loc ] ); // Remove temporary locations before database update
			}
		}

		update_option( 'polylang', $this->options );
		return $locations;
	}

	/**
	 * Assign menu languages and translations based on ( temporary ) locations
	 *
	 * @since 1.1
	 *
	 * @param array $mods theme mods
	 * @return unmodified $mods
	 */
	public function pre_update_option_theme_mods( $mods ) {
		if ( current_user_can( 'edit_theme_options' ) && isset( $mods['nav_menu_locations'] ) ) {

			// Manage Locations tab in Appearance -> Menus
			if ( isset( $_GET['action'] ) && 'locations' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				check_admin_referer( 'save-menu-locations' );
				$this->options['nav_menus'][ $this->theme ] = array();
			}

			// Edit Menus tab in Appearance -> Menus
			// Add the test of $_POST['update-nav-menu-nonce'] to avoid conflict with Vantage theme
			elseif ( isset( $_POST['action'], $_POST['update-nav-menu-nonce'] ) && 'update' === $_POST['action'] ) {
				check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );
				$this->options['nav_menus'][ $this->theme ] = array();
			}

			// Customizer
			// Don't reset locations in this case.
			// see http://wordpress.org/support/topic/menus-doesnt-show-and-not-saved-in-theme-settings-multilingual-site
			elseif ( isset( $_POST['action'] ) && 'customize_save' == $_POST['action'] ) {
				check_ajax_referer( 'save-customize_' . $GLOBALS['wp_customize']->get_stylesheet(), 'nonce' );
			}

			else {
				return $mods; // No modification for nav menu locations
			}

			$mods['nav_menu_locations'] = $this->update_nav_menu_locations( $mods['nav_menu_locations'] );
		}
		return $mods;
	}

	/**
	 * Fills temporary menu locations based on menus translations
	 *
	 * @since 1.2
	 *
	 * @param bool|array $menus
	 * @return bool|array modified list of menu locations
	 */
	public function theme_mod_nav_menu_locations( $menus ) {
		// Prefill locations with 0 value in case a location does not exist in $menus
		$locations = get_registered_nav_menus();
		if ( is_array( $locations ) ) {
			$locations = array_fill_keys( array_keys( $locations ), 0 );
			$menus = is_array( $menus ) ? array_merge( $locations, $menus ) : $locations;
		}

		if ( is_array( $menus ) ) {
			foreach ( array_keys( $menus ) as $loc ) {
				foreach ( $this->model->get_languages_list() as $lang ) {
					if ( ! empty( $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ] ) && term_exists( $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ], 'nav_menu' ) ) {
						$menus[ $this->combine_location( $loc, $lang ) ] = $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ];
					}
				}
			}
		}

		return $menus;
	}

	/**
	 * Removes the nav menu term_id from the locations stored in Polylang options when a nav menu is deleted
	 *
	 * @since 1.7.3
	 *
	 * @param int $term_id nav menu id
	 */
	public function delete_nav_menu( $term_id ) {
		if ( isset( $this->options['nav_menus'] ) ) {
			foreach ( $this->options['nav_menus'] as $theme => $locations ) {
				foreach ( $locations as $loc => $languages ) {
					foreach ( $languages as $lang => $menu_id ) {
						if ( $menu_id === $term_id ) {
							unset( $this->options['nav_menus'][ $theme ][ $loc ][ $lang ] );
						}
					}
				}
			}

			update_option( 'polylang', $this->options );
		}
	}
}
