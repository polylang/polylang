<?php

namespace WP_Syntex\Polylang\Tests\Strings\Table;

use PLL_MO;
use PLL_Model;
use PLL_Admin;
use PLL_Admin_Strings;
use PLL_Table_String;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Strings\Database_Repository;

/**
 * @group strings
 */
class Test_Prepare_Items extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$options       = self::create_options();
		$model         = new PLL_Model( $options );
		$links_model   = $model->get_links_model();
		$this->pll_env = new PLL_Admin( $links_model );

		Database_Repository::reset();

		foreach ( $this->pll_env->model->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->export_to_db( $language );
		}

		wp_set_current_user( 1 ); // Set admin user.
	}

	private function register_test_strings(): void {
		Database_Repository::register( 'alpha', 'First string', 'Animals', null, false );
		Database_Repository::register( 'beta', 'Second string', 'Animals', null, false );
		Database_Repository::register( 'gamma', 'Third string', 'Plants', null, false );
		Database_Repository::register( 'delta', 'Fourth string', 'Plants', null, false );
		Database_Repository::register( 'epsilon', "Multi\nLine\nString", 'Colors', null, true );
		Database_Repository::register( 'zeta', 'Last string', 'Colors', null, false );
	}

	public function test_prepare_items_basic() {
		$this->register_test_strings();
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 6, $table->items, 'All 6 registered strings should be in items.' );

		foreach ( $table->items as $item ) {
			$this->assertArrayHasKey( 'string', $item, 'Item should have string key.' );
			$this->assertArrayHasKey( 'name', $item, 'Item should have name key.' );
			$this->assertArrayHasKey( 'context', $item, 'Item should have context key.' );
			$this->assertArrayHasKey( 'translations', $item, 'Item should have translations key.' );
			$this->assertArrayHasKey( 'row', $item, 'Item should have row key.' );
			$this->assertArrayHasKey( 'disabled', $item, 'Item should have disabled key.' );
			$this->assertIsArray( $item['translations'], 'Translations should be an array.' );
			$this->assertIsArray( $item['disabled'], 'Disabled should be an array.' );

			// Check each language has a translation entry.
			foreach ( $this->pll_env->model->languages->get_list() as $language ) {
				$this->assertArrayHasKey( $language->slug, $item['translations'], "Item should have translation for {$language->slug}." );
				$this->assertArrayHasKey( $language->slug, $item['disabled'], "Item should have disabled state for {$language->slug}." );
			}
		}
	}

	public function test_prepare_items_filter_by_group() {
		$this->register_test_strings();
		$_GET['group'] = 'Animals';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Only 2 Animals strings should be returned.' );

		foreach ( $table->items as $item ) {
			$this->assertSame( 'Animals', $item['context'], 'All items should have Animals context.' );
		}
	}

	public function test_prepare_items_invalid_group() {
		$this->register_test_strings();
		$_GET['group'] = 'NonExistent';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 6, $table->items, 'Invalid group should show all strings.' );
	}

	public function test_prepare_items_search_by_name() {
		$this->register_test_strings();
		$_GET['s'] = 'alpha';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 1, $table->items, 'Only 1 string with name alpha should be found.' );
		$this->assertSame( 'alpha', array_values( $table->items )[0]['name'], 'Found string should be alpha.' );
	}

	public function test_prepare_items_search_by_string() {
		$this->register_test_strings();
		$_GET['s'] = 'First';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 1, $table->items, 'Only 1 string containing "First" should be found.' );
		$this->assertStringContainsString( 'First', array_values( $table->items )[0]['string'], 'Found string should contain "First".' );
	}

	public function test_prepare_items_search_by_translation() {
		$this->register_test_strings();

		// Add a translation for "First string".
		$fr = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( "Animals\4First string", 'Première chaîne' ) );
		$mo->export_to_db( $fr );

		$_GET['s'] = 'Première';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 1, $table->items, 'String with translation containing "Première" should be found.' );
		$this->assertSame( 'First string', array_values( $table->items )[0]['string'], 'Found string should be "First string".' );
	}

	public function test_prepare_items_search_case_insensitive() {
		$this->register_test_strings();
		$_GET['s'] = 'FIRST';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 1, $table->items, 'Search should be case insensitive.' );
		$this->assertStringContainsString( 'First', array_values( $table->items )[0]['string'], 'Found string should contain "First".' );
	}

	public function test_prepare_items_search_no_results() {
		$this->register_test_strings();
		$_GET['s'] = 'nonexistent';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 0, $table->items, 'No items should be found for nonexistent search.' );
	}

	public function test_prepare_items_sort_by_string_asc() {
		$this->register_test_strings();
		$_GET['orderby'] = 'string';
		$_GET['order']   = 'asc';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$strings = wp_list_pluck( $table->items, 'string' );
		$sorted  = $strings;
		sort( $sorted );

		$this->assertSame( $sorted, array_values( $strings ), 'Strings should be sorted ascending.' );
	}

	public function test_prepare_items_sort_by_string_desc() {
		$this->register_test_strings();
		$_GET['orderby'] = 'string';
		$_GET['order']   = 'desc';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$strings = wp_list_pluck( $table->items, 'string' );
		$sorted  = $strings;
		rsort( $sorted );

		$this->assertSame( $sorted, array_values( $strings ), 'Strings should be sorted descending.' );
	}

	public function test_prepare_items_sort_by_name() {
		$this->register_test_strings();
		$_GET['orderby'] = 'name';
		$_GET['order']   = 'asc';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$names  = wp_list_pluck( $table->items, 'name' );
		$sorted = $names;
		sort( $sorted );

		$this->assertSame( $sorted, array_values( $names ), 'Strings should be sorted by name.' );
	}

	public function test_prepare_items_sort_by_context() {
		$this->register_test_strings();
		$_GET['orderby'] = 'context';
		$_GET['order']   = 'asc';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$contexts = wp_list_pluck( $table->items, 'context' );
		$sorted   = $contexts;
		sort( $sorted );

		$this->assertSame( $sorted, array_values( $contexts ), 'Strings should be sorted by context.' );
	}

	public function test_prepare_items_pagination() {
		$this->register_test_strings();

		// Set per_page to 2.
		$user_id = get_current_user_id();
		update_user_option( $user_id, 'pll_strings_per_page', 2 );

		// First, verify the total count of registered strings.
		$all_strings = ( new Database_Repository( $this->pll_env->model->languages ) )->find_all();
		$this->assertCount( 6, $all_strings, 'Should have exactly 6 registered strings.' );

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Only 2 items per page should be returned.' );

		// Get second page.
		$_GET['paged'] = 2;
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Second page should also have 2 items.' );

		// Get third page.
		$_GET['paged'] = 3;
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Third page should have 2 items.' );

		// Get fourth page (should be empty or have no items beyond the 6 total).
		$_GET['paged'] = 4;
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 0, $table->items, 'Fourth page should be empty.' );
	}

	public function test_prepare_items_pagination_args() {
		$this->register_test_strings();

		$user_id = get_current_user_id();
		update_user_option( $user_id, 'pll_strings_per_page', 2 );

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$pagination = $table->get_pagination_arg( 'total_items' );
		$this->assertSame( 6, $pagination, 'Total items should be 6.' );

		$per_page = $table->get_pagination_arg( 'per_page' );
		$this->assertSame( 2, $per_page, 'Per page should be 2.' );

		$total_pages = $table->get_pagination_arg( 'total_pages' );
		$this->assertSame( 3, $total_pages, 'Total pages should be 3.' );
	}

	public function test_prepare_items_language_filter() {
		$this->register_test_strings();

		// Add translations for French only.
		$fr = $this->pll_env->model->languages->get( 'fr' );
		$mo = new PLL_MO();
		$mo->add_entry( $mo->make_entry( 'First string', 'Première chaîne', 'Animals' ) );
		$mo->export_to_db( $fr );

		// Set language filter to French.
		update_user_meta( get_current_user_id(), 'pll_filter_content', 'fr' );

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		// Items should still contain all 6 strings.
		$this->assertCount( 6, $table->items, 'All strings should be returned.' );

		// But only French translations should be present.
		foreach ( $table->items as $item ) {
			$this->assertArrayHasKey( 'fr', $item['translations'], 'French translation should be present.' );
			$this->assertArrayNotHasKey( 'de', $item['translations'], 'German translation should not be present.' );
			$this->assertArrayNotHasKey( 'en', $item['translations'], 'English translation should not be present.' );
		}
	}

	public function test_prepare_items_multiline_strings() {
		$this->register_test_strings();

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$multiline_item = null;
		foreach ( $table->items as $item ) {
			if ( 'epsilon' === $item['name'] ) {
				$multiline_item = $item;
				break;
			}
		}

		$this->assertNotNull( $multiline_item, 'Multiline string should be found.' );
		$this->assertTrue( $multiline_item['multiline'], 'String should be marked as multiline.' );
		$this->assertStringContainsString( "\n", $multiline_item['string'], 'String should contain newlines.' );
	}

	public function test_prepare_items_combined_group_and_search() {
		$this->register_test_strings();
		$_GET['group'] = 'Animals';
		$_GET['s']     = 'First';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 1, $table->items, 'Only 1 string should match both filters.' );
		$this->assertSame( 'Animals', array_values( $table->items )[0]['context'], 'String should be in Animals group.' );
		$this->assertStringContainsString( 'First', array_values( $table->items )[0]['string'], 'String should contain "First".' );
	}

	public function test_prepare_items_combined_group_and_sort() {
		$this->register_test_strings();
		$_GET['group']   = 'Animals';
		$_GET['orderby'] = 'name';
		$_GET['order']   = 'desc';

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Should have 2 Animals strings.' );

		$names = wp_list_pluck( $table->items, 'name' );
		$this->assertSame( 'beta', array_values( $names )[0], 'First item should be beta (desc order).' );
		$this->assertSame( 'alpha', array_values( $names )[1], 'Second item should be alpha (desc order).' );
	}

	public function test_prepare_items_combined_search_and_pagination() {
		$this->register_test_strings();
		$_GET['s'] = 'string'; // Should match most strings.

		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'pll_strings_per_page', 2 );

		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'First page should have 2 items.' );

		// Total items should be all strings matching search.
		$total = $table->get_pagination_arg( 'total_items' );
		$this->assertGreaterThan( 2, $total, 'Total items should be more than 2.' );
	}

	public function test_prepare_items_no_strings() {
		$table = new PLL_Table_String( $this->pll_env->model->languages );
		$table->prepare_items();

		$this->assertCount( 0, $table->items, 'No items should be returned when no strings are registered.' );
		$this->assertSame( 0, $table->get_pagination_arg( 'total_items' ), 'Total items should be 0.' );
	}
}
