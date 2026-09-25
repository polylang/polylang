<?php

namespace WP_Syntex\Polylang\Tests\Model;

use Exploit_Object_Serialization;
use PLL_Language;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PLLTest_Translatable;
use WP_Term;
use WP_Term_Query;

class Test_Serialized_Term_Description extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	/**
	 * @dataProvider allowed_descriptions_provider
	 *
	 * @param string $description Value that must be kept.
	 * @return void
	 */
	public function test_sanitize_keeps_allowed_types( $description ) {
		$this->assertSame( $description, $this->factory()->pll_model->post->sanitize_description( $description ) );
	}

	public function allowed_descriptions_provider() {
		return array(
			'empty string'        => array( '' ),
			'plain string'        => array( 'not-serialized' ),
			'string'              => array( serialize( 'the_value' ) ),
			'int'                 => array( serialize( 42 ) ),
			'negative int'        => array( serialize( -1 ) ),
			'bool true'           => array( serialize( true ) ),
			'bool false'          => array( serialize( false ) ),
			'list'                => array( serialize( array( 'the_value', 'the_value_2' ) ) ),
			'map'                 => array( serialize( array( 'en' => 12, 'fr' => 34 ) ) ),
			'nested'              => array( serialize( array( 'key' => array( 'subkey' => 'the_value' ) ) ) ),
			'language metas'      => array(
				serialize(
					array(
						'locale'    => 'en_US',
						'rtl'       => false,
						'flag_code' => 'us',
						'active'    => true,
						'fallbacks' => array( 'en_GB' ),
					)
				),
			),
			'translations + sync' => array(
				serialize(
					array(
						'en'   => 12,
						'fr'   => 34,
						'sync' => array(
							'en' => 'fr',
							'fr' => 'fr',
						),
					)
				),
			),
		);
	}

	/**
	 * @dataProvider disallowed_values_provider
	 *
	 * @param mixed $value Value that must be dropped.
	 * @return void
	 */
	public function test_sanitize_drops_disallowed_types( $value ) {
		$description = is_string( $value ) ? $value : serialize( $value );

		$this->assertSame( '', $this->factory()->pll_model->post->sanitize_description( $description ) );
	}

	/**
	 * @dataProvider non_string_descriptions_provider
	 *
	 * @param mixed $description Value that must be dropped.
	 * @return void
	 */
	public function test_sanitize_drops_non_string_values( $description ) {
		$this->assertSame( '', $this->factory()->pll_model->post->sanitize_description( $description ) );
	}

	public function non_string_descriptions_provider() {
		return array(
			'null'   => array( null ),
			'bool'   => array( true ),
			'int'    => array( 42 ),
			'float'  => array( 1.5 ),
			'array'  => array( array( 'en' => 12 ) ),
			'object' => array( (object) array( 'en' => 12 ) ),
		);
	}

	public function disallowed_values_provider() {
		return array(
			'object'         => array( (object) array( 'cmd' => 'id' ) ),
			'nested object'  => array( array( 'en' => 1, 'evil' => (object) array() ) ),
			'exploit object' => array( new Exploit_Object_Serialization() ),
			'null'           => array( null ),
			'null in array'  => array( array( 'en' => 1, 'x' => null ) ),
			'float'          => array( 1.5 ),
			'float in array' => array( array( 'en' => 1.5 ) ),
			'custom object'  => array( 'C:8:"stdClass":0:{}' ),
			'enum'           => array( 'E:7:"Foo:Bar";' ),
			'reference'      => array( 'a:1:{i:0;r:1;}' ),
		);
	}

	public function test_hooks_pll_taxonomies() {
		$post = $this->factory()->pll_model->post;
		$term = $this->factory()->pll_model->term;

		$this->assertNotFalse( has_filter( 'pre_post_translations_description', array( $post, 'sanitize_description' ) ) );
		$this->assertNotFalse( has_filter( 'pre_term_translations_description', array( $term, 'sanitize_description' ) ) );
		$this->assertNotFalse( has_filter( 'pre_language_description', array( $post, 'sanitize_description' ) ) );
		$this->assertNotFalse( has_filter( 'pre_term_language_description', array( $term, 'sanitize_description' ) ) );
	}

	public function test_hooks_pll_taxonomies_on_read() {
		$post = $this->factory()->pll_model->post;
		$term = $this->factory()->pll_model->term;

		$this->assertNotFalse( has_filter( 'get_post_translations', array( $post, 'sanitize_term' ) ) );
		$this->assertNotFalse( has_filter( 'get_term_translations', array( $term, 'sanitize_term' ) ) );
		$this->assertNotFalse( has_filter( 'get_language', array( $post, 'sanitize_term' ) ) );
		$this->assertNotFalse( has_filter( 'get_term_language', array( $term, 'sanitize_term' ) ) );
	}

	public function test_filters_drop_stored_object_for_custom_table_taxonomy() {
		require_once PLL_TEST_DATA_DIR . 'translatable.php';

		$foo      = new PLLTest_Translatable( $this->factory()->pll_model );
		$taxonomy = $foo->get_tax_language();
		$result   = wp_insert_term( 'pll-serialized-object', $taxonomy );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term_id', $result );

		$term_id = (int) $result['term_id'];
		$this->poison_description( $term_id, $taxonomy );

		$this->assert_term_description_is_empty(
			get_term( $term_id, $taxonomy ),
			sprintf( 'get_term() should empty a stored object payload for taxonomy %s.', $taxonomy )
		);

		unregister_taxonomy( $taxonomy );
	}

	public function test_update_post_translations_description_drops_object() {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$term = $this->factory()->pll_model->post->get_object_term( $posts['en'], 'post_translations' );
		$this->assertInstanceOf( WP_Term::class, $term );

		wp_update_term(
			$term->term_id,
			'post_translations',
			array(
				'description' => serialize( new Exploit_Object_Serialization() ),
			)
		);

		$this->assert_term_description_in_db_is_empty( (int) $term->term_id, 'post_translations' );
	}

	public function test_update_language_description_drops_object() {
		$term_id  = self::factory()->language->create( array( 'locale' => 'de_DE_formal' ) );
		$language = $this->factory()->pll_model->languages->get( $term_id );
		$this->assertInstanceOf( PLL_Language::class, $language );

		$term_id = $language->get_tax_prop( 'language', 'term_id' );

		wp_update_term(
			$term_id,
			'language',
			array(
				'description' => serialize( new Exploit_Object_Serialization() ),
			)
		);

		$this->assert_term_description_in_db_is_empty( $term_id, 'language' );
	}

	public function test_insert_term_description_drops_object() {
		$result = wp_insert_term(
			'pll-serialized-object',
			'post_translations',
			array(
				'description' => serialize( new Exploit_Object_Serialization() ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'term_id', $result );
		$this->assert_term_description_in_db_is_empty( (int) $result['term_id'], 'post_translations' );
	}

	public function test_wordpress_term_getters_drop_stored_object() {
		$group   = $this->create_poisoned_translations_group();
		$term_id = (int) $group['term']->term_id;
		$args    = array(
			'taxonomy'   => 'post_translations',
			'include'    => array( $term_id ),
			'hide_empty' => false,
		);

		$get_terms_result = get_terms( $args );
		$this->assertIsArray( $get_terms_result );
		$this->assertCount( 1, $get_terms_result );

		$term_query = new WP_Term_Query( $args );
		$this->assertIsArray( $term_query->terms );
		$this->assertCount( 1, $term_query->terms );

		$terms = array(
			'get_term'         => get_term( $term_id, 'post_translations' ),
			'get_terms'        => reset( $get_terms_result ),
			'WP_Term_Query'    => reset( $term_query->terms ),
			'get_term_by'      => get_term_by( 'slug', $group['term']->slug, 'post_translations' ),
			'get_term_to_edit' => get_term_to_edit( $term_id, 'post_translations' ),
		);

		foreach ( $terms as $getter => $term ) {
			$this->assert_term_description_is_empty(
				$term,
				sprintf( '%s should empty a stored object payload in the description.', $getter )
			);
		}

		$this->assertSame( '', get_term_field( 'description', $term_id, 'post_translations', 'raw' ) );
	}

	public function test_object_term_getters_drop_stored_object() {
		$group   = $this->create_poisoned_translations_group();
		$post_id = $group['posts']['en'];

		$object_terms = wp_get_object_terms( $post_id, 'post_translations' );
		$this->assertIsArray( $object_terms );
		$this->assertCount( 1, $object_terms );

		wp_cache_delete( $post_id, 'post_translations_relationships' );
		update_object_term_cache( array( $post_id ), 'post' );

		$term_lists = array(
			'wp_get_object_terms'  => $object_terms,
			'get_the_terms'        => get_the_terms( $post_id, 'post_translations' ),
			'get_object_term_cache' => get_object_term_cache( $post_id, 'post_translations' ),
		);

		foreach ( $term_lists as $getter => $terms ) {
			$this->assertIsArray( $terms, $getter );
			$this->assertCount( 1, $terms, $getter );
			$this->assert_term_description_is_empty(
				reset( $terms ),
				sprintf( '%s should empty a stored object payload in the description.', $getter )
			);
		}

		$this->assert_term_description_is_empty(
			$this->factory()->pll_model->post->get_object_term( $post_id, 'post_translations' ),
			'PLL_Translatable_Object::get_object_term() should empty a stored object payload in the description.'
		);
	}

	public function test_translations_getters_ignore_stored_object() {
		$group = $this->create_poisoned_translations_group();
		$posts = $group['posts'];
		$term  = $group['term'];

		$this->assertSame( array( 'en' => $posts['en'] ), $this->factory()->pll_model->post->get_translations( $posts['en'] ) );
		$this->assertSame( array(), $this->factory()->pll_model->post->get_translations_from_term_id( (int) $term->term_id ) );
		$this->assertSame( 0, $this->factory()->pll_model->post->get_translation( $posts['en'], 'fr' ) );
	}

	public function test_language_term_drops_stored_object() {
		$language = $this->factory()->pll_model->languages->get( self::factory()->language->create( array( 'locale' => 'de_DE_formal' ) ) );
		$this->assertInstanceOf( PLL_Language::class, $language );

		$term_id = (int) $language->get_tax_prop( 'language', 'term_id' );
		$this->poison_description( $term_id, 'language' );
		$this->factory()->pll_model->languages->clean_cache();

		// `PLL_Language_Factory` needs a locale, which a sanitized description no longer provides: don't rebuild the language here.
		$this->assert_term_description_is_empty(
			get_term( $term_id, 'language' ),
			'get_term() should empty a stored object payload in the description.'
		);
	}

	/**
	 * Creates a translations group holding a serialized object, written directly in database.
	 *
	 * @return array The translated posts and poisoned translations term.
	 *
	 * @phpstan-return array{
	 *     posts: array{en: positive-int, fr: positive-int},
	 *     term: WP_Term
	 * }
	 */
	private function create_poisoned_translations_group(): array {
		$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$term = $this->factory()->pll_model->post->get_object_term( $posts['en'], 'post_translations' );
		$this->assertInstanceOf( WP_Term::class, $term );

		$this->poison_description( (int) $term->term_id, 'post_translations' );

		return compact( 'posts', 'term' );
	}

	/**
	 * Asserts the term description stored in `term_taxonomy` is empty, bypassing `get_{$taxonomy}`.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	private function assert_term_description_in_db_is_empty( int $term_id, string $taxonomy ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT description FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = %s",
				$term_id,
				$taxonomy
			)
		);

		$this->assertIsObject( $row );
		$this->assertSame( '', $row->description );
	}

	/**
	 * Asserts that a term getter returned a term with an empty description.
	 *
	 * @param mixed  $term    Term returned by the getter.
	 * @param string $message Assertion message.
	 * @return void
	 */
	private function assert_term_description_is_empty( $term, string $message ): void {
		$this->assertInstanceOf( WP_Term::class, $term, $message );
		$this->assertSame( '', $term->description, $message );
	}

	/**
	 * Stores a serialized object in a term description, bypassing `pre_{$taxonomy}_description`.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	private function poison_description( int $term_id, string $taxonomy ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'description' => serialize( new Exploit_Object_Serialization() ) ),
			array(
				'term_id'  => $term_id,
				'taxonomy' => $taxonomy,
			)
		);

		clean_term_cache( array( $term_id ), $taxonomy );
	}
}
