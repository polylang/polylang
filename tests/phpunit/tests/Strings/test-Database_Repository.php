<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_MO;
use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Collection;
use WP_Syntex\Polylang\Strings\Translatable;
use WP_Syntex\Polylang\Strings\Database_Repository;

/**
 * @group strings
 */
class Database_Repository_Test extends PLL_UnitTestCase {
	/**
	 * @var Database_Repository
	 */
	private Database_Repository $repository;

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

		$this->repository = new Database_Repository();
	}

	public static function wpTearDownAfterClass() {
		Database_Repository::reset();

		parent::wpTearDownAfterClass();
	}

	public function test_find_all_creates_translatables_with_correct_properties() {
		Database_Repository::register( 'unique_name', 'unique_string', 'unique_context' );

		$collection          = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$string_with_context = 'unique_stringunique_context';
		$id                  = md5( $string_with_context );
		$translatable        = $collection->get( $id );

		$this->assertInstanceOf( Translatable::class, $translatable );
		$this->assertSame( 'unique_string', $translatable->get_source() );
		$this->assertSame( 'unique_name', $translatable->get_name() );
		$this->assertSame( 'unique_context', $translatable->get_context() );
	}

	public function test_register_with_default_context() {
		Database_Repository::register( 'test_name', 'test_string' );

		$collection = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$id = md5( 'test_stringPolylang' ); // `$source . $context`

		$this->assertTrue( $collection->has( $id ) );
	}

	/**
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $multiline The multiline flag value to test.
	 */
	public function test_register_with_multiline( bool $multiline ) {
		Database_Repository::register( 'test_name', 'test_string', 'test_context', null, $multiline );

		$collection   = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$id           = md5( 'test_stringtest_context' );                // `$source . $context`
		$translatable = $collection->get( $id );

		$this->assertSame( $multiline, $translatable->is_multiline() );
	}

	public function test_find_all_returns_collection_with_correct_language() {
		$result = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( $this->pll_model->languages->get( 'en' ), $result->target_language() );
	}

	public function test_find_by_context_returns_collection() {
		Database_Repository::register( 'test_name', 'test_string', 'test_context' );
		Database_Repository::register( 'test_name', 'test_string', 'other_context' );

		$result = $this->repository->find_by_context( 'test_context', $this->pll_model->languages->get( 'en' ) );

		$this->assertInstanceOf( Collection::class, $result );
		$this->assertSame( 1, $result->count() );
		$this->assertTrue( $result->has( md5( 'test_stringtest_context' ) ) );
		$this->assertSame( $this->pll_model->languages->get( 'en' ), $result->target_language() );
	}

	public function test_find_by_context_returns_empty_collection_when_no_matches() {
		$collection = $this->repository->find_by_context( 'non_existing_context', $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( 0, $collection->count() );
	}

	public function test_save_persists_translations() {
		Database_Repository::register( 'test_save', 'test_source', 'test_context' );

		$collection   = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$id           = md5( 'test_sourcetest_context' );
		$translatable = $collection->get( $id );
		$translatable->set_translation( 'nouvelle traduction' );

		$this->repository->save( $collection );

		$mo = new PLL_MO();
		$mo->import_from_db( $this->pll_model->languages->get( 'en' ) );

		$this->assertSame( 'nouvelle traduction', $mo->translate( 'test_source', 'test_context' ) );
	}

	public function test_save_applies_sanitization_filter() {
		Database_Repository::register( 'test_sanitize', 'test_source', 'test_context' );

		$filter_called = false;
		$filter = function ( $translation ) use ( &$filter_called ) {
			$filter_called = true;

			return $translation;
		};
		add_filter( 'pll_sanitize_string_translation', $filter, 5 );

		$collection   = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$id           = md5( 'test_sourcetest_context' );
		$translatable = $collection->get( $id );
		$translatable->set_translation( 'some text' );

		$this->repository->save( $collection );

		$this->assertTrue( $filter_called );
	}

	public function test_register_overwrites_existing_string_with_same_id() {
		Database_Repository::register( 'name_1', 'same_source', 'same_context' );
		Database_Repository::register( 'name_2', 'same_source', 'same_context' );

		$collection   = $this->repository->find_all( $this->pll_model->languages->get( 'en' ) );
		$id           = md5( 'same_sourcesame_context' );
		$translatable = $collection->get( $id );

		$this->assertSame( 'name_2', $translatable->get_name() );
	}
}
