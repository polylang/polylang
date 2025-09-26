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

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		$pll_admin = ( new PLL_Context_Admin() )->get();
		// To avoid an extra query due to the Privacy policy page translation management.
		remove_filter( 'map_meta_cap', array( $pll_admin->filters, 'fix_privacy_policy_page_editing' ), 10 );

		wp_cache_flush();
		$this->startQueryCount();

		$post_ID = $posts['en'];
		ob_start();
		$pll_admin->classic_editor->post_language();
		ob_end_clean();

		$this->stopQueryCount();

		$this->writeResultToFile( array( 'posts' => $posts, 'translations' => reset( $terms ) ) );

		$this->assertSame( 10, $this->query_counter, 'Number of queries when loading post language meta box should be 10.' );
	}

	/**
	 * Tests that getting the post translations
	 * triggers only expected DB queries.
	 */
	public function test_post_translations() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' ),
			array( 'lang' => 'de' )
		);

		$terms = wp_get_object_terms( $posts, 'post_translations' );
		$this->assertCount( 1, $terms );

		$pll_admin = ( new PLL_Context_Admin() )->get();
		// To avoid an extra query due to the Privacy policy page translation management.
		remove_filter( 'map_meta_cap', array( $pll_admin->filters, 'fix_privacy_policy_page_editing' ), 10 );

		wp_cache_flush();
		$this->startQueryCount();

		$pll_admin->model->post->get_translations( $posts['en'] );

		$this->stopQueryCount();

		$this->writeResultToFile( array( 'posts' => $posts, 'translations' => reset( $terms ) ) );

		$this->assertSame( 2, $this->query_counter, 'Number of queries when getting post translations should be 2.' );
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

		add_filter( 'query', array( $this, 'countQuery' ), 10, 1 );
	}

	/**
	 * Ends counting database queries.
	 */
	private function stopQueryCount() {
		$this->monitoring_active = false;
		remove_filter( 'query', array( $this, 'countQuery' ), 10 );
	}

	private function writeResultToFile( array $objects ) {
		global $wpdb;

		$content  = "=== POLYLANG DB QUERIES LOG ===\n";
		$content .= 'Test: ' . $this->getName() . "\n";
		$content .= 'Total queries: ' . $this->query_counter . "\n";
		$content .= 'Total execution time: ' . number_format( microtime( true ) - $this->start_time, 4 ) . " seconds\n";
		$content .= str_repeat( '=', 50 ) . "\n\n";

		$content .= "--- Posts ---\n";

		$callback = function ( $key, $value ) {
			return $key . ' => ' . $value;
		};

		$posts = array_map(
			$callback,
			array_keys( $objects['posts'] ),
			$objects['posts']
		);

		$content .= implode( "\n", $posts ) . "\n";
		$content .= str_repeat( '=', 13 ) . "\n\n";

		if ( ! empty( $objects['translations'] ) ) {
			$content .= "--- Translations ---\n";
			$content .= "Translations group: {$objects['translations']->term_id}\n";

			$translations = maybe_unserialize( $objects['translations']->description );

			$content .= implode( "\n", array_map( $callback, array_keys( $translations ), $translations ) ) . "\n";
			$content .= str_repeat( '=', 20 ) . "\n\n";
		}

		foreach ( $this->captured_queries as $i => $query_data ) {
			$content .= "--- QUERY #{$i} ---\n";
			$content .= "SQL:\n" . $query_data['sql'] . "\n\n";

			// Rerun the query after `query` filter has been removed.
			$results = $wpdb->get_results( $query_data['sql'], ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$formatted = array();
			if ( ! empty( $results ) ) {

				foreach ( $results as $index => $row ) {
					$formatted[] = '=== Résultat #' . ( $index + 1 ) . ' ===';
					$formatted = array_merge(
						$formatted,
						array_map( $callback, array_keys( $row ), $row )
					);
					$formatted[] = '';
				}
			}

			$content .= implode( "\n", $formatted ) . "\n";
			$content .= "Timestamp: {$query_data['timestamp']}s\n";
			$content .= "Backtrace:\n" . implode( "\n", $query_data['backtrace'] ) . "\n\n\n";
		}

		$log_dir = PLL_TEST_DATA_DIR . '/polylang-query-logs/';

		@mkdir( $log_dir );
		$timestamp = gmdate( 'Y-m-d_H-i-s' );
		file_put_contents( "{$log_dir}queries_{$timestamp}.log", $content );
	}
}
