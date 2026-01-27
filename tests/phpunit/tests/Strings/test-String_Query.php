<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_MO;
use PLL_Model;
use PLL_UnitTestCase;
use Translation_Entry;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Collection;
use WP_Syntex\Polylang\Strings\String_Query;
use WP_Syntex\Polylang\Strings\Database_Repository;

/**
 * @group strings
 */
class String_Query_Test extends PLL_UnitTestCase {
	/**
	 * @var Database_Repository
	 */
	private Database_Repository $repository;

	/**
	 * @var String_Query
	 */
	private String_Query $query;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$this->pll_model = new PLL_Model(
			self::create_options(
				array(
					'default_lang' => 'en',
				)
			)
		);

		$this->repository = new Database_Repository( $this->pll_model->languages );
		$this->query      = new String_Query( $this->repository, $this->pll_model->languages );
	}

	public function tear_down() {
		Database_Repository::reset();

		parent::tear_down();
	}

	public static function wpTearDownAfterClass() {
		Database_Repository::reset();

		parent::wpTearDownAfterClass();
	}

	public function test_get_returns_collection() {
		$result = $this->query->get();

		$this->assertInstanceOf( Collection::class, $result );
	}

	public function test_by_context_filters_strings_by_context() {
		Database_Repository::register( 'string_1', 'source_1', 'context_a' );
		Database_Repository::register( 'string_2', 'source_2', 'context_b' );
		Database_Repository::register( 'string_3', 'source_3', 'context_a' );

		$result = $this->query->by_context( 'context_a' )->get();

		$this->assertCount( 2, $result );
		$this->assertTrue( $result->has( md5( 'source_1context_a' ) ) );
		$this->assertTrue( $result->has( md5( 'source_3context_a' ) ) );
		$this->assertFalse( $result->has( md5( 'source_2context_b' ) ) );
	}

	public function test_by_context_returns_self_for_chaining() {
		$result = $this->query->by_context( 'test_context' );

		$this->assertSame( $this->query, $result );
	}

	/**
	 * @testWith ["apple"]
	 *           ["APPLE"]
	 *           ["App"]
	 *
	 * @param string $fragment The fragment to search for.
	 */
	public function test_by_fragment_searches_in_source_case_insensitive( string $fragment ) {
		Database_Repository::register( 'test_name', 'apple source', 'test_context' );

		$result = $this->query->by_fragment( $fragment )->get();

		$this->assertCount( 1, $result );
	}

	/**
	 * @testWith ["test"]
	 *           ["TEST"]
	 *           ["Test"]
	 *
	 * @param string $fragment The fragment to search for.
	 */
	public function test_by_fragment_searches_in_name_case_insensitive( string $fragment ) {
		Database_Repository::register( 'test_name', 'source text', 'test_context' );

		$result = $this->query->by_fragment( $fragment )->get();

		$this->assertCount( 1, $result );
	}

	public function test_by_fragment_searches_in_translations() {
		Database_Repository::register( 'test_name', 'source text', 'test_context' );

		$mo = new PLL_MO();
		$mo->import_from_db( $this->pll_model->languages->get( 'fr' ) );
		$mo->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'source text',
					'translations' => array( 'French banana translation' ),
					'context'      => 'test_context',
				)
			)
		);
		$mo->export_to_db( $this->pll_model->languages->get( 'fr' ) );

		$result = $this->query->by_fragment( 'banana' )->get();

		$this->assertCount( 1, $result );
	}

	public function test_by_fragment_returns_self_for_chaining() {
		$result = $this->query->by_fragment( 'test' );

		$this->assertSame( $this->query, $result );
	}

	public function test_by_fragment_returns_empty_when_no_match() {
		Database_Repository::register( 'test_name', 'source text', 'test_context' );

		$result = $this->query->by_fragment( 'nonexistent' )->get();

		$this->assertCount( 0, $result );
	}

	/**
	 * @testWith ["name", "asc"]
	 *           ["name", "desc"]
	 *           ["string", "asc"]
	 *           ["string", "desc"]
	 *           ["context", "asc"]
	 *           ["context", "desc"]
	 *
	 * @param string $field The field to sort by.
	 * @param string $order The sort order.
	 */
	public function test_order_by_with_valid_fields_and_orders( string $field, string $order ) {
		Database_Repository::register( 'b_name', 'b_source', 'b_context' );
		Database_Repository::register( 'a_name', 'a_source', 'a_context' );

		$result = $this->query->order_by( $field, $order )->get();
		$items  = iterator_to_array( $result );

		$this->assertCount( 2, $items );

		$first_item  = reset( $items );
		$second_item = next( $items );

		$getter = 'string' === $field ? 'get_source' : 'get_' . $field;

		if ( 'asc' === $order ) {
			$this->assertStringStartsWith( 'a_', $first_item->$getter() );
			$this->assertStringStartsWith( 'b_', $second_item->$getter() );
		} else {
			$this->assertStringStartsWith( 'b_', $first_item->$getter() );
			$this->assertStringStartsWith( 'a_', $second_item->$getter() );
		}
	}

	public function test_order_by_returns_self_for_chaining() {
		$result = $this->query->order_by( 'name', 'asc' );

		$this->assertSame( $this->query, $result );
	}

	/**
	 * @testWith ["ASC"]
	 *           ["DESC"]
	 *           ["Asc"]
	 *           ["Desc"]
	 *
	 * @param string $order The sort order to test.
	 */
	public function test_order_by_normalizes_order_case( string $order ) {
		Database_Repository::register( 'test_name', 'test_source', 'test_context' );

		$result = $this->query->order_by( 'name', $order )->get();

		$this->assertInstanceOf( Collection::class, $result );
	}

	public function test_order_by_defaults_to_asc_for_invalid_order() {
		Database_Repository::register( 'b_name', 'source', 'context' );
		Database_Repository::register( 'a_name', 'source', 'context' );

		$result = $this->query->order_by( 'name', 'invalid' )->get();
		$items  = iterator_to_array( $result );
		$first  = reset( $items );

		$this->assertSame( 'a_name', $first->get_name() );
	}

	public function test_order_by_triggers_doing_it_wrong_for_invalid_field() {
		$this->setExpectedIncorrectUsage( 'WP_Syntex\Polylang\Strings\String_Query::order_by' );

		$this->query->order_by( 'invalid_field' );
	}

	public function test_chaining_filters_and_sorting() {
		Database_Repository::register( 'z_name', 'apple z source', 'context_a' );
		Database_Repository::register( 'a_name', 'apple a source', 'context_a' );
		Database_Repository::register( 'b_name', 'banana source', 'context_b' );

		$result = $this->query
			->by_context( 'context_a' )
			->by_fragment( 'apple' )
			->order_by( 'name', 'asc' )
			->get();

		$items = iterator_to_array( $result );
		$this->assertCount( 2, $items );

		$first = reset( $items );
		$this->assertSame( 'a_name', $first->get_name() );
	}

	public function test_query_resets_state_after_get() {
		Database_Repository::register( 'test_1', 'source_1', 'context_a' );
		Database_Repository::register( 'test_2', 'source_2', 'context_b' );

		$this->query->by_context( 'context_a' )->get();

		$result = $this->query->get();

		$this->assertCount( 2, $result );
	}

	public function test_multiple_fragments_combined() {
		Database_Repository::register( 'test_name', 'apple orange', 'test_context' );
		Database_Repository::register( 'other_name', 'banana', 'test_context' );

		$result = $this->query->by_fragment( 'apple' )->get();

		$this->assertCount( 1, $result );
		$this->assertTrue( $result->has( md5( 'apple orangetest_context' ) ) );
	}

	public function test_order_by_with_defaults() {
		Database_Repository::register( 'z_name', 'source', 'context' );
		Database_Repository::register( 'a_name', 'source', 'context' );

		$result = $this->query->order_by()->get();
		$items  = iterator_to_array( $result );
		$first  = reset( $items );

		$this->assertSame( 'a_name', $first->get_name() );
	}

	public function test_paginate_returns_self_for_chaining() {
		$result = $this->query->paginate( 10, 1 );

		$this->assertSame( $this->query, $result );
	}

	public function test_paginate_returns_correct_page_size() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );
		Database_Repository::register( 'string_3', 'source_3', 'context' );
		Database_Repository::register( 'string_4', 'source_4', 'context' );
		Database_Repository::register( 'string_5', 'source_5', 'context' );

		$result = $this->query->paginate( 2, 1 )->get();

		$this->assertCount( 2, $result );
	}

	public function test_paginate_returns_correct_page() {
		Database_Repository::register( 'a_name', 'a_source', 'context' );
		Database_Repository::register( 'b_name', 'b_source', 'context' );
		Database_Repository::register( 'c_name', 'c_source', 'context' );
		Database_Repository::register( 'd_name', 'd_source', 'context' );
		Database_Repository::register( 'e_name', 'e_source', 'context' );

		$result = $this->query->order_by( 'name', 'asc' )->paginate( 2, 2 )->get();
		$items  = iterator_to_array( $result );

		$this->assertCount( 2, $items );

		$first = reset( $items );
		$this->assertSame( 'c_name', $first->get_name() );
	}

	public function test_paginate_stores_total_count() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );
		Database_Repository::register( 'string_3', 'source_3', 'context' );
		Database_Repository::register( 'string_4', 'source_4', 'context' );
		Database_Repository::register( 'string_5', 'source_5', 'context' );

		$result = $this->query->paginate( 2, 1 )->get();

		$this->assertCount( 2, $result );
		$this->assertSame( 5, $result->get_total() );
	}

	public function test_paginate_with_filters() {
		Database_Repository::register( 'string_1', 'source_1', 'context_a' );
		Database_Repository::register( 'string_2', 'source_2', 'context_a' );
		Database_Repository::register( 'string_3', 'source_3', 'context_b' );
		Database_Repository::register( 'string_4', 'source_4', 'context_a' );
		Database_Repository::register( 'string_5', 'source_5', 'context_a' );

		$result = $this->query
			->by_context( 'context_a' )
			->paginate( 2, 1 )
			->get();

		$this->assertCount( 2, $result );
		$this->assertSame( 4, $result->get_total() );
	}

	public function test_paginate_handles_last_partial_page() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );
		Database_Repository::register( 'string_3', 'source_3', 'context' );

		$result = $this->query->paginate( 2, 2 )->get();

		$this->assertCount( 1, $result );
		$this->assertSame( 3, $result->get_total() );
	}

	public function test_paginate_handles_page_beyond_total() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );

		$result = $this->query->paginate( 10, 5 )->get();

		$this->assertCount( 0, $result );
		$this->assertSame( 2, $result->get_total() );
	}

	public function test_paginate_enforces_minimum_per_page() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );

		$result = $this->query->paginate( 0, 1 )->get();

		$this->assertCount( 1, $result );
	}

	public function test_paginate_enforces_minimum_page() {
		Database_Repository::register( 'a_name', 'a_source', 'context' );
		Database_Repository::register( 'b_name', 'b_source', 'context' );

		$result = $this->query->order_by( 'name', 'asc' )->paginate( 1, 0 )->get();
		$items  = iterator_to_array( $result );
		$first  = reset( $items );

		$this->assertSame( 'a_name', $first->get_name() );
	}

	public function test_pagination_resets_after_get() {
		Database_Repository::register( 'string_1', 'source_1', 'context' );
		Database_Repository::register( 'string_2', 'source_2', 'context' );
		Database_Repository::register( 'string_3', 'source_3', 'context' );

		$this->query->paginate( 1, 1 )->get();
		$result = $this->query->get();

		$this->assertCount( 3, $result );
	}
}
