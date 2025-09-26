<?php

use WP_Syntex\Polylang\Options\Business;

class Translated_Table_Test extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );
		$factory->language->create_many( 2 );

		// Translatable custom table.
		require_once PLL_TEST_DATA_DIR . 'translatable.php';
	}

	/**
	 * @ticket #2713 {@see https://github.com/polylang/polylang-pro/issues/2713}
	 */
	public function test_register_custom_table_taxomomy() {
		add_action(
			'pll_model_init',
			function ( $model ) {
				// Register the DB table in Polylang.
				$foo = ( new PLLTest_Translatable( $model, 'foo1' ) )->init();
				$model->translatable_objects->register( $foo );
			}
		);

		$pll_admin = ( new PLL_Context_Admin() )->get();

		$this->assertSame( array( 'foo1_language' ), $pll_admin->options[ Business\Language_Taxonomies::key() ] );
	}
}
