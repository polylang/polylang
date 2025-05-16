<?php

class Test_PLL_MO extends PLL_UnitTestCase {
	use PLL_MO_Trait;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$options         = $this->create_options();
		$model           = new PLL_Model( $options );
		$links_model     = $model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
	}

	public function tear_down() {
		$this->flush_pll_mo_cache( $this->pll_admin->model->languages->get_list() );

		parent::tear_down();
	}

	public function test_cache_is_hit_when_reimporting_from_db() {
		$language = $this->pll_admin->model->languages->get( 'en' );
		$translations = array( 'val' => 'val_en', 'val2' => 'val2_en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		foreach ( $translations as $original => $translation ) {
			$mo->add_entry( $mo->make_entry( $original, $translation ) );
		}
		$mo->export_to_db( $language );

		$db_calls = 0;
		add_filter(
			'get_term_metadata',
			function () use ( &$db_calls ) {
				$db_calls++;
				return null;
			}
		);

		$a_mo = new PLL_MO();
		$a_mo->import_from_db( $language );
		$another_mo = new PLL_MO();
		$another_mo->import_from_db( $language );

		$this->assertEquals( $a_mo, $another_mo );
		$this->assertSame( 1, $db_calls );
	}

	public function test_cache_is_updated_when_deleting_entries() {
		$language = $this->pll_admin->model->languages->get( 'en' );
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$added = $mo->add_entry( $mo->make_entry( 'val', 'val_en' ) );

		$this->assertTrue( $added );

		unset( $mo );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$mo->delete_entry( 'val' );

		unset( $mo );

		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		$this->assertSame( 'val', $mo->translate( 'val' ) );
	}
}
