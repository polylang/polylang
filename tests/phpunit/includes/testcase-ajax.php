<?php

// FIXME use traits from PHP 5.4 instead of duplicating code from PLL_UnitTestCase
class PLL_Ajax_UnitTestCase extends WP_Ajax_UnitTestCase {
	static $polylang;
	static $hooks;

	static function wpSetUpBeforeClass() {
		//~ self::backup_filters( 'clean' );
		self::$polylang = new StdClass();

		self::$polylang->options = PLL_Install::get_default_options();
		self::$polylang->options['hide_default'] = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis
		self::$polylang->model = new PLL_Admin_Model( self::$polylang->options );
		self::$polylang->links_model = self::$polylang->model->get_links_model(); // we always need a links model due to PLL_Language::set_home_url()
	}

	static function wpTearDownAfterClass() {
		self::delete_all_languages();
		//~ self::restore_filters( 'clean' );
	}

	function tearDown() {
		unset( $GLOBALS['wp_settings_errors'] );
		self::$polylang->model->clean_languages_cache(); // we must do it before database ROLLBACK otherwhise it is impossible to delete the transient

		parent::tearDown();
	}

	static function create_language( $locale, $args = array() ) {
		include PLL_SETTINGS_INC . '/languages.php';
		$values = $languages[ $locale ];

		$values[3] = (int) 'rtl' === $values[3];
		$values[] = 0; // default term_group
		$keys = array( 'slug', 'locale', 'name', 'rtl', 'flag', 'term_group' );

		$args = array_merge( array_combine( $keys, $values ), $args );
		self::$polylang->model->add_language( $args );
		unset( $GLOBALS['wp_settings_errors'] ); // clean "errors"
	}

	static function delete_all_languages() {
		global $wpdb;
		$languages = self::$polylang->model->get_languages_list();
		if ( is_array( $languages ) ) {
			foreach ( $languages as $lang ) {
				// delete the default category first
				if ( $default_cat = self::$polylang->model->term->get_translation( get_option( 'default_category' ), $lang ) ) {
					// for some reason wp_delete_term doesn't work :/
					$wpdb->delete( $wpdb->terms, array( 'term_id' => $default_cat ) );
					$wpdb->delete( $wpdb->term_taxonomy, array( 'term_id' => $default_cat ) );
				}
				self::$polylang->model->delete_language( $lang->term_id );
				unset( $GLOBALS['wp_settings_errors'] );
			}
		}
	}

	static function backup_filters( $case ) {
		$globals = array( 'merged_filters', 'wp_actions', 'wp_current_filter', 'wp_filter' );
		foreach ( $globals as $key ) {
			self::$hooks[ $case ][ $key ] = $GLOBALS[ $key ];
		}
	}

	static function restore_filters( $case ) {
		$globals = array( 'merged_filters', 'wp_actions', 'wp_current_filter', 'wp_filter' );
		foreach ( $globals as $key ) {
			if ( isset( self::$hooks[ $case ][ $key ] ) ) {
				$GLOBALS[ $key ] = self::$hooks[ $case ][ $key ];
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
