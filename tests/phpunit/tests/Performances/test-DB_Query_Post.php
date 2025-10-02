<?php

namespace WP_Syntex\Polylang\Tests\Integration\Performances;

use PLL_UnitTestCase;
use PLL_Context_Admin;
use PLL_UnitTest_Factory;

class DB_Query_Post_Test extends PLL_UnitTestCase {

	private $query_counter = 0;
	private $captured_queries = array();
	private $monitoring_active = false;
	private $start_time = 0;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );
		$factory->language->create_many( 3 );
	}

	/**
	 * Tests that getting the post translations
	 * triggers only expected DB queries
	 */
	public function test_get_post_translations() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$query_count = $this->count_queries(
			function () use ( $pll_admin, $posts ) {
				$pll_admin->model->post->get_translations( $posts['en'] );
			}
		);

		$this->assertSame( 2, $query_count, 'Number of queries when getting post translations should be 2.' );
	}

	/**
	 * Tests that saving the post translations
	 * triggers only expected DB queries.
	 */
	public function test_save_post_translations() {
		global $wp_version;

		// Skips this test because count result isn't the same for WordPress 6.2.
		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$this->markTestSkipped( 'This test is skipped for WordPress version less than 6.3.' );
		}

		$post_en = self::factory()->post->create( array( 'lang' => 'en' ) );
		$post_fr = self::factory()->post->create( array( 'lang' => 'fr' ) );
		$post_de = self::factory()->post->create( array( 'lang' => 'de' ) );

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$query_count = $this->count_queries(
			function () use ( $pll_admin, $post_en, $post_fr, $post_de ) {
				$pll_admin->model->post->save_translations( $post_en, array( 'fr' => $post_fr, 'de' => $post_de ) );
			}
		);

		$this->assertSame( 38, $query_count, 'Number of queries when getting post translations should be 2.' );
	}

	/**
	 * Counts the number of database queries executed during a callback.
	 *
	 * @param callable $callback The function to execute while counting queries.
	 * @return int The number of queries executed.
	 */
	private function count_queries( callable $callback ) {
		global $wpdb;

		wp_cache_flush();

		$queries_before = $wpdb->num_queries;

		$callback();

		return $wpdb->num_queries - $queries_before;
	}
}
