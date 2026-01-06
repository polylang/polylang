<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_UnitTestCase;
use WP_Syntex\Polylang\Strings\Collection;
use WP_Syntex\Polylang\Strings\Translatable;

/**
 * @group strings
 */
class Collection_Test extends PLL_UnitTestCase {
	public function test_empty_collection_can_be_created() {
		$collection = new Collection();

		$this->assertSame( 0, $collection->count() );
		$this->assertSame( array(), $collection->all() );
	}

	public function test_collection_can_be_created_with_initial_translatables() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );

		$collection = new Collection( array( $translatable1, $translatable2 ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertTrue( $collection->has( $translatable1->get_id() ) );
		$this->assertTrue( $collection->has( $translatable2->get_id() ) );
	}

	public function test_add_should_add_translatable_to_collection() {
		$collection   = new Collection();
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );

		$collection->add( $translatable );

		$this->assertSame( 1, $collection->count() );
		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_add_same_translatable_twice_should_replace_it() {
		$collection   = new Collection();
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );

		$collection->add( $translatable );
		$collection->add( $translatable );

		$this->assertSame( 1, $collection->count() );
	}

	public function test_add_translatables_with_same_string_should_replace_existing() {
		$collection    = new Collection();
		$translatable1 = new Translatable( 'Same String', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'Same String', 'name_two', 'ContextB' );

		$collection->add( $translatable1 );
		$collection->add( $translatable2 );

		// Both have same ID (md5 of string), so only one should exist.
		$this->assertSame( 1, $collection->count() );
		$this->assertSame( 'name_two', $collection->get( $translatable1->get_id() )->get_name() );
	}

	public function test_remove_should_remove_translatable_by_id() {
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$collection->remove( $translatable->get_id() );

		$this->assertSame( 0, $collection->count() );
		$this->assertFalse( $collection->has( $translatable->get_id() ) );
	}

	public function test_remove_with_nonexistent_id_should_do_nothing() {
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$collection->remove( 'nonexistent_id' );

		$this->assertSame( 1, $collection->count() );
		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_remove_on_empty_collection_should_do_nothing() {
		$collection = new Collection();

		$collection->remove( 'any_id' );

		$this->assertSame( 0, $collection->count() );
	}

	public function test_get_should_return_translatable_by_id() {
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$result = $collection->get( $translatable->get_id() );

		$this->assertSame( $translatable, $result );
	}

	public function test_get_with_nonexistent_id_should_return_null() {
		$collection = new Collection();

		$result = $collection->get( 'nonexistent_id' );

		$this->assertNull( $result );
	}

	public function test_has_should_return_true_for_existing_translatable() {
		$translatable = new Translatable( 'Test String', 'test_name', 'TestContext' );
		$collection   = new Collection( array( $translatable ) );

		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_has_should_return_false_for_nonexistent_translatable() {
		$collection = new Collection();

		$this->assertFalse( $collection->has( 'nonexistent_id' ) );
	}

	public function test_all_should_return_all_translatables() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$all = $collection->all();

		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( $translatable1->get_id(), $all );
		$this->assertArrayHasKey( $translatable2->get_id(), $all );
	}

	public function test_all_should_return_empty_array_for_empty_collection() {
		$collection = new Collection();

		$this->assertSame( array(), $collection->all() );
	}

	public function test_count_should_return_correct_count() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );
		$translatable3 = new Translatable( 'String Three', 'name_three', 'ContextC' );
		$collection    = new Collection( array( $translatable1, $translatable2, $translatable3 ) );

		$this->assertSame( 3, $collection->count() );
	}

	public function test_count_should_return_zero_for_empty_collection() {
		$collection = new Collection();

		$this->assertSame( 0, $collection->count() );
	}

	public function test_filter_by_context_should_return_matching_translatables() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );
		$translatable3 = new Translatable( 'String Three', 'name_three', 'ContextA' );
		$collection    = new Collection( array( $translatable1, $translatable2, $translatable3 ) );

		$filtered = $collection->filter_by_context( 'ContextA' );

		$this->assertInstanceOf( Collection::class, $filtered );
		$this->assertSame( 2, $filtered->count() );
		$this->assertTrue( $filtered->has( $translatable1->get_id() ) );
		$this->assertTrue( $filtered->has( $translatable3->get_id() ) );
		$this->assertFalse( $filtered->has( $translatable2->get_id() ) );
	}

	public function test_filter_by_context_should_return_empty_collection_when_no_matches() {
		$translatable = new Translatable( 'Test String', 'test_name', 'ContextA' );
		$collection   = new Collection( array( $translatable ) );

		$filtered = $collection->filter_by_context( 'NonExistentContext' );

		$this->assertInstanceOf( Collection::class, $filtered );
		$this->assertSame( 0, $filtered->count() );
	}

	public function test_filter_by_context_on_empty_collection_should_return_empty_collection() {
		$collection = new Collection();

		$filtered = $collection->filter_by_context( 'AnyContext' );

		$this->assertInstanceOf( Collection::class, $filtered );
		$this->assertSame( 0, $filtered->count() );
	}

	public function test_filter_by_context_should_not_modify_original_collection() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$collection->filter_by_context( 'ContextA' );

		$this->assertSame( 2, $collection->count() );
	}

	public function test_get_contexts_should_return_all_unique_contexts() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB' );
		$translatable3 = new Translatable( 'String Three', 'name_three', 'ContextA' );
		$collection    = new Collection( array( $translatable1, $translatable2, $translatable3 ) );

		$contexts = $collection->get_contexts();

		$this->assertCount( 2, $contexts );
		$this->assertContains( 'ContextA', $contexts );
		$this->assertContains( 'ContextB', $contexts );
	}

	public function test_get_contexts_should_return_empty_array_for_empty_collection() {
		$collection = new Collection();

		$this->assertSame( array(), $collection->get_contexts() );
	}

	public function test_get_contexts_should_return_single_context_when_all_share_same() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'SharedContext' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'SharedContext' );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$contexts = $collection->get_contexts();

		$this->assertCount( 1, $contexts );
		$this->assertContains( 'SharedContext', $contexts );
	}

	public function test_to_array_should_return_array_representation() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextB', true );
		$collection    = new Collection( array( $translatable1, $translatable2 ) );

		$array = $collection->to_array();

		$this->assertCount( 2, $array );
		$this->assertSame(
			array(
				'id'        => md5( 'String One' ),
				'name'      => 'name_one',
				'string'    => 'String One',
				'context'   => 'ContextA',
				'multiline' => false,
			),
			$array[0]
		);
		$this->assertSame(
			array(
				'id'        => md5( 'String Two' ),
				'name'      => 'name_two',
				'string'    => 'String Two',
				'context'   => 'ContextB',
				'multiline' => true,
			),
			$array[1]
		);
	}

	public function test_to_array_should_return_empty_array_for_empty_collection() {
		$collection = new Collection();

		$this->assertSame( array(), $collection->to_array() );
	}

	public function test_collection_operations_should_chain_correctly() {
		$translatable1 = new Translatable( 'String One', 'name_one', 'ContextA' );
		$translatable2 = new Translatable( 'String Two', 'name_two', 'ContextA' );
		$translatable3 = new Translatable( 'String Three', 'name_three', 'ContextB' );

		$collection = new Collection();
		$collection->add( $translatable1 );
		$collection->add( $translatable2 );
		$collection->add( $translatable3 );

		$this->assertSame( 3, $collection->count() );

		$collection->remove( $translatable2->get_id() );

		$this->assertSame( 2, $collection->count() );
		$this->assertFalse( $collection->has( $translatable2->get_id() ) );

		$filtered = $collection->filter_by_context( 'ContextA' );

		$this->assertSame( 1, $filtered->count() );
		$this->assertTrue( $filtered->has( $translatable1->get_id() ) );
	}
}
