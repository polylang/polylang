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
	protected $has_redirect;

	/**
	 * Stores the last redirection location made with `wp_redirect()`.
	 *
	 * @var string
	 */
	protected $redirect_location;

	/**
	 * Stores the last redirection status made with `wp_redirect()`.
	 *
	 * @var int
	 */
	protected $redirect_status;

	/**
	 * Asserts `wp_redirect()` has been called during a callback execution.
	 *
	 * @param callable $callback Callback to call.
	 * @param string   $args     Parameters to pass to the callback, default to empty array.
	 * @param string   $message  Error message to display, a default one is provided.
	 *
	 * @return void
	 */
	protected function assert_redirect( $callback, $args = array(), $message = 'A redirection should have been made.' ) {
		$this->has_redirect      = false;
		$this->redirect_location = '';
		$this->redirect_status   = 0;

		add_filter(
			'wp_redirect',
			function ( $location, $status ) { // phpcs:ignore WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.MissingReturnStatement
				$this->has_redirect      = true;
				$this->redirect_location = $location;
				$this->redirect_status   = $status;

				throw new PLL_WP_Redirect_Exit_Exception();
			},
			10,
			2
		);

		try {
			call_user_func( $callback, ...$args );
		} catch ( PLL_WP_Redirect_Exit_Exception $e ) {
			unset( $e );
		}

		$this->assertTrue( $this->has_redirect, $message );
	}

	/**
	 * Asserts last redirection location is as expected.
	 *
	 * @param string $expected_location Expected location of the redirection.
	 * @param string $message           Error message to display.
	 *
	 * @return void
	 */
	protected function assert_redirect_location( $expected_location, $message = 'A redirection occured without the expected location.' ) {
		$this->assertSame( $expected_location, $this->redirect_location, $message );
	}

	/**
	 * Asserts last redirection status code is as expected.
	 *
	 * @param string $expected_status Expected status of the redirection.
	 * @param string $message         Error message to display.
	 *
	 * @return void
	 */
	protected function assert_redirect_status( $expected_status, $message = 'A redirection occured without the expected status.' ) {
		$this->assertSame( $expected_status, $this->redirect_status, $message );
	}
}
