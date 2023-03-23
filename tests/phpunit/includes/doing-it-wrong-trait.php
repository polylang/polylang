<?php

/**
 * A trait to mute doing it wrong errors.
 */
trait PLL_Doing_It_Wrong_Trait {
	/**
	 * Don't trigger an error if `PLL_Model::get_languages_list()` is called too early.
	 * WP's test suite already does this in `WP_UnitTestCase_Base::set_up()`, but it happens too late because
	 * we create our languages in `wpSetUpBeforeClass()` with `PLL_UnitTestCase::create_language()`, which calls
	 * `PLL_Admin_Model::add_language()` => `PLL_Admin_Model::validate_lang()` => `PLL_Model::get_languages_list()`.
	 * Should be called in `wpSetUpBeforeClass()`.
	 *
	 * @since 3.4
	 * @see self::doing_it_wrong_run()
	 *
	 * @return void
	 */
	public static function filter_doing_it_wrong_trigger_error() {
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Don't trigger an error if `PLL_Model::get_languages_list()` is called too early.
	 * Note: the parameters `$message` and `$version` are available since WP 6.1.
	 *
	 * @since 3.4
	 * @see WP_UnitTestCase_Base::doing_it_wrong_run()
	 * @see PLL_UnitTestCase_Trait::wpSetUpBeforeClass()
	 *
	 * @param string $function The function to add.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 * @return void
	 */
	public function doing_it_wrong_run( $function, $message = '', $version = '' ) {
		if ( 'PLL_Model::get_languages_list()' === $function ) {
			return;
		}

		parent::doing_it_wrong_run( $function, $message, $version );
	}
}
