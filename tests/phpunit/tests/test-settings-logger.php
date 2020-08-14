<?php


class PLL_Settings_Logger_Test extends PLL_UnitTestCase
{
	/**
	 * @var PLL_Settings_Logger
	 */
	private $logger;
	private $setting_name;

	public function setUp()
	{
		parent::setUp();

		$this->setting_name = 'test-setting';
		$this->logger =  new PLL_Settings_Logger($this->setting_name);
	}

	public function tearDown()
	{
		global $wp_settings_errors;

		$wp_settings_errors = array();
	}

	public function test_error_message() {
		$test_message = 'This is a test error';

		$this->logger->error($test_message);

		$settings_errors = get_settings_errors($this->setting_name);
		$this->assertEquals( 1, count( $settings_errors ) );
		$this->assertEquals( $test_message, $settings_errors[0]['message'] );
		$this->assertEquals( 'test-setting_error', $settings_errors[0]['code'] );
		$this->assertEquals( 'error', $settings_errors[0]['type'] );
	}

	public function test_message_with_number_placeholder() {
		$test_message = new PLL_Settings_Message_Incremental(
			function( $args ) {
				return _n('{count} string translation updated.', '{count} strings translations updated.', $args['count'], 'polylang');
			}
		);
		$context = array(
			'count' => 1
		);

		$this->logger->log( 'notice', $test_message, $context );

		$settings_errors = get_settings_errors();
		$this->assertEquals( 1, count( $settings_errors ) );
		$this->assertEquals( '1 string translation updated.', $settings_errors[0]['message'] );
	}

	public function test_increment_message_placeholder() {
		$test_message = new PLL_Settings_Message_Incremental(
			function( $args ) {
				return sprintf(
					_n( '%d string translation updated.', '%d strings translations updated.', $args['count'], 'polylang'),
					$args['count']
				);
			}
		);
		$context = array( 'code' => 'pll_strings_translations_updated', 'count' => 1 );

		$this->logger->log( 'notice', $test_message, $context );

		$second_test_message = new PLL_Settings_Message_Incremental(
			function( $args ) {
				return _n( 'Second message doesn\'t matter ...', ' ... only the context code parameter needs to match.', $args['count'], 'polylang');
			}
		);

		$this->logger->notice( $second_test_message, $context );

		$settings_errors = get_settings_errors( $this->setting_name );
		$this->assertEquals( 1, count( $settings_errors ) );
		$this->assertEquals( '2 strings translations updated.', $settings_errors[0]['message']);
	}

	public function test_set_two_different_log_messages() {
		$test_message_1 = new PLL_Settings_Message_Incremental(
			function( $args ) {
				return _n( '%d string translation updated.', '%d strings translations updated.', $args['count'], 'polylang');
			}
		);
		$context_1 = array(
			'code'  => 'pll_strings_translations_updated',
			'count' => 1,
		);

		$this->logger->notice( $test_message_1, $context_1 );

		$test_message_2 = new PLL_Settings_Message_Incremental(
			function( $args ) {
				return _n( '%d string translation updated.', '%d strings translations updated.', $args['count'], 'polylang');
			}
		);
		$context_2 = array(
			'code'  => 'pll_rest_strings_translations_updated',
			'count' => 1,
		);

		$this->logger->notice( $test_message_2, $context_2 );

		$settings_errors = get_settings_errors();
		$this->assertEquals( 2, count( $settings_errors ) );
		$this->assertNotEquals( $settings_errors[0]['code'], $settings_errors[1]['code']);
		$this->assertEquals( $settings_errors[0]['message'], $settings_errors[1]['message']);
		$this->assertEquals( $settings_errors[0]['type'], $settings_errors[1]['type']);
		$this->assertEquals( $settings_errors[0]['setting'], $settings_errors[1]['setting']);
	}
}
