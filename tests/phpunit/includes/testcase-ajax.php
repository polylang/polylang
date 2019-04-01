<?php

class PLL_Ajax_UnitTestCase extends WP_Ajax_UnitTestCase {
	// FIXME use traits from PHP 5.4 instead of duplicating code from PLL_UnitTestCase
	static $polylang;
	static $hooks;

	static function wpSetUpBeforeClass() {
		self::$polylang = new StdClass();

		self::$polylang->options = PLL_Install::get_default_options();
		self::$polylang->options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis
		self::$polylang->model = new PLL_Admin_Model( self::$polylang->options );
		self::$polylang->links_model = self::$polylang->model->get_links_model(); // We always need a links model due to PLL_Language::set_home_url()
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
		$languages = include PLL_SETTINGS_INC . '/languages.php';
		$values    = $languages[ $locale ];

		$values['slug'] = $values['code'];
		$values['rtl'] = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0; // default term_group

		$args = array_merge( $values, $args );
		self::$polylang->model->add_language( $args );
	}

	static function delete_all_languages() {
		global $wpdb;
		$languages = self::$polylang->model->get_languages_list();
		if ( is_array( $languages ) ) {
			foreach ( $languages as $lang ) {
				// Delete the default category first
				if ( $default_cat = self::$polylang->model->term->get_translation( get_option( 'default_category' ), $lang ) ) {
					// For some reason wp_delete_term doesn't work :/
					$wpdb->delete( $wpdb->terms, array( 'term_id' => $default_cat ) );
					$wpdb->delete( $wpdb->term_taxonomy, array( 'term_id' => $default_cat ) );
				}
				self::$polylang->model->delete_language( $lang->term_id );
			}
		}
	}

	/**
	 * Backport assertNotFalse to PHPUnit 3.6.12 which only runs in PHP 5.2.
	 *
	 * @param bool   $condition
	 * @param string $message
	 */
	public static function assertNotFalse( $condition, $message = '' ) {
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			self::assertThat( $condition, self::logicalNot( self::isFalse() ), $message );
		} else {
			parent::assertNotFalse( $condition, $message );
		}
	}
}
