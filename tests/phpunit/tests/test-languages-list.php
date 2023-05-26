<?php

class Languages_List_Test extends PLL_UnitTestCase {
	public function set_up() {
		self::create_language( 'en_US' );
		$options                 = PLL_Install::get_default_options();
		$options['default_lang'] = 'en';
		$this->pll_model         = new PLL_Admin_Model( $options );
	}

	public function tear_down() {
		self::delete_all_languages();
	}

	/**
	 * @ticket #1691
	 * @see https://github.com/polylang/polylang-pro/issues/1691.
	 */
	public function test_ghost_language() {
		// Create an ghost language.
		wp_insert_term( 'Casper', 'term_language', array( 'slug' => 'falang_casp' ) );

		$this->pll_model->clean_languages_cache();
		$list = @$this->pll_model->get_languages_list(); // Supress notices to let the assertion do its job.

		$this->assertCount( 1, $list, 'There should be only one language.' );

		$list = wp_list_pluck( $list, 'slug' );

		$this->assertSameSets( array( 'en' ), $list, 'The language should be English.' );
	}

	public function test_missing_term_language() {
		// Delete the `term_language` term.
		$en = $this->pll_model->get_language( 'en' );
		wp_delete_term( $en->get_tax_prop( 'term_language', 'term_id' ), 'term_language' );

		$this->pll_model->clean_languages_cache();
		$list = $this->pll_model->get_languages_list();

		$this->assertCount( 1, $list, 'There should still be one language.' );

		$list = wp_list_pluck( $list, 'slug' );

		$this->assertSameSets( array( 'en' ), $list, 'The remaining language should be English.' );
	}
}
