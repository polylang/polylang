<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Collection;
use WP_Syntex\Polylang\Strings\Translatable;

/**
 * @group strings
 */
class Collection_Test extends PLL_UnitTestCase {
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
	}

	public function test_constructor_with_translatables() {
		$translatable1 = new Translatable( 'Hello', 'greeting', 'test' );
		$translatable2 = new Translatable( 'Goodbye', 'farewell', 'test' );

		$collection = new Collection( array( $translatable1, $translatable2 ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertTrue( $collection->has( $translatable1->get_id() ) );
		$this->assertTrue( $collection->has( $translatable2->get_id() ) );
	}

	public function test_add_translatable() {
		$collection   = new Collection( array() );
		$translatable = new Translatable( 'Test', 'test_name', 'test_context' );

		$this->assertCount( 0, $collection );

		$collection->add( $translatable );

		$this->assertCount( 1, $collection );
		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_add_same_translatable_twice_overwrites() {
		$collection = new Collection( array() );
		$translatable1 = new Translatable( 'Same', 'same_name', 'test' );
		$translatable2 = new Translatable( 'Same', 'same_name', 'test' );

		$this->assertCount( 0, $collection );

		$collection->add( $translatable1 );
		$collection->add( $translatable2 );

		$this->assertCount( 1, $collection );
		// Both should have the same ID since they have the same source and context
		$this->assertSame( $translatable1->get_id(), $translatable2->get_id() );
	}

	public function test_get_existing_translatable() {
		$translatable = new Translatable( 'Test', 'test_name', 'test_context' );
		$collection   = new Collection( array( $translatable ) );

		$retrieved = $collection->get( $translatable->get_id() );

		$this->assertInstanceOf( Translatable::class, $retrieved );
		$this->assertSame( $translatable->get_id(), $retrieved->get_id() );
	}

	public function test_get_non_existing_translatable_returns_null() {
		$collection = new Collection( array() );

		$this->assertNull( $collection->get( 'non_existing_id' ) );
	}

	public function test_has_returns_true_for_existing_translatable() {
		$translatable = new Translatable( 'Test', 'test_name', 'test_context' );
		$collection = new Collection( array( $translatable ) );

		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_has_returns_false_for_non_existing_translatable() {
		$collection = new Collection( array() );

		$this->assertFalse( $collection->has( 'non_existing_id' ) );
	}

	public function test_remove_existing_translatable() {
		$translatable = new Translatable( 'Test', 'test_name', 'test_context' );
		$collection   = new Collection( array( $translatable ) );

		$this->assertCount( 1, $collection );

		$collection->remove( $translatable->get_id() );

		$this->assertCount( 0, $collection );
		$this->assertFalse( $collection->has( $translatable->get_id() ) );
	}

	/**
	 * @testWith [0]
	 *           [1]
	 *           [3]
	 *
	 * @param int $count The number of translatables to add to the collection.
	 */
	public function test_count_returns_correct_number( int $count ) {
		$translatables = array();

		for ( $i = 0; $i < $count; ++$i ) {
			$translatables[] = new Translatable( "String $i", "name_$i", 'test' );
		}

		$collection = new Collection( $translatables );

		$this->assertSame( $count, $collection->count() );
	}

	public function test_collection_is_iterable() {
		$translatable1 = new Translatable( 'First', 'first', 'test' );
		$translatable2 = new Translatable( 'Second', 'second', 'test' );

		$collection = new Collection( array( $translatable1, $translatable2 ) );

		$count = 0;
		foreach ( $collection as $id => $translatable ) {
			$this->assertIsString( $id );
			$this->assertInstanceOf( Translatable::class, $translatable );
			++$count;
		}

		$this->assertSame( 2, $count );
	}

	public function test_get_total_returns_count_when_not_set() {
		$translatable1 = new Translatable( 'First', 'first', 'test' );
		$translatable2 = new Translatable( 'Second', 'second', 'test' );

		$collection = new Collection( array( $translatable1, $translatable2 ) );

		$this->assertSame( 2, $collection->get_total() );
	}

	public function test_set_total_and_get_total() {
		$translatable1 = new Translatable( 'First', 'first', 'test' );

		$collection = new Collection( array( $translatable1 ) );
		$collection->set_total( 10 );

		$this->assertSame( 1, $collection->count() );
		$this->assertSame( 10, $collection->get_total() );
	}
}
