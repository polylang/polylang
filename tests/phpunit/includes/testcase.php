<?php

class PLL_UnitTestCase extends WP_UnitTestCase {
	static $polylang;
	static $hooks;

	static function wpSetUpBeforeClass() {
		self::$polylang = new StdClass();

		self::$polylang->options = PLL_Install::get_default_options();
		self::$polylang->options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis
		self::$polylang->model = new PLL_Admin_Model( self::$polylang->options );
		self::$polylang->links_model = self::$polylang->model->get_links_model(); // We always need a links model due to PLL_Language::set_home_url()

		$_SERVER['SCRIPT_FILENAME'] = '/index.php'; // To pass the test in PLL_Choose_Lang::init() by default
	}

	static function wpTearDownAfterClass() {
		self::delete_all_languages();
	}

	function tearDown() {
		unset( $GLOBALS['wp_settings_errors'] );
		self::$polylang->model->clean_languages_cache(); // We must do it before database ROLLBACK otherwhise it is impossible to delete the transient

		parent::tearDown();
	}

	static function create_language( $locale, $args = array() ) {
		include PLL_SETTINGS_INC . '/languages.php';
		$values = $languages[ $locale ];

		$values[3] = (int) 'rtl' === $values[3];
		$values[] = 0; // Default term_group
		$keys = array( 'slug', 'locale', 'name', 'rtl', 'flag', 'term_group' );

		$args = array_merge( array_combine( $keys, $values ), $args );
		self::$polylang->model->add_language( $args );
		unset( $GLOBALS['wp_settings_errors'] ); // Clean "errors"
	}

	static function delete_all_languages() {
		$languages = self::$polylang->model->get_languages_list();
		if ( is_array( $languages ) ) {
			// Delete the default categories first
			$tt = wp_get_object_terms( get_option( 'default_category' ), 'term_translations' );
			$terms = self::$polylang->model->term->get_translations( get_option( 'default_category' ) );

			wp_delete_term( $tt, 'term_translations' );

			foreach ( $terms as $t ) {
				wp_delete_term( $t, 'category' );
			}

			foreach ( $languages as $lang ) {
				self::$polylang->model->delete_language( $lang->term_id );
				unset( $GLOBALS['wp_settings_errors'] );
			}
		}
	}

	/**
	 * Backport assertNotFalse to PHPUnit 3.6.12 which only runs in PHP 5.2.
	 */
	public static function assertNotFalse( $condition, $message = '' ) {
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			self::assertThat( $condition, self::logicalNot( self::isFalse() ), $message );
		} else {
			parent::assertNotFalse( $condition, $message );
		}
	}
}
