<?php

class Posts_List_Test extends PLL_UnitTestCase {

	protected static $en;
	protected static $fr;
	protected static $posts;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$en    = self::factory()->post->create( array( 'lang' => 'en' ) );
		self::$fr    = self::factory()->post->create( array( 'lang' => 'fr' ) );
		self::$posts = self::factory()->post->create_translated( array( 'lang' => 'en' ), array( 'lang' => 'fr' ) );
	}

	public function test_filtered_list_table() {
		new PLL_Context_Admin();

		$_GET['untranslated_in'] = 'fr';
		$list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit.php' ) );
		$list_table->prepare_items();
		$GLOBALS['post'] = $GLOBALS['wp_the_query']->post; // Needed by touch_time.

		ob_start();
		$list_table->display();
		$html = ob_get_clean();
		$doc  = new DomDocument();
		$doc->loadHTML( $html );
		$xpath = new DOMXpath( $doc );

		// The dropdown.
		$options = $xpath->query( './/select[@name="untranslated_in"]/option', $doc );
		$this->assertSame( 4, $options->length, 'Expected the <select> tag to contain 4 <option> tags.' );
		$this->assertSameSets(
			array( '0', 'en', 'fr', 'de' ),
			array(
				$options->item( 0 )->getAttribute( 'value' ),
				$options->item( 1 )->getAttribute( 'value' ),
				$options->item( 2 )->getAttribute( 'value' ),
				$options->item( 3 )->getAttribute( 'value' ),
			)
		);

		// The selected item.
		$selected = $xpath->query( './/select[@name="untranslated_in"]/option[@selected="selected"]', $doc );
		$this->assertEquals( 'fr', $selected->item( 0 )->getAttribute( 'value' ) );

		// The list of posts.
		$rows = $xpath->query( '//tbody/tr', $doc );
		$this->assertSame( 1, $rows->length, 'The list table must contain 1 post' );
		$this->assertSame( 'post-' . self::$en, $rows->item( 0 )->getAttribute( 'id' ) );
	}
}
