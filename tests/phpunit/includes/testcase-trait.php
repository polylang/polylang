<?php

/**
 * A trait to share code between several test case classes.
 */
trait PLL_UnitTestCase_Trait {
	/**
	 * A container for Polylang classes instances.
	 *
	 * @var object
	 */
	static $polylang;

	/**
	 * Initialization before all tests run.
	 *
	 * @param WP_UnitTest_Factory $factory
	 */
	static function wpSetUpBeforeClass( $factory ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::$polylang = new StdClass();

		self::$polylang->options = PLL_Install::get_default_options();
		self::$polylang->options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis.
		self::$polylang->model = new PLL_Admin_Model( self::$polylang->options );
		self::$polylang->links_model = self::$polylang->model->get_links_model(); // We always need a links model due to PLL_Language::set_home_url().
	}

	/**
	 * Deletes all languages after all tests have run.
	 */
	static function wpTearDownAfterClass() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::delete_all_languages();
	}

	/**
	 * Empties the languages cache after all tests
	 */
	function tearDown() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::$polylang->model->clean_languages_cache(); // We must do it before database ROLLBACK otherwhise it is impossible to delete the transient

		$globals = array( 'current_screen', 'hook_suffix', 'wp_settings_errors', 'post_type', 'wp_scripts', 'wp_styles' );
		foreach ( $globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		$_REQUEST = array(); // WP Cleans up only $_POST and $_GET.

		parent::tearDown();
	}

	/**
	 * Helper function to create a language
	 *
	 * @param string $locale Language locale.
	 * @param array  $args   Allows to optionnally override the default values for the language
	 */
	static function create_language( $locale, $args = array() ) {
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$values    = $languages[ $locale ];

		$values['slug'] = $values['code'];
		$values['rtl'] = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0; // Default term_group.

		$args = array_merge( $values, $args );
		self::$polylang->model->add_language( $args );
	}

	/**
	 * Deletes all languages
	 */
	static function delete_all_languages() {
		$languages = self::$polylang->model->get_languages_list();
		if ( is_array( $languages ) ) {
			// Delete the default categories first.
			$tt = wp_get_object_terms( get_option( 'default_category' ), 'term_translations' );
			$terms = self::$polylang->model->term->get_translations( get_option( 'default_category' ) );

			wp_delete_term( $tt, 'term_translations' );

			foreach ( $terms as $t ) {
				wp_delete_term( $t, 'category' );
			}

			foreach ( $languages as $lang ) {
				self::$polylang->model->delete_language( $lang->term_id );
			}
		}
	}
}
