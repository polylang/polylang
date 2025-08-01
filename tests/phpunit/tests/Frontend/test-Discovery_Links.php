<?php

namespace WP_Syntex\Polylang\Tests\Integration\Frontend;

use PLL_UnitTest_Factory;
use PLL_UnitTestCase;
use PLL_Frontend_Trait;
use PLL_Context_Frontend;

class Test_Discovery_Links extends PLL_UnitTestCase {
	use PLL_Frontend_Trait;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$this->frontend = ( new PLL_Context_Frontend(
			array(
				'options' => array(
					'default_lang' => 'en',
				),
				'permalink_structure' => '/%postname%/',
			)
		) )->get();
	}

	public function test_add_current_language_to_oembed_endpoint() {
		self::factory()->post->create(
			array(
				'lang'       => 'fr',
				'post_title' => 'test',
			)
		);

		$this->go_to( home_url( '/fr/test/' ) );

		$this->assertQueryTrue( 'is_single', 'is_singular' );
		$this->assertSame( 'fr', $this->frontend->curlang->slug );

		$expected = '<link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="http://example.org/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.org%2Ffr%2Ftest%2F&#038;lang=fr" /><link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="http://example.org/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.org%2Ffr%2Ftest%2F&#038;format=xml&#038;lang=fr" />';

		if ( version_compare( $GLOBALS['wp_version'], '6.6.0', '<' ) ) {
			// Title attribute added in 6.6.0, @see {https://core.trac.wordpress.org/ticket/59006/}.
			$expected = '<link rel="alternate" type="application/json+oembed" href="http://example.org/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.org%2Ffr%2Ftest%2F&#038;lang=fr" /><link rel="alternate" type="text/xml+oembed" href="http://example.org/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.org%2Ffr%2Ftest%2F&#038;format=xml&#038;lang=fr" />';
		}

		$this->assertSame( $expected, str_replace( "\n", '', get_echo( 'wp_oembed_add_discovery_links' ) ) );
	}
}
