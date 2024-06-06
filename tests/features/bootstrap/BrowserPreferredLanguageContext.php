<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

require_once __DIR__ . '/../../phpunit/includes/bootstrap.php';

/**
 * Defines application features from the specific context.
 */
class BrowserPreferredLanguageContext extends PLL_UnitTestCase implements Context {

	/**
	 * @BeforeFeature
	 */
	public static function prepare_for_feature() {
		self::setUpBeforeClass();
	}

	/**
	 * @AfterFeature
	 */
	public static function clean_after_feature() {
		self::tearDownAfterClass();
	}

	/**
	 * @BeforeScenario
	 */
	public function prepare_for_scenario() {
		$this->set_up();
	}

	/**
	 * @AfterScenario
	 */
	public function clean_after_scenario() {
		self::delete_all_languages();
		$this->tear_down();
	}

	/**
	 * @Given /^my website has content in.*([a-z]{2}-[A-Z]{2}).*?(?:with the slug ([a-z]{2}-[a-z]{2}))?$/
	 * @param string $language_code Language tag as defined by IETF's BCP 47 {@see https://tools.ietf.org/html/bcp47#section-2.1}
	 * @param string $language_slug Optional. User's custom slug for given language.
	 */
	public function my_website_has_content_in( $language_code, $language_slug = '' ) {
		$args = empty( $language_slug ) ? array() : array( 'slug' => $language_slug );
		self::create_language( Locale::canonicalize( $language_code ), $args );

		$post_id = self::factory()->post->create();

		$default_slug = explode( '-', $language_code )[0];
		self::$model->post->set_language( $post_id, empty( $language_slug ) ? $default_slug : $language_slug );
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
	 * @When I visit my website's homepage for the first time
	 */
	public function i_visit_my_website_homepage_for_the_first_time() {
		// TODO: define step
	}

	/**
	 * @Then /^Polylang will remember.*([a-z]{2}-[A-Z]{2}).*as my preferred browsing language$/
	 * @param string $language_code Language codes as defined by IETF's BCP 47 {@see https://tools.ietf.org/html/bcp47#section-2.1}
	 */
	public function polylang_will_remember( $language_code ) {
		self::$model->clean_languages_cache();

		$polylang = new stdClass();
		$polylang->model = self::$model;
		$choose_lang = new PLL_Choose_Lang_Url( $polylang );

		$preferred_browser_language = $choose_lang->get_preferred_browser_language();
		$preferred_locale = self::$model->get_language( $preferred_browser_language )->locale;
		$expected_locale = Locale::canonicalize( $language_code );
		self::assertEquals( $expected_locale, $preferred_locale, "{$preferred_locale} does not match {$expected_locale}" );
	}
}
