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
	static $model;

	/**
	 * Initialization before all tests run.
	 *
	 * @param WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 */
	static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$options = PLL_Install::get_default_options();
		$options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis.
		$options['media_support'] = 1; // Force option to pre 3.1 value otherwise phpunit tests break on Travis.
		self::$model = new PLL_Admin_Model( $options );

		// Since WP 5.8 firing the 'init' action registers the legacy widget block which can be registered only once, so let's clean it up to avoid notices.
		add_action(
			'init',
			function() {
				if ( class_exists( 'WP_Block_Type_Registry' ) && WP_Block_Type_Registry::get_instance()->is_registered( 'core/legacy-widget' ) ) {
					unregister_block_type( 'core/legacy-widget' );
				}
			},
			0
		);
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
		self::$model->clean_languages_cache(); // We must do it before database ROLLBACK otherwhise it is impossible to delete the transient

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
	 * @param array  $args   Allows to optionnally override the default values for the language.
	 * @throws InvalidArgumentException If language is not created.
	 */
	static function create_language( $locale, $args = array() ) {
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$values    = $languages[ $locale ];

		$values['slug'] = $values['code'];
		$values['rtl'] = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0; // Default term_group.

		$args = array_merge( $values, $args );

		$links_model     = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$admin_default_term = new PLL_Admin_Default_Term( $pll_admin );

		$errors = self::$model->add_language( $args );
		if ( is_wp_error( $errors ) ) {
			throw new InvalidArgumentException( $errors->get_error_message() );
		}
		$admin_default_term->handle_default_term_on_create_language( $args );
		self::$model->clean_languages_cache();
	}

	/**
	 * Deletes all languages
	 */
	static function delete_all_languages() {
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
}
