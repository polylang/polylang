<?php
/**
 * @package Polylang
 */

/**
 * Main Polylang class for admin (except Polylang pages), accessible from @see PLL().
 *
 * @since 1.2
 */
class PLL_Admin extends PLL_Admin_Base {
	/**
	 * @var PLL_Admin_Filters|null
	 */
	public $filters;

	/**
	 * @var PLL_Admin_Filters_Columns|null
	 */
	public $filters_columns;

	/**
	 * @var PLL_Admin_Filters_Post|null
	 */
	public $filters_post;

	/**
	 * @var PLL_Admin_Filters_Term|null
	 */
	public $filters_term;

	/**
	 * @var PLL_Admin_Filters_Media|null
	 */
	public $filters_media;

	/**
	 * @since 2.9
	 *
	 * @var PLL_Filters_Sanitization|null
	 */
	public $filters_sanitization;

	/**
	 * @var PLL_Admin_Block_Editor|null
	 */
	public $block_editor;

	/**
	 * @var PLL_Admin_Classic_Editor|null
	 */
	public $classic_editor;

	/**
	 * @var PLL_Admin_Nav_Menu|null
	 */
	public $nav_menu;

	/**
	 * @var PLL_Admin_Filters_Widgets_Options|null
	 */
	public $filters_widgets_options;

	/**
	 * Setups filters and action needed on all admin pages and on plugins page.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		// Adds a 'settings' link in the plugins table
		add_filter( 'plugin_action_links_' . POLYLANG_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'in_plugin_update_message-' . POLYLANG_BASENAME, array( $this, 'plugin_update_message' ), 10, 2 );
	}

	/**
	 * Setups filters and action needed on all admin pages and on plugins page
	 * Loads the settings pages or the filters base on the request
	 *
	 * @since 1.2
	 */
	public function init() {
		parent::init();

		// Setup filters for admin pages
		// Priority 5 to make sure filters are there before customize_register is fired
		if ( $this->model->get_languages_list() ) {
			add_action( 'wp_loaded', array( $this, 'add_filters' ), 5 );
		}
	}

	/**
	 * Adds a 'settings' link for our plugin in the plugins list table.
	 *
	 * @since 0.1
	 *
	 * @param string[] $links List of links associated to the plugin.
	 * @return string[] Modified list of links.
	 */
	public function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="admin.php?page=mlang">' . __( 'Settings', 'polylang' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds the upgrade notice in plugins table
	 *
	 * @since 1.1.6
	 *
	 * @param array  $plugin_data Not used
	 * @param object $r           Plugin update data
	 * @return void
	 */
	public function plugin_update_message( $plugin_data, $r ) {
		if ( isset( $r->upgrade_notice ) ) {
			printf( '<p style="margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">%s</p>', esc_html( $r->upgrade_notice ) );
		}
	}

	/**
	 * Setup filters for admin pages
	 *
	 * @since 1.2
	 * @since 2.7 instantiate a PLL_Bulk_Translate instance.
	 * @return void
	 */
	public function add_filters() {
		$this->filters_sanitization = new PLL_Filters_Sanitization( $this->get_locale_for_sanitization() );
		$this->filters_widgets_options = new PLL_Admin_Filters_Widgets_Options( $this );

		// All these are separated just for convenience and maintainability
		$classes = array( 'Filters', 'Filters_Columns', 'Filters_Post', 'Filters_Term', 'Nav_Menu', 'Classic_Editor', 'Block_Editor' );

		// Don't load media filters if option is disabled or if user has no right
		if ( $this->options['media_support'] && ( $obj = get_post_type_object( 'attachment' ) ) && ( current_user_can( $obj->cap->edit_posts ) || current_user_can( $obj->cap->create_posts ) ) ) {
			$classes[] = 'Filters_Media';
		}

		foreach ( $classes as $class ) {
			$obj = strtolower( $class );

			/**
			 * Filter the class to instantiate when loading admin filters
			 *
			 * @since 1.5
			 *
			 * @param string $class class name
			 */
			$class = apply_filters( 'pll_' . $obj, 'PLL_Admin_' . $class );
			$this->$obj = new $class( $this );
		}
	}

	/**
	 * Retrieve the locale according to the current language instead of the language
	 * of the admin interface.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public function get_locale_for_sanitization() {
		$locale = get_locale();

		if ( isset( $_POST['post_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['term_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['inline_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( ! empty( $this->curlang ) ) {
			$locale = $this->curlang->locale;
		}

		return $locale;
	}
}
