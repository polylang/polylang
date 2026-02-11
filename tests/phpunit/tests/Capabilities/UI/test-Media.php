<?php

namespace WP_Syntex\Polylang\Tests\Integration\modules\Capabilities\UI;

use PLL_Model;
use PLL_Admin;
use PLL_CRUD_Terms;
use PLL_CRUD_Posts;
use PLL_Admin_Links;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use PLL_Admin_Filters_Media;
use WP_Syntex\Polylang\Capabilities\Capabilities;
use WP_Syntex\Polylang\Capabilities\User\Creator;

/**
 * Tests for PLL_Admin_Filters_Media::attachment_fields_to_edit().
 *
 * @group capabilities
 */
class Test_Media extends PLL_UnitTestCase {
	/**
	 * @var Capabilities
	 */
	protected $capabilities;

	/**
	 * Array of default options used in test. Override at will.
	 *
	 * @var array
	 */
	protected $options = array(
		'default_lang' => 'en',
	);

	/**
	 * @var ReflectionClass
	 */
	protected $reflection;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$options                        = $this->create_options( $this->options );
		$this->pll_model                = new PLL_Model( $options );
		$links_model                    = $this->pll_model->get_links_model();
		$this->capabilities             = new Capabilities();
		Capabilities::set_user_creator( new Creator() );
		$this->pll_env                  = new PLL_Admin( $links_model );
		$this->pll_env->posts           = new PLL_CRUD_Posts( $this->pll_env );
		$this->pll_env->terms           = new PLL_CRUD_Terms( $this->pll_env );
		$this->pll_env->links           = new PLL_Admin_Links( $this->pll_env );
		$this->pll_env->posts           = new PLL_CRUD_Posts( $this->pll_env );
		$this->pll_env->filters_media   = new PLL_Admin_Filters_Media( $this->pll_env );

		$GLOBALS['polylang'] = $this->pll_env;
		self::require_api();
	}

	/**
	 * @testWith ["upload.php"]
	 *           ["async-upload.php"]
	 *
	 * @param string $pagenow The pagenow value.
	 */
	public function test_returns_language_field_outside_edit_media_panel( string $pagenow ): void {
		$GLOBALS['pagenow'] = $pagenow;

		$attachment = self::factory()->attachment->create();
		$this->pll_model->post->set_language( $attachment, 'en' );

		$fields = $this->pll_env->filters_media->attachment_fields_to_edit( array(), get_post( $attachment ) );

		$this->assertArrayHasKey( 'language', $fields );
		$this->assertSame( 'Language', $fields['language']['label'] );
	}

	public function test_returns_empty_fields_on_edit_media_panel(): void {
		$GLOBALS['pagenow'] = 'post.php';

		$attachment = self::factory()->attachment->create();
		$this->pll_model->post->set_language( $attachment, 'en' );

		$fields = $this->pll_env->filters_media->attachment_fields_to_edit( array(), get_post( $attachment ) );

		$this->assertArrayNotHasKey( 'language', $fields );
	}

	/**
	 * Tests that non-translator users see all languages and dropdown is enabled.
	 *
	 * @testWith ["en"]
	 *           ["fr"]
	 *           ["de"]
	 *
	 * @param string $lang_slug The language of the attachment.
	 */
	public function test_non_translator_sees_all_languages( string $lang_slug ): void {
		$GLOBALS['pagenow'] = 'upload.php';

		$attachment = self::factory()->attachment->create();
		$this->pll_model->post->set_language( $attachment, $lang_slug );

		$fields = $this->pll_env->filters_media->attachment_fields_to_edit( array(), get_post( $attachment ) );
		$html   = $fields['language']['html'];

		$this->assert_languages_in_dropdown( array( 'en-US', 'fr-FR', 'de-DE' ), $html );
		$this->assert_selected_language( $lang_slug, $html );
		$this->assertFalse( $this->is_dropdown_disabled( $html ) );
	}

	public function test_media_without_language_shows_empty_option(): void {
		$GLOBALS['pagenow'] = 'upload.php';

		$attachment = self::factory()->attachment->create();
		// Do not set a language for the attachment.

		$fields = $this->pll_env->filters_media->attachment_fields_to_edit( array(), get_post( $attachment ) );
		$html   = $fields['language']['html'];

		// The first option should be empty.
		$this->assert_first_option_is_empty( $html );
		$this->assertFalse( $this->is_dropdown_disabled( $html ) );
	}

	/**
	 * Asserts that the dropdown contains exactly the expected languages.
	 *
	 * @param string[] $expected_langs Expected language locales.
	 * @param string   $html           The HTML string.
	 */
	protected function assert_languages_in_dropdown( array $expected_langs, string $html ): void {
		// Backward compatibility with WP 6.6 and below.
		if ( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
			$dom = new \DOMDocument();
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

			$langs = array();
			foreach ( $dom->getElementsByTagName( 'option' ) as $option ) {
				$lang = $option->getAttribute( 'lang' );

				if ( '' !== $lang ) {
					$langs[] = $lang;
				}
			}

			$this->assertSameSets( $expected_langs, $langs );

			return;
		}

		$processor = \WP_HTML_Processor::create_fragment( $html );
		$langs     = array();

		while ( $processor->next_tag( 'OPTION' ) ) {
			$lang = $processor->get_attribute( 'lang' );

			if ( null !== $lang ) {
				$langs[] = $lang;
			}
		}

		$this->assertSameSets( $expected_langs, $langs );
	}

	/**
	 * Asserts that the given language is selected.
	 *
	 * @param string $expected_slug The expected selected language slug.
	 * @param string $html          The HTML string.
	 */
	protected function assert_selected_language( string $expected_slug, string $html ): void {
		// Backward compatibility with WP 6.6 and below.
		if ( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
			$dom = new \DOMDocument();
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

			foreach ( $dom->getElementsByTagName( 'option' ) as $option ) {
				if ( ! $option->hasAttribute( 'selected' ) ) {
					continue;
				}

				$this->assertSame( $expected_slug, $option->getAttribute( 'value' ) );

				return;
			}

			$this->fail( 'A language should be selected.' );

			return;
		}

		$processor = \WP_HTML_Processor::create_fragment( $html );
		$found     = false;

		while ( $processor->next_tag( 'OPTION' ) ) {
			if ( null === $processor->get_attribute( 'selected' ) ) {
				continue;
			}

			$found = true;
			$this->assertSame( $expected_slug, $processor->get_attribute( 'value' ) );
			break;
		}

		$this->assertTrue( $found, 'A language should be selected.' );
	}

	/**
	 * Checks if the dropdown is disabled.
	 *
	 * @param string $html The HTML string.
	 * @return bool
	 */
	protected function is_dropdown_disabled( string $html ): bool {
		// Backward compatibility with WP 6.6 and below.
		if ( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
			$dom = new \DOMDocument();
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

			$selects = $dom->getElementsByTagName( 'select' );

			if ( 0 === $selects->length ) {
				return false;
			}

			return $selects->item( 0 )->hasAttribute( 'disabled' );
		}

		$processor = \WP_HTML_Processor::create_fragment( $html );

		if ( ! $processor->next_tag( 'SELECT' ) ) {
			return false;
		}

		return null !== $processor->get_attribute( 'disabled' );
	}

	/**
	 * Asserts that the first option is empty.
	 *
	 * @param string $html The HTML string.
	 */
	protected function assert_first_option_is_empty( string $html ): void {
		// Backward compatibility with WP 6.6 and below.
		if ( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
			$dom = new \DOMDocument();
			$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

			$options = $dom->getElementsByTagName( 'option' );

			$this->assertGreaterThan( 0, $options->length, 'The dropdown should have options.' );
			$this->assertEmpty( $options->item( 0 )->getAttribute( 'value' ), 'The first option should have an empty value.' );

			return;
		}

		$processor = \WP_HTML_Processor::create_fragment( $html );

		$this->assertTrue( $processor->next_tag( 'OPTION' ), 'The dropdown should have options.' );
		$this->assertEmpty( $processor->get_attribute( 'value' ), 'The first option should have an empty value.' );
	}
}
