<?php

class Terms_List_Test extends PLL_UnitTestCase {
	static $editor;

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );

		self::$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	function setUp() {
		parent::setUp();

		wp_set_current_user( self::$editor ); // set a user to pass current_user_can tests
		self::$polylang = new PLL_Admin( self::$polylang->links_model );
		self::$polylang->filters = new PLL_Admin_Filters( self::$polylang ); // To activate the fix_delete_default_category() filter
		self::$polylang->filters_term = new PLL_Admin_Filters_Term( self::$polylang );
	}

	function tearDown() {
		parent::tearDown();

		$_REQUEST = $_GET = $_POST = array();
		unset( $GLOBALS['hook_suffix'], $GLOBALS['current_screen'] );
	}

	function test_term_list_with_admin_language_filter() {
		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'essai' ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'enfant', 'parent' => $fr ) );
		self::$polylang->model->term->set_language( $fr, 'fr' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'test' ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$en = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'child', 'parent' => $en ) );
		self::$polylang->model->term->set_language( $en, 'en' );

		$GLOBALS['taxnow'] = $_REQUEST['taxonomy'] = $_GET['taxonomy'] = 'category'; // WP_Screen tests $_REQUEST, Polylang tests $_GET
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();
		$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );
		self::$polylang->set_current_language();

		// without filter
		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		$this->assertNotFalse( strpos( $list, 'test' ) );
		$this->assertNotFalse( strpos( $list, 'essai' ) );
		$this->assertNotFalse( strpos( $list, 'child' ) );
		$this->assertNotFalse( strpos( $list, 'enfant' ) );

		// the filter is active
		self::$polylang->pref_lang = self::$polylang->filter_lang = self::$polylang->model->get_language( 'en' );
		self::$polylang->set_current_language();

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		$this->assertNotFalse( strpos( $list, 'test' ) );
		$this->assertFalse( strpos( $list, 'essai' ) );
		$this->assertNotFalse( strpos( $list, 'child' ) );
		$this->assertFalse( strpos( $list, 'enfant' ) );
	}

	// bug introduced by WP 4.3 and fixed in v1.8.2
	function test_default_category_in_list_table() {
		$id = $this->factory->term->create( array( 'taxonomy' => 'category' ) ); // a non default category
		$default = get_option( 'default_category' );
		$en = self::$polylang->model->term->get( $default, 'en' );
		$fr = self::$polylang->model->term->get( $default, 'fr' );

		$GLOBALS['taxnow'] = $_REQUEST['taxonomy'] = $_GET['taxonomy'] = 'category'; // WP_Screen tests $_REQUEST, Polylang tests $_GET
		$GLOBALS['hook_suffix'] = 'edit-tags.php';
		set_current_screen();
		$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		$list = ob_get_clean();

		// checkbox only for non default category
		$this->assertFalse( strpos( $list, '"cb-select-' . $en . '"' ) );
		$this->assertFalse( strpos( $list, '"cb-select-' . $fr . '"' ) );
		$this->assertNotFalse( strpos( $list, '"cb-select-' . $id . '"' ) );

		// delete link only for non default category
		$this->assertFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $en . '&amp;' ) );
		$this->assertFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $fr . '&amp;' ) );
		$this->assertNotFalse( strpos( $list, 'edit-tags.php?action=delete&amp;taxonomy=category&amp;tag_ID=' . $id . '&amp;' ) );
	}
}
