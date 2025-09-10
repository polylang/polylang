<?php

use WP_Syntex\Polylang\Options\Options;

/**
 * A trait to share code between several test case classes.
 *
 * Notes:
 * - Order of the "set up before class" methods: `set_up_before_class()` => `wpSetUpBeforeClass()` => `pllSetUpBeforeClass()`.
 *
 * TODO: create a common way to instantiate PLL_Base, PLL_Model, and PLL_Links_Model; so we don't need to define those
 * class properties here.
 */
trait PLL_UnitTestCase_Trait {
	use PLL_Doing_It_Wrong_Trait;
	use PLL_Options_Trait;

	/**
	 * @var Options|null
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
	 * @deprecated Use `PLL_UnitTest_Factory_For_Language` instead.
	 *
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
	 * Polylang Factory.
	 *
	 * @var PLL_UnitTest_Factory|null
	 */
	protected static $pll_factory = null;

	/**
	 * Initialization before all tests run.
	 * - Creates the deprecated `self::$model`.
	 * - Removes some hooks.
	 * - Tweaks `_doing_it_wrong()`.
	 * - Calls `self::pllSetUpBeforeClass()`.
	 *
	 * @param WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		self::create_model_legacy();

		remove_action( 'current_screen', '_load_remote_block_patterns' );
		remove_action( 'current_screen', '_load_remote_featured_patterns' );

		/*
		 * `print_emoji_styles()` is deprecated since WP 6.4, but still hooked for backward compatibility {@see https://core.trac.wordpress.org/ticket/58775}.
		 */
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );

		self::filter_doing_it_wrong_trigger_error();

		/*
		 * Ensure `$factory` is an instance of `PLL_UnitTest_Factory` otherwise testcases directly
		 * extending WordPress ones instead of our `WP_UnitTestCase_Polyfill` would get a fatal error.
		 */
		if ( $factory instanceof PLL_UnitTest_Factory ) {
			static::pllSetUpBeforeClass( $factory );
		}
	}

	/**
	 * Initialization before all tests run.
	 *
	 * @param PLL_UnitTest_Factory $factory PLL_UnitTest_Factory object.
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Does nothing, only ensures the factory is correctly type hinted.
	}

	/**
	 * Fetches the factory object for generating Polylang and WordPress fixtures.
	 *
	 * @return PLL_UnitTest_Factory The fixture factory.
	 */
	protected static function factory() {
		if ( ! self::$pll_factory ) {
			self::$pll_factory = new PLL_UnitTest_Factory();
		}
		return self::$pll_factory;
	}

	/**
	 * Deletes all languages after all tests have run.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		self::delete_all_languages();
	}

	/**
	 * Empties the languages cache after all tests.
	 * Resets some globals and superglobals.
	 *
	 * @return void
	 */
	public function tear_down() {
		self::$model->clean_languages_cache(); // We must do it before database ROLLBACK otherwise it is impossible to delete the transient.

		$globals = array( 'current_screen', 'hook_suffix', 'wp_settings_errors', 'post_type', 'taxonomy', 'wp_scripts', 'wp_styles', 'pagenow', 'taxnow', 'typenow' );
		foreach ( $globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		$_REQUEST = array(); // WP Cleans up only $_POST and $_GET.
		$this->reset__SERVER();

		parent::tear_down();
	}

	/**
	 * Helper function to create a language.
	 *
	 * @deprecated Use `PLL_UnitTest_Factory_For_Language` instead.
	 * @throws InvalidArgumentException If language is not created.
	 *
	 * @param string $locale Language locale.
	 * @param array  $args   Allows to optionally override the default values for the language.
	 * @return void
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
	 * Deletes all languages.
	 *
	 * @return void
	 */
	public static function delete_all_languages() {
		self::$model->options['default_lang'] = ''; // Force a dummy value to avoid warnings.
		$languages = self::$model->get_languages_list();

		if ( ! is_array( $languages ) ) {
			return;
		}

		// Delete the default categories first.
		$tt    = wp_get_object_terms( get_option( 'default_category' ), 'term_translations' );
		$terms = self::$model->term->get_translations( get_option( 'default_category' ) );

		wp_delete_term( $tt, 'term_translations' );

		foreach ( $terms as $t ) {
			wp_delete_term( $t, 'category' );
		}

		foreach ( $languages as $lang ) {
			self::$model->delete_language( $lang->term_id );
		}
	}

	/**
	 * Requires WP's admin menus.
	 *
	 * @param bool $trigger_hooks Whether trigger `admin_menu` hook or not.
	 * @return array
	 */
	protected function require_wp_menus( $trigger_hooks = true ) {
		global $submenu, $wp_filter;
		global $_wp_submenu_nopriv;

		if ( isset( static::$submenu ) ) {
			$submenu = static::$submenu;
		} else {
			$hooks = $wp_filter['admin_menu'] ?? null;
			unset( $wp_filter['admin_menu'] );

			require_once ABSPATH . 'wp-admin/menu.php';

			static::$submenu = $submenu;

			if ( isset( $hooks ) ) {
				$wp_filter['admin_menu'] = $hooks;
			}
		}

		if ( $trigger_hooks ) {
			do_action( 'admin_menu', '' );
		}

		return static::$submenu;
	}

	/**
	 * Requires the API functions.
	 *
	 * @return void
	 */
	protected static function require_api(): void {
		require_once POLYLANG_DIR . '/include/api.php';
	}

	/**
	 * Creates the static model used to add languages before tests.
	 *
	 * @deprecated Use `PLL_UnitTest_Factory_For_Language` instead.
	 *
	 * @return void
	 */
	protected static function create_model_legacy() {
		$options = self::create_options(
			array(
				'hide_default'  => false,
				'media_support' => true,
				'version'       => POLYLANG_VERSION,
			)
		);
		self::$model = new PLL_Admin_Model( $options );
	}

	/**
	 * Verifies that a file exists.
	 * Depending on the environment variable `GITHUB_ACTIONS`:
	 * - If defined (value in GitHub Actions is `'true'`): does an assertion.
	 * - If not defined: skips if the file doesn't exist.
	 *
	 * @see https://docs.github.com/en/actions/writing-workflows/choosing-what-your-workflow-does/store-information-in-variables#default-environment-variables
	 *
	 * @param string $path    Path to the file.
	 * @param string $message Error message.
	 * @return void
	 */
	protected static function markTestSkippedIfFileNotExists( string $path, string $message = '' ): void {
		if ( false !== getenv( 'GITHUB_ACTIONS' ) ) {
			self::assertFileExists( $path, $message );
			return;
		}

		if ( ! file_exists( $path ) ) {
			self::markTestSkipped( $message );
		}
	}
}
