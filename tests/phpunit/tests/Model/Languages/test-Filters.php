<?php

namespace WP_Syntex\Polylang\Tests\Model\Languages;

use PLL_Model;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;

class Test_Filters extends PLL_UnitTestCase {
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );
	}

	public function set_up() {
		parent::set_up();

		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$this->pll_model = new PLL_Model( $options );
	}

	public function test_hide_empty_proxy() {
		$this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$languages = $this->pll_model->languages
			->filter( 'hide_empty' )
			->get_list();

		$this->assertCount( 2, $languages );
		$this->assertSame( array( 'en', 'fr' ), wp_list_pluck( $languages, 'slug' ), 'The empty language (German) should be hidden.' );
	}

	public function test_hide_default_proxy() {
		$languages = $this->pll_model->languages
			->filter( 'hide_default' )
			->get_list();

		$this->assertCount( 2, $languages );
		$this->assertSame( array( 'fr', 'de' ), wp_list_pluck( $languages, 'slug' ), 'The default language (English) should be hidden.' );
	}

	public function test_hide_empty_and_hide_default_proxies() {
		$this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$languages = $this->pll_model->languages
			->filter( 'hide_empty' )
			->filter( 'hide_default' )
			->get_list();

		$this->assertCount( 1, $languages );
		$this->assertSame( array( 'fr' ), wp_list_pluck( $languages, 'slug' ), 'The default language (English) and the empty language (German) should be hidden.' );
	}

	public function test_empty_proxy() {
		$languages = $this->pll_model->languages
			->filter( '' )
			->get_list();

		$this->assertCount( 3, $languages );
		$this->assertSame( array( 'en', 'fr', 'de' ), wp_list_pluck( $languages, 'slug' ) );
	}

	public function test_unknown_proxy() {
		$languages = $this->pll_model->languages
			->filter( 'unknown' )
			->get_list();

		$this->assertCount( 3, $languages );
		$this->assertSame( array( 'en', 'fr', 'de' ), wp_list_pluck( $languages, 'slug' ) );
	}

	public function test_hide_empty_with_arguments() {
		$this->factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		$languages_slugs = $this->pll_model->languages
			->filter( 'hide_empty' )
			->get_list( array( 'fields' => 'slug' ) );

		$this->assertCount( 2, $languages_slugs );
		$this->assertSame( array( 'en', 'fr' ), $languages_slugs );
	}
}
