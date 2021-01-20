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

	function test_term_slugs() {
		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang ); // activate our filters

		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $term_id, 'en' );

		$_POST['term_lang_choice'] = 'fr';
		$term_id = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $term_id, 'fr' );

		$term = get_term( $term_id, 'category' );
		$this->assertEquals( 'test-fr', $term->slug );
	}
}
