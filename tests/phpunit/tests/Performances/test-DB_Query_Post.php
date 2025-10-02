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
	 * triggers only expected DB queries.
	 */
	public function test_get_post_translations() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$this->startQueryCount();

		$pll_admin->model->post->get_translations( $posts['en'] );

		$this->stopQueryCount();

		$this->assertSame( 2, $this->query_counter, 'Number of queries when getting post translations should be 2.' );
	}

	/**
	 * Tests that saving the post translations
	 * triggers only expected DB queries.
	 */
	public function test_save_post_translations() {
		$post_en = self::factory()->post->create( array( 'lang' => 'en' ) );
		$post_fr = self::factory()->post->create( array( 'lang' => 'fr' ) );
		$post_de = self::factory()->post->create( array( 'lang' => 'de' ) );

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$this->startQueryCount();

		$pll_admin->model->post->save_translations( $post_en, array( 'fr' => $post_fr, 'de' => $post_de ) );

		$this->stopQueryCount();

		$this->assertSame( 38, $this->query_counter, 'Number of queries when getting post translations should be 2.' );
	}

	/**
	 * Counts each SQL query executed.
	 *
	 * @param string $query La requête SQL
	 * @return string La requête inchangée
	 */
	public function countQuery( $query ) {
		if ( ! $this->monitoring_active ) {
			return $query;
		}

		++$this->query_counter;

		// Stores the query details for later analysis.
		$this->captured_queries[] = array(
			'sql' => $query,
			'timestamp' => microtime( true ) - $this->start_time,
			'backtrace' => wp_debug_backtrace_summary( null, 2, false ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
		);

		return $query;
	}

	/**
	 * Starts counting database queries.
	 */
	private function startQueryCount() {
		$this->query_counter     = 0;
		$this->captured_queries  = array();
		$this->monitoring_active = true;
		$this->start_time        = microtime( true );

		wp_cache_flush();
		add_filter( 'query', array( $this, 'countQuery' ), 10, 1 );
	}

	/**
	 * Ends counting database queries.
	 */
	private function stopQueryCount() {
		$this->monitoring_active = false;
		remove_filter( 'query', array( $this, 'countQuery' ), 10 );
	}
}
