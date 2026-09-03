<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

class Terms_Test extends TestCase {

	public function test_info_returns_terms_without_lang_grouped_by_taxonomy() {
		$category_no_lang_ids = self::factory()->term->create_many(
			2,
			array( 'taxonomy' => 'category' )
		);
		$post_tag_no_lang_ids = self::factory()->term->create_many(
			2,
			array( 'taxonomy' => 'post_tag' )
		);
		$term_en = self::factory()->term->create( array( 'taxonomy' => 'category', 'lang' => 'en' ) );

		$result        = $this->site_health->info( array() );
		$term_no_lang  = $result['pll_warnings']['fields']['term-no-lang']['value'];

		$this->assertSame( array( 'category', 'post_tag' ), array_keys( $term_no_lang ), 'Result should be grouped by taxonomy.' );
		$this->assertSameSets(
			array_map( 'strval', $category_no_lang_ids ),
			explode( ',', $term_no_lang['category'] ),
			'Result should contain category IDs without language.'
		);
		$this->assertSameSets(
			array_map( 'strval', $post_tag_no_lang_ids ),
			explode( ',', $term_no_lang['post_tag'] ),
			'Result should contain post_tag IDs without language.'
		);
		$this->assertStringNotContainsString( (string) $term_en, $term_no_lang['category'], 'Result should not contain terms that already have a language.' );
	}

	public function test_info_does_not_add_warnings_when_no_terms_are_missing_a_language() {
		self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'lang'     => 'en',
			)
		);

		$result = $this->site_health->info( array() );

		$this->assertArrayNotHasKey(
			'pll_warnings',
			$result,
			'Should not add warnings when no terms are missing a language.'
		);
	}
}
