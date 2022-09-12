<?php

class Slugs_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function test_term_slugs() {
		$links_model = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		new PLL_Admin_Filters_Term( $pll_admin ); // activate our filters

		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $term_id, 'en' );

		$_POST['term_lang_choice'] = 'fr';
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $term_id, 'fr' );

		$term = get_term( $term_id, 'category' );
		$this->assertEquals( 'test-fr', $term->slug );
	}
}
