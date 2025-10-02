<?php

namespace WP_Syntex\Polylang\Tests\Integration\Options\Abstract_List;

use PLL_UnitTestCase;
use WP_UnitTest_Factory;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Business;

/**
 * Test the `prepare()` method of all classes extending {@see WP_Syntex\Polylang\Options\Abstract_List}.
 */
class Prepare_Test extends PLL_UnitTestCase {
	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::require_api();
		require_once PLL_TEST_DATA_DIR . 'translatable.php';
	}

	public static function wpTearDownAfterClass() {
		array_map( 'unregister_taxonomy', array( 'foo1_language', 'foo2_language', 'foo3_language', 'foo4_language', 'tax1', 'tax2', 'tax3', 'tax4' ) );
		array_map( 'unregister_post_type', array( 'cpt1', 'cpt2', 'cpt3', 'cpt4' ) );

		parent::wpTearDownAfterClass();
	}

	public function test_post_types() {
		array_map( 'register_post_type', array( 'cpt1', 'cpt2', 'cpt3', 'cpt4' ) );

		$this->test_option(
			Business\Post_Types::key(),
			array( 'cpt1', 'cpt2', 'cpt3' ),
			array( 'cpt1', 'cpt4' )
		);
	}

	public function test_taxonomies() {
		foreach ( array( 'tax1', 'tax2', 'tax3', 'tax4' ) as $tax ) {
			register_taxonomy( $tax, 'post' );
		}

		$this->test_option(
			Business\Taxonomies::key(),
			array( 'tax1', 'tax2', 'tax3' ),
			array( 'tax1', 'tax4' )
		);
	}

	public function test_sync() {
		$this->test_option(
			Business\Sync::key(),
			array( 'taxonomies', 'post_meta', 'comment_status' ),
			array( 'taxonomies', '_thumbnail_id' )
		);
	}

	/**
	 * @param string $key        Option key.
	 * @param array  $expected_1 Value at the beginning of the test.
	 * @param array  $expected_2 Value to set.
	 * @return void
	 */
	private function test_option( string $key, array $expected_1, array $expected_2 ): void {
		$duplicates    = array_merge( $expected_1, $expected_1, $expected_1 );
		$this->options = self::create_options( array( $key => $duplicates ) );

		// Make sure the DB contains duplicates.
		$options = get_option( Options::OPTION_NAME );
		$this->assertIsArray( $options );
		$this->assertArrayHasKey( $key, $options );
		$this->assertSame( $duplicates, $options[ $key ] );

		// Make sure the value in the object doesn’t contain duplicates.
		$this->assertSame( $expected_1, $this->options->get( $key ) );

		// Update with a different value, otherwise the DB won't be updated.
		$duplicates = array_merge( $expected_2, $expected_2, $expected_2 );
		$this->options->set( $key, $duplicates );

		// Make sure the value in the object doesn’t contain duplicates.
		$this->assertSame( $expected_2, $this->options->get( $key ) );

		// Make sure the DB doesn’t contain duplicates anymore.
		$this->options->save();

		$options = get_option( Options::OPTION_NAME );
		$this->assertIsArray( $options );
		$this->assertArrayHasKey( $key, $options );
		$this->assertSame( $expected_2, $options[ $key ] );
	}
}
