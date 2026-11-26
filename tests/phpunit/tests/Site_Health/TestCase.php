<?php

namespace WP_Syntex\Polylang\Tests\Site_Health;

use ReflectionClass;
use ReflectionProperty;
use WP_Debug_Data;
use WP_Error;
use PLL_Admin;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PLL_Admin_Site_Health;
use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;

abstract class TestCase extends PLL_UnitTestCase {

	/**
	 * @var PLL_Admin_Site_Health
	 */
	protected $site_health;

	/**
	 * @var PLL_Admin
	 */
	protected $pll_admin;

	/**
	 * @var array
	 */
	protected $pre_existing_data;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$links_model            = self::$model->get_links_model();
		$this->pll_admin        = new PLL_Admin( $links_model );
		$this->site_health      = new PLL_Admin_Site_Health( $this->pll_admin );

		// Assign a language to WordPress' default category ("Uncategorized"), so it doesn't interfere with tests checking terms without a language.
		$this->pll_admin->model->term->set_language( (int) get_option( 'default_category' ), 'en' );

		// Prevent the external WordPress.org request performed by WP_Debug_Data,
		// which is unrelated to the behavior tested here.
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error(
					'test_http_request',
					'HTTP request disabled for this test.'
				);
			}
		);
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';

		// For tests that's checking nothing pre-existent is erased.
		$this->pre_existing_data = array(
			'label'       => 'Title of this data',
			'description' => 'Description',
			'fields'      => array(
				'name' => array(
					'label' => 'Name',
					'value' => 'Field name',
				),
			),
		);
		add_filter(
			'debug_information',
			function ( $debug_info ) {
				$debug_info['pre_existing_data'] = $this->pre_existing_data;

				return $debug_info;
			}
		);
	}

	/**
	 * Configures WordPress to use a static front page.
	 *
	 * @param int    $page ID of the page to use as front page.
	 * @param string $show Value for 'show_on_front'. Default 'page'.
	 * @return void
	 */
	protected function set_page_on_front( int $page, string $show = 'page' ): void {
		update_option( 'show_on_front', $show );
		update_option( 'page_on_front', $page );
	}

	/**
	 * Returns the full Site Health debug information.
	 *
	 * @return array
	 */
	protected function get_debug_info(): array {
		return WP_Debug_Data::debug_data();
	}

	/**
	 * Returns the Polylang options fields from Site Health.
	 *
	 * @return array
	 */
	protected function get_pll_options_fields(): array {
		$debug_info = $this->get_debug_info();

		$this->assertArrayHasKey( 'pll_options', $debug_info, 'Debug information should contain pll_options.' );
		$this->assertArrayHasKey( 'fields', $debug_info['pll_options'], 'pll_options should contain fields.' );

		return $debug_info['pll_options']['fields'];
	}

	/**
	 * Asserts pre-existing debug data was not modified by Polylang filters.
	 *
	 * @param array $debug_info Full debug information array.
	 * @return void
	 */
	protected function assert_pre_existing_data_preserved( array $debug_info ): void {
		$this->assertSame(
			$this->pre_existing_data,
			$debug_info['pre_existing_data'] ?? array(),
			'Pre-existing data should be preserved unchanged.'
		);
	}

	/**
	 * Asserts a single Polylang option field in Site Health.
	 *
	 * @param array  $fields          Option fields from debug data.
	 * @param string $key             Field key.
	 * @param string $label           Expected field label.
	 * @param mixed  $expected_value  Expected field value.
	 * @return void
	 */
	protected function assert_option_field( array $fields, string $key, string $label, $expected_value ): void {
		$this->assertArrayHasKey( $key, $fields, "Field \"$key\" should be present." );
		$this->assertSame( $label, $fields[ $key ]['label'], "Label for \"$key\" should match." );
		$this->assertSame( $expected_value, $fields[ $key ]['value'], "Value for \"$key\" should match." );
	}

	/**
	 * Enables the "language set from content" URL mode in settings.
	 *
	 * @return void
	 */
	protected function enable_language_from_content(): void {
		update_option( 'pll_language_from_content_available', 'yes' );
	}

	/**
	 * Sets an option instance value via reflection (bypasses schema validation).
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Value to set on the option instance.
	 * @return void
	 */
	protected function set_option_value_via_reflection( string $key, $value ): void {
		$options    = $this->pll_admin->model->options;
		$reflection = new ReflectionClass( Options::class );
		$property   = $reflection->getProperty( 'options' );

		version_compare( PHP_VERSION, '8.1', '<' ) && $property->setAccessible( true );

		$blog_id = get_current_blog_id();
		/** @var array $all_options */
		$all_options = $property->getValue( $options );

		$this->assertArrayHasKey( $key, $all_options[ $blog_id ], "Option \"$key\" should be registered." );
		$this->assertInstanceOf( Abstract_Option::class, $all_options[ $blog_id ][ $key ] );

		$value_reflection = new ReflectionProperty( Abstract_Option::class, 'value' );

		version_compare( PHP_VERSION, '8.1', '<' ) && $value_reflection->setAccessible( true );

		$value_reflection->setValue( $all_options[ $blog_id ][ $key ], $value );
	}
}
