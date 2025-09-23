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
		$factory->language->create_many( 3 );
	}

	/**
	 * Tests that loading the post language meta box in the classic editor
	 * triggers only expected DB queries.
	 */
	public function test_post_language_metabox() {
		global $post_ID;

		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$this->startQueryCount();

		$post_ID = $posts['en'];
		ob_start();
		$pll_admin->classic_editor->post_language();
		ob_end_clean();

		$this->stopQueryCount();

		/*
		 * There is an extra query due to the Privacy policy page translation management.
		 * When getting the edit link for translations in the post language meta box,
		 * `user_can()` function is called and triggers `map_meta_cap` filter on which Polylang is hooked.
		 * @See `PLL_Filters::fix_privacy_policy_page_editing()`.
		 */
		$this->assertSame( 3, $this->query_counter, 'Number of queries when loading post language meta box should be 3.' );
	}

	/**
	 * Starts counting database queries.
	 */
	protected function startQueryCount() {
		$this->query_counter     = 0;
		$this->captured_queries  = array();
		$this->monitoring_active = true;
		$this->start_time        = microtime( true );

		add_filter( 'query', array( $this, 'countQuery' ), 10, 1 );
	}

	/**
	 * Ends counting database queries.
	 */
	protected function stopQueryCount() {
		$this->monitoring_active = false;
		remove_filter( 'query', array( $this, 'countQuery' ), 10 );
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
}
