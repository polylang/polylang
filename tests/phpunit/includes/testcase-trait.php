<?php

/**
 * A trait to share code between several test case classes.
 */
trait PLL_UnitTestCase_Trait {

	/**
	 * @var array|null
	 */
	protected $options;

	/**
	 * @var PLL_Links_Model|null
	 */
	protected $links_model;

	/**
	 * @var PLL_Model|null
	 */
	protected $pll_model;

	/**
	 * @var PLL_Frontend|null
	 */
	protected $frontend;

	/**
	 * @var PLL_Admin|null
	 */
	protected $pll_admin;

	/**
	 * @var PLL_Base|null
	 */
	protected $pll_env;

	/**
	 * @var PLL_Admin_Model|null
	 */
	public static $model;

	/**
	 * The admin submenu.
	 *
	 * @var array|null
	 */
	protected static $submenu;

	/**
	 * Initialization before all tests run.
	 *
	 * @param WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$options = PLL_Install::get_default_options();
		$options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis.
		$options['media_support'] = 1; // Force option to pre 3.1 value otherwise phpunit tests break on Travis.
		self::$model = new PLL_Admin_Model( $options );

		remove_action( 'current_screen', '_load_remote_block_patterns' );
		remove_action( 'current_screen', '_load_remote_featured_patterns' );
	}

	/**
	 * Deletes all languages after all tests have run.
	 */
	public static function wpTearDownAfterClass() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::delete_all_languages();
	}

	/**
	 * Empties the languages cache after all tests
	 */
	public function tear_down() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::$model->clean_languages_cache(); // We must do it before database ROLLBACK otherwhise it is impossible to delete the transient

		$globals = array( 'current_screen', 'hook_suffix', 'wp_settings_errors', 'post_type', 'wp_scripts', 'wp_styles' );
		foreach ( $globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		$_REQUEST = array(); // WP Cleans up only $_POST and $_GET.

		parent::tear_down();
	}

	/**
	 * Helper function to create a language
	 *
	 * @param string $locale Language locale.
	 * @param array  $args   Allows to optionnally override the default values for the language.
	 * @throws InvalidArgumentException If language is not created.
	 */
	public static function create_language( $locale, $args = array() ) {
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$values    = $languages[ $locale ];

		$values['slug']       = $values['code'];
		$values['rtl']        = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0;

		$args = array_merge( $values, $args );

		$errors = self::$model->add_language( $args );
		if ( is_wp_error( $errors ) ) {
			throw new InvalidArgumentException( $errors->get_error_message() );
		}

		self::$model->clean_languages_cache();
	}

	/**
	 * Deletes all languages
	 */
	public static function delete_all_languages() {
		$languages = self::$model->get_languages_list();
		if ( is_array( $languages ) ) {
			// Delete the default categories first.
			$tt = wp_get_object_terms( get_option( 'default_category' ), 'term_translations' );
			$terms = self::$model->term->get_translations( get_option( 'default_category' ) );

			wp_delete_term( $tt, 'term_translations' );

			foreach ( $terms as $t ) {
				wp_delete_term( $t, 'category' );
			}

			foreach ( $languages as $lang ) {
				self::$model->delete_language( $lang->term_id );
			}
		}
	}

	protected function require_wp_menus( $trigger_hooks = true ) {
		global $submenu, $wp_filter;
		global $_wp_submenu_nopriv;

		if ( isset( static::$submenu ) ) {
			$submenu = static::$submenu;

			if ( $trigger_hooks ) {
				do_action( 'admin_menu', '' );
			}

			return static::$submenu;
		}

		$hooks = isset( $wp_filter['admin_menu'] ) ? $wp_filter['admin_menu'] : null;
		unset( $wp_filter['admin_menu'] );

		require_once ABSPATH . 'wp-admin/menu.php';

		static::$submenu = $submenu;

		if ( isset( $hooks ) ) {
			$wp_filter['admin_menu'] = $hooks;
		}

		if ( $trigger_hooks ) {
			do_action( 'admin_menu', '' );
		}

		return static::$submenu;
	}
}
