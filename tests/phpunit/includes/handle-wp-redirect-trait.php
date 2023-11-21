<?php

/**
 * Trait to run tests containing `wp_redirect()` and `exit`.
 * Also privides some assertions for the redirection.
 */
trait PLL_Handle_WP_Redirect_Trait {
	/**
	 * Whether or not `wp_redirect()` has been called.
	 *
	 * @var bool
	 */
	protected $has_redirect = false;

	/**
	 * Stores the last redirection location made with `wp_redirect()`.
	 *
	 * @var string
	 */
	protected $last_location;

	/**
	 * Stores the last redirection status made with `wp_redirect()`.
	 *
	 * @var int
	 */
	protected $last_status;

	/**
	 * Allows to continue the execution after wp_redirect() + exit.
	 *
	 * @return void
	 */
	protected function handle_wp_redirect() {
		$this->redirect_location = '';
		$this->redirect_status = 0;
		
		add_filter(
			'wp_redirect',
			function ( $location, $status ) { // phpcs:ignore WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.MissingReturnStatement
				$this->has_redirect  = true;
				$this->last_location = $location;
				$this->last_status   = $status;

				throw new Exception( 'wp_redirect' );
			},
			10,
			2
		);
	}

	/**
	 * Asserts `wp_redirect()` has been called.
	 *
	 * @param string $message Error message to display.
	 *
	 * @return void
	 */
	protected function assert_redirect( $message = '' ) {
		$this->assertNotEmpty( $this->redirect_location, $message );
	}

	/**
	 * Asserts last redirection location is as expected.
	 *
	 * @param string $expected_location Expected location of the redirection.
	 * @param string $message           Error message to display.
	 *
	 * @return void
	 */
	protected function assert_redirect_location( $expected_location, $message = '' ) {
		$this->assertSame( $expected_location, $this->last_location, $message );
	}

	/**
	 * Asserts last redirection status code is as expected.
	 *
	 * @param string $expected_status Expected status of the redirection.
	 * @param string $message         Error message to display.
	 *
	 * @return void
	 */
	protected function assert_redirect_status( $expected_status, $message = '' ) {
		$this->assertSame( $expected_status, $this->last_status, $message );
	}

	/**
	 * Resets the hanbdler so a new call to `wp_redirect()` and new assertions can be made safely.
	 * Should be called in `tear_down()` as well.
	 *
	 * @return void
	 */
	protected function reset_wp_redirect_handler() {
		$this->has_redirect  = false;
		$this->last_location = null;
		$this->last_status   = null;
	}
}
