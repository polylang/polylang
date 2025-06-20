<?php

class Terms_List_Test extends PLL_UnitTestCase {
	protected static $editor;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$editor ); // Set a user to pass current_user_can() tests.

		$links_model = self::$model->get_links_model();
		$this->pll_admin = new PLL_Admin( $links_model );
		$this->pll_admin->filters = new PLL_Admin_Filters( $this->pll_admin ); // To activate the fix_delete_default_category() filter.
		$this->pll_admin->terms = new PLL_CRUD_Terms( $this->pll_admin );
	}

	public function test_term_list_with_admin_language_filter() {
		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$model->term->set_language( $fr, 'fr' );

		$fr = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'enfant', 'parent' => $fr ) );
		self::$model->term->set_language( $fr, 'fr' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$model->term->set_language( $en, 'en' );

		$en = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'child', 'parent' => $en ) );
		self::$model->term->set_language( $en, 'en' );

		// WP_Screen tests $_REQUEST, Polylang tests $_GET.
		$_GET['taxonomy']       = 'category';
		$_REQUEST['taxonomy']   = 'category';
		$GLOBALS['taxnow']      = 'category';
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();
		$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );
		$this->pll_admin->set_current_language();

		// Without filter.
		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		$this->assertNotFalse( strpos( $list, 'test' ) );
		$this->assertNotFalse( strpos( $list, 'essai' ) );
		$this->assertNotFalse( strpos( $list, 'child' ) );
		$this->assertNotFalse( strpos( $list, 'enfant' ) );

		// The filter is active.
		$this->pll_admin->filter_lang = self::$model->get_language( 'en' );
		$this->pll_admin->pref_lang   = $this->pll_admin->filter_lang;
		$this->pll_admin->set_current_language();

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		$this->assertNotFalse( strpos( $list, 'test' ) );
		$this->assertFalse( strpos( $list, 'essai' ) );
		$this->assertNotFalse( strpos( $list, 'child' ) );
		$this->assertFalse( strpos( $list, 'enfant' ) );
	}
}
