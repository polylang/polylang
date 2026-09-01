<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Posts_Test extends TestCase {

	public function test_get_post_ids_without_lang_returns_posts_grouped_by_post_type() {
		$post_no_lang_ids = $this->create_posts_without_lang( 2, 'post' );
		$page_no_lang_ids = $this->create_posts_without_lang( 2, 'page' );
		$post_en = self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

		$result = $this->site_health->get_post_ids_without_lang();

		$this->assertSame( array( 'post', 'page' ), array_keys( $result ), 'Result should be grouped by post type.' );
		$this->assertSameSets(
			array_map( 'strval', $post_no_lang_ids ),
			explode( ',', $result['post'] ),
			'Result should contain posts IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $page_no_lang_ids ),
			explode( ',', $result['page'] ),
			'Result should contain pages IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $post_en, $result['post'], 'Result should not contain posts that already have a language.' );
	}

	/**
	 * @testWith [-1, 7]
	 *           [2, 2]
	 *           [0, 7]
	 *
	 * @param int $limit          The limit to pass to get_post_ids_without_lang().
	 * @param int $expected_count The expected number of returned post IDs.
	 */
	public function test_get_post_ids_without_lang_respects_limit( int $limit, int $expected_count ) {
		$post_no_lang_ids = $this->create_posts_without_lang( 7, 'post' );

		$result = $this->site_health->get_post_ids_without_lang( $limit );
		$result_ids = explode( ',', $result['post'] );

		$this->assertCount( $expected_count, $result_ids, "Result should contain $expected_count post IDs for limit $limit." );
		$this->assertEmpty(
			array_diff( $result_ids, $post_no_lang_ids ),
			'All returned IDs should be among the created posts without language.'
		);
	}

	public function test_get_post_ids_without_lang_uses_default_limit_when_no_argument_passed() {
		$this->create_posts_without_lang( 7, 'post' );

		$result = $this->site_health->get_post_ids_without_lang();

		$this->assertCount( 5, explode( ',', $result['post'] ), 'Should default to a limit of 5 when no argument is passed.' );
	}

	public function test_get_post_ids_without_lang_returns_empty_array_when_none_missing() {
		self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

		$result = $this->site_health->get_post_ids_without_lang();

		$this->assertEmpty( $result, 'Result should contain an empty array when all posts have lang.' );
	}
}
