<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class BrowserPreferredLanguageContext implements Context {

	/**
	 * @BeforeSuite
	 */
	public static function prepare_for_suite() {
		require_once __DIR__ . '/../../phpunit/includes/bootstrap.php';
	}

	/**
	 * @BeforeFeature
	 */
	public static function prepare_for_feature() {
		PLL_UnitTestCase::wpSetUpBeforeClass( new WP_UnitTest_Factory() );
	}

	/**
	 * Initializes context.
	 *
	 * Every scenario gets its own context instance.
	 * You can also pass arbitrary arguments to the
	 * context constructor through behat.yml.
	 */
	public function __construct() {
		$this->test_case = new PLL_UnitTestCase();
	}

	/**
	 * @Given /^I chose ((?:[-_a-zA-Z]+(?:, )?)+)(?: \(in this order\))? as my preferred browsing languages?$/
	 * @param string[] $language_codes Language codes as defined by IETF's BCP 47 {@see https://tools.ietf.org/html/bcp47#section-2.1}
	 */
	public function i_chose_my_preferred_browsing_languages( $language_codes ) {
		$language_codes = array_map( 'trim', explode( ',', $language_codes ) );
		$accept_languages_header = '';
		$languages_count = count( $language_codes );
		for ( $i = 0; $i < $languages_count; $i++ ) {
			if ( $i > 0 ) {
				$accept_languages_header .= ',';
			}
			$accept_languages_header .= $language_codes[ $i ] . ';q=' . ( 10 - $i ) / 10;
		}
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept_languages_header;
	}

	/**
	 * @Given /^a webpage exists in ((?:[-_a-zA-Z]+(?:, )?)+) languages?$/
	 * @param string[] $language_codes Language codes as defined by IETF's BCP 47 {@see https://tools.ietf.org/html/bcp47#section-2.1}
	 */
	public function a_webpage_exists_in_languages( $language_codes ) {
		$language_codes = array_map( 'trim', explode( ',', $language_codes ) );

		foreach ( $language_codes as $language_code ) {
			// $language_slug = strtolower( $language_code );
			$language_slug = PLL_UnitTestCase::create_language( Locale::canonicalize( $language_code )/*, array ( 'slug' => $language_slug )*/ );

			$post_id = $this->test_case->factory->post->create();
			PLL_UnitTestCase::$polylang->model->post->set_language( $post_id, $language_slug );
		}
	}

	/**
	 * @When I visit the webpage for the first time
	 */
	public function i_visit_the_webpage_for_the_firstT_time() {
		// TODO: define step
	}

	/**
	 * @Then /^I should be served this page in ([-_a-zA-Z]+) language$/
	 * @param string $language_code Language codes as defined by IETF's BCP 47 {@see https://tools.ietf.org/html/bcp47#section-2.1}
	 */
	public function i_should_be_served_this_page_in_language( $language_code ) {
		$choose_lang = new PLL_Choose_Lang_Url( PLL_UnitTestCase::$polylang );

		PLL_UnitTestCase::assertEquals( $language_code, $choose_lang->get_preferred_browser_language() );
	}
}
