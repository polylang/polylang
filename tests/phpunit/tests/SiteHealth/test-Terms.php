<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Terms_Test extends TestCase {

	public function test_get_term_ids_without_lang_returns_terms_grouped_by_taxonomy() {
		$category_no_lang_ids = $this->create_terms_without_lang( 2, 'category' );
		$post_tag_no_lang_ids = $this->create_terms_without_lang( 2, 'post_tag' );
		$term_en = self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		$result = $this->site_health->get_term_ids_without_lang();

		$this->assertSame( array( 'category', 'post_tag' ), array_keys( $result ), 'Result should be grouped by taxonomy.' );
		$this->assertSameSets(
			array_map( 'strval', $category_no_lang_ids ),
			explode( ',', $result['category'] ),
			'Result should contain category IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $post_tag_no_lang_ids ),
			explode( ',', $result['post_tag'] ),
			'Result should contain post_tag IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $term_en, $result['category'], 'Result should not contain terms that already have a language.' );
	}

	/**
	 * @testWith [-1, 7]
	 *           [0, 7]
	 *
	 * @param int $limit          The limit to pass to get_term_ids_without_lang().
	 * @param int $expected_count The expected number of returned term IDs.
	 */
	public function test_get_term_ids_without_lang_respects_limit( int $limit, int $expected_count ) {
		$category_no_lang_ids = $this->create_terms_without_lang( 7, 'category' );

		$result = $this->site_health->get_term_ids_without_lang( $limit );
		$result_ids = explode( ',', $result['category'] );

		$this->assertCount( $expected_count, $result_ids, "Result should contain $expected_count term IDs for limit $limit." );
		$this->assertEmpty(
			array_diff( $result_ids, $category_no_lang_ids ),
			'All returned IDs should be among the created terms without language.'
		);
	}

	public function test_get_term_ids_without_lang_uses_default_limit_when_no_argument_passed() {
		$this->create_terms_without_lang( 7, 'category' );

		$result = $this->site_health->get_term_ids_without_lang();

		$this->assertCount( 5, explode( ',', $result['category'] ), 'Should default to a limit of 5 when no argument is passed.' );
	}

	public function test_get_term_ids_without_lang_returns_empty_array_when_none_missing() {
		self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		$result = $this->site_health->get_term_ids_without_lang();

		$this->assertEmpty( $result, 'Result should contain an empty array when all terms have lang.' );
	}
}
