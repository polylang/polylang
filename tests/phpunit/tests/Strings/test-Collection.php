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
		$translatable1 = new Translatable( 'Hello', 'Bonjour', 'greeting', 'test' );
		$translatable2 = new Translatable( 'Goodbye', 'Au revoir', 'farewell', 'test' );

		$collection = new Collection( array( $translatable1, $translatable2 ), $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertTrue( $collection->has( $translatable1->get_id() ) );
		$this->assertTrue( $collection->has( $translatable2->get_id() ) );
	}

	public function test_add_translatable() {
		$collection   = new Collection( array(), $this->pll_model->languages->get( 'en' ) );
		$translatable = new Translatable( 'Test', 'Test translation', 'test_name', 'test_context' );

		$this->assertCount( 0, $collection );

		$collection->add( $translatable );

		$this->assertCount( 1, $collection );
		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_add_same_translatable_twice_overwrites() {
		$collection = new Collection( array(), $this->pll_model->languages->get( 'en' ) );
		$translatable1 = new Translatable( 'Same', 'Translation 1', 'same_name', 'test' );
		$translatable2 = new Translatable( 'Same', 'Translation 2', 'same_name', 'test' );

		$this->assertCount( 0, $collection );

		$collection->add( $translatable1 );
		$collection->add( $translatable2 );

		$this->assertCount( 1, $collection );
		$this->assertSame( 'Translation 2', $collection->get( $translatable2->get_id() )->get_translation() );
	}

	public function test_get_existing_translatable() {
		$translatable = new Translatable( 'Test', 'Test translation', 'test_name', 'test_context' );
		$collection   = new Collection( array( $translatable ), $this->pll_model->languages->get( 'en' ) );

		$retrieved = $collection->get( $translatable->get_id() );

		$this->assertInstanceOf( Translatable::class, $retrieved );
		$this->assertSame( $translatable->get_id(), $retrieved->get_id() );
	}

	public function test_get_non_existing_translatable_returns_null() {
		$collection = new Collection( array(), $this->pll_model->languages->get( 'en' ) );

		$this->assertNull( $collection->get( 'non_existing_id' ) );
	}

	public function test_has_returns_true_for_existing_translatable() {
		$translatable = new Translatable( 'Test', 'Test translation', 'test_name', 'test_context' );
		$collection = new Collection( array( $translatable ), $this->pll_model->languages->get( 'en' ) );

		$this->assertTrue( $collection->has( $translatable->get_id() ) );
	}

	public function test_has_returns_false_for_non_existing_translatable() {
		$collection = new Collection( array(), $this->pll_model->languages->get( 'en' ) );

		$this->assertFalse( $collection->has( 'non_existing_id' ) );
	}

	public function test_remove_existing_translatable() {
		$translatable = new Translatable( 'Test', 'Test translation', 'test_name', 'test_context' );
		$collection   = new Collection( array( $translatable ), $this->pll_model->languages->get( 'en' ) );

		$this->assertCount( 1, $collection );

		$collection->remove( $translatable->get_id() );

		$this->assertCount( 0, $collection );
		$this->assertFalse( $collection->has( $translatable->get_id() ) );
	}

	public function test_target_language_returns_correct_language() {
		$collection = new Collection( array(), $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( $this->pll_model->languages->get( 'en' ), $collection->target_language() );
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
			$translatables[] = new Translatable( "String $i", "Translation $i", "name_$i", 'test' );
		}

		$collection = new Collection( $translatables, $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( $count, $collection->count() );
	}

	public function test_collection_is_iterable() {
		$translatable1 = new Translatable( 'First', 'Premier', 'first', 'test' );
		$translatable2 = new Translatable( 'Second', 'Deuxième', 'second', 'test' );

		$collection = new Collection( array( $translatable1, $translatable2 ), $this->pll_model->languages->get( 'en' ) );

		$count = 0;
		foreach ( $collection as $id => $translatable ) {
			$this->assertIsString( $id );
			$this->assertInstanceOf( Translatable::class, $translatable );
			++$count;
		}

		$this->assertSame( 2, $count );
	}
}
