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
	}

	static function wpTearDownAfterClass() {
		self::delete_all_languages();
	}

	function setUp() {
		parent::setUp();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default
		add_filter( 'wp_doing_ajax', '__return_false' );
	}

	function tearDown() {
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
