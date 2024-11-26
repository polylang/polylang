<?php

use Brain\Monkey\Functions;

/**
 * A test case class for Polylang standard tests
 */
abstract class PLL_UnitTestCase extends WP_UnitTestCase_Polyfill {
	use PLL_UnitTestCase_Trait;

	public function set_up() {
		Functions\when( 'pll_is_plugin_active' )->alias(
			function ( $value ) {
				return POLYLANG_BASENAME === $value;
			}
		);

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
	 * Verifies that a file exists.
	 * Depending on the environment var `PLL_SKIP_PLUGINS_TESTS`, skips or does an assertion if the file doesn't exist.
	 *
	 * @param string $path    Path to the file.
	 * @param string $message Error message.
	 * @return void
	 */
	protected static function markTestSkippedIfFileNotExists( string $path, string $message = '' ): void {
		if ( ! getenv( 'PLL_SKIP_PLUGINS_TESTS' ) ) {
			self::assertFileExists( $path, $message );
			return;
		}
		if ( ! file_exists( $path ) ) {
			self::markTestSkipped( $message );
		}
	}
}
