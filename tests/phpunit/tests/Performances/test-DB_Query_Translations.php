<?php

namespace WP_Syntex\Polylang\Tests\Integration\Performances;

use PLL_UnitTestCase;
use PLL_Context_Admin;
use PLL_UnitTest_Factory;

class DB_Query_Translations_Test extends PLL_UnitTestCase {

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

		/*
		 * Expected queries from 1 call to `wp_get_object_terms()`:
		 * --------------------------------------------------------
		 *
		 * SELECT DISTINCT t.term_id, tr.object_id
		 *    FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *    WHERE tt.taxonomy IN ('language', 'post_translations') AND tr.object_id IN (4)
		 *    ORDER BY t.name ASC
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (2,8)
		 */
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

		/*
		 * Expected queries:
		 *
		 * 3 call to `wp_get_object_terms()`, 2 queries each:
		 * --------------------------------------------------
		 *
		 * SELECT DISTINCT t.term_id, tr.object_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('language', 'post_translations') AND tr.object_id IN (7)
		 *     ORDER BY t.name ASC
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (2)
		 * SELECT DISTINCT t.term_id, tr.object_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('language', 'post_translations') AND tr.object_id IN (8)
		 *     ORDER BY t.name ASC
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (4)
		 * SELECT DISTINCT t.term_id, tr.object_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('language', 'post_translations') AND tr.object_id IN (9)
		 *     ORDER BY t.name ASC
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (6)
		 *
		 * 1 call to `wp_insert_term()`, 8 queries:
		 * ----------------------------------------
		 *
		 * SELECT  t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND t.name IN ('pll_68e7b3e475f0c') AND tt.parent = '0'
		 *     ORDER BY t.name ASC
		 *
		 * SELECT  t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *     WHERE t.slug IN ('pll_68e7b3e475f0c')
		 *     ORDER BY t.term_id ASC
		 *     LIMIT 1

		 * SELECT  t.term_id
		 *    FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *    WHERE t.name IN ('pll_68e7b3e475f0c')
		 *    ORDER BY t.term_id ASC
		 *    LIMIT 1
		 *
		 * INSERT INTO `wptests_terms` (`name`, `slug`, `term_group`) VALUES ('pll_68e7b3e475f0c', 'pll_68e7b3e475f0c', 0)
		 * SELECT tt.term_taxonomy_id FROM wptests_term_taxonomy AS tt INNER JOIN wptests_terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = 'post_translations' AND t.term_id = 9
		 * INSERT INTO `wptests_term_taxonomy` (`term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES (9, 'post_translations', 'a:3:{s:2:\"en\";i:7;s:2:\"fr\";i:8;s:2:\"de\";i:9;}', 0, 0)
		 * SELECT t.term_id, t.slug, tt.term_taxonomy_id, tt.taxonomy FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON ( tt.term_id = t.term_id ) WHERE t.slug = 'pll_68e7b3e475f0c' AND tt.parent = 0 AND tt.taxonomy = 'post_translations' AND t.term_id < 9 AND tt.term_taxonomy_id != 9
		 * SELECT autoload FROM wptests_options WHERE option_name = 'post_translations_children'
		 *
		 * 3 calls to `wp_set_object_terms()`:
		 * -----------------------------------
		 *
		 * SELECT DISTINCT t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND tr.object_id IN (7)
		 *
		 * SELECT  t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND t.slug IN ('pll_68e7b3e475f0c')
		 *     ORDER BY t.term_id ASC
		 *     LIMIT 1
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (9)
		 * SELECT term_taxonomy_id FROM wptests_term_relationships WHERE object_id = 7 AND term_taxonomy_id = 9
		 * INSERT INTO `wptests_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES (7, 9)
		 * SELECT COUNT(*) FROM wptests_term_relationships WHERE term_taxonomy_id = 9
		 * UPDATE `wptests_term_taxonomy` SET `count` = 1 WHERE `term_taxonomy_id` = 9
		 * SELECT term_id, taxonomy FROM wptests_term_taxonomy WHERE term_taxonomy_id IN (9)
		 *
		 * SELECT DISTINCT t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND tr.object_id IN (8)
		 *
		 * SELECT  t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND t.slug IN ('pll_68e7b3e475f0c')
		 *     ORDER BY t.term_id ASC
		 *     LIMIT 1
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (9)
		 * SELECT term_taxonomy_id FROM wptests_term_relationships WHERE object_id = 8 AND term_taxonomy_id = 9
		 * INSERT INTO `wptests_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES (8, 9)
		 * SELECT COUNT(*) FROM wptests_term_relationships WHERE term_taxonomy_id = 9
		 * UPDATE `wptests_term_taxonomy` SET `count` = 2 WHERE `term_taxonomy_id` = 9
		 * SELECT term_id, taxonomy FROM wptests_term_taxonomy WHERE term_taxonomy_id IN (9)
		 *
		 * SELECT DISTINCT t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN wptests_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND tr.object_id IN (9)
		 *
		 * SELECT  t.term_id
		 *     FROM wptests_terms AS t  INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id
		 *     WHERE tt.taxonomy IN ('post_translations') AND t.slug IN ('pll_68e7b3e475f0c')
		 *     ORDER BY t.term_id ASC
		 *     LIMIT 1
		 *
		 * SELECT t.*, tt.* FROM wptests_terms AS t INNER JOIN wptests_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id IN (9)
		 * SELECT term_taxonomy_id FROM wptests_term_relationships WHERE object_id = 9 AND term_taxonomy_id = 9
		 * INSERT INTO `wptests_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES (9, 9)
		 * SELECT COUNT(*) FROM wptests_term_relationships WHERE term_taxonomy_id = 9
		 * UPDATE `wptests_term_taxonomy` SET `count` = 3 WHERE `term_taxonomy_id` = 9
		 * SELECT term_id, taxonomy FROM wptests_term_taxonomy WHERE term_taxonomy_id IN (9)
		 */
		$this->assertSame( 38, $query_count, 'Number of queries when saving post translations should be 38.' );
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
