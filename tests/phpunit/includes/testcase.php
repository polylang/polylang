<?php

/**
 * A test case class for Polylang standard tests
 */
abstract class PLL_UnitTestCase extends WP_UnitTestCase {
	use PLL_UnitTestCase_Trait;

	public function set_up() {
		parent::set_up();

		add_filter( 'wp_using_themes', '__return_true' ); // To pass the test in PLL_Choose_Lang::init() by default.
		add_filter( 'wp_doing_ajax', '__return_false' );
	}

	/**
	 * Asserts an object has the correct language set.
	 *
	 * @param WP_Post|WP_Term $object   Object to check, only post and term supported.
	 * @param string          $language Language slug to check.
	 * @param string          $message  Error message.
	 * @return void
	 */
	protected function assert_has_language( $object, $language, $message = '' ) {
		$object_language = false;

		if ( $object instanceof WP_Post ) {
			$object_language = self::$model->post->get_language( $object->ID );
		} elseif ( $object instanceof WP_Term ) {
			$object_language = self::$model->term->get_language( $object->term_id );
		}

		$this->assertNotFalse( $object_language, $message );
		$this->assertSame( $language, $object_language->slug, $message );
	}

	/**
	 * Allows to continue the execution after wp_redirect + exit.
	 *
	 * @param string $expected_location Expected location.
	 * @param int    $expected_status   Expected Status.
	 * @param string $message           Error message.
	 * @return void
	 */
	protected function expect_wp_redirect( $expected_location = '', $expected_status = 0, $message = '' ) {
		add_filter(
			'wp_redirect',
			function ( $location, $status ) use ( $expected_location, $expected_status ) { // phpcs:ignore WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.MissingReturnStatement
				if ( ! empty( $expected_location ) ) {
					$this->assertSame( $expected_location, $location );
				}

				if ( ! empty( $expected_status ) ) {
					$this->assertSame( $expected_status, $status );
				}

				throw new Exception( 'Call to wp_redirect' );
			},
			10,
			2
		);

		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Call to wp_redirect', $message );
	}
}
