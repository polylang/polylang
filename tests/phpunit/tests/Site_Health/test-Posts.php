<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Posts_Test extends TestCase {

	public function test_info_returns_posts_without_lang_grouped_by_post_type() {
		$post_no_lang_ids = self::factory()->post->create_many(
			2,
			array( 'post_type' => 'post' )
		);
		$page_no_lang_ids = self::factory()->post->create_many(
			2,
			array( 'post_type' => 'page' )
		);
		$post_en = self::factory()->post->create( array( 'post_type' => 'post', 'lang' => 'en' ) );

		$result       = $this->site_health->info( array() );
		$post_no_lang = $result['pll_warnings']['fields']['post-no-lang']['value'];

		$this->assertSame( array( 'post', 'page' ), array_keys( $post_no_lang ), 'Result should be grouped by post type.' );
		$this->assertSameSets(
			array_map( 'strval', $post_no_lang_ids ),
			explode( ',', $post_no_lang['post'] ),
			'Result should contain posts IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $page_no_lang_ids ),
			explode( ',', $post_no_lang['page'] ),
			'Result should contain pages IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $post_en, $post_no_lang['post'], 'Result should not contain posts that already have a language.' );
	}

	public function test_info_does_not_add_warnings_when_no_posts_are_missing_a_language() {
		self::factory()->post->create(
			array(
				'post_type' => 'post',
				'lang'      => 'en',
			)
		);

		$result = $this->site_health->info( array() );

		$this->assertArrayNotHasKey(
			'post-no-lang',
			$result['pll_warnings']['fields'] ?? array(),
			'Should not report posts without language when all posts have a language.'
		);
	}
}
