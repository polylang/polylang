<?php

namespace WP_Syntex\Polylang\Tests\Switcher;

use DOMXpath;
use PLL_Model;
use DOMDocument;
use PLL_Frontend;
use PLL_UnitTestCase;
use PLL_UnitTest_Factory;
use WP_Syntex\Polylang\Switcher\Switcher;
use WP_Syntex\Polylang\Switcher\Settings\Settings;

abstract class TestCase extends PLL_UnitTestCase {
	protected static $posts;
	protected $pll_options;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		// Create posts to not trigger 'hide_if_empty'.
		self::$posts = self::factory()->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);

		self::require_api();
	}

	public static function wpTearDownAfterClass() {
		foreach ( self::$posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		parent::wpTearDownAfterClass();
	}

	public function set_up() {
		parent::set_up();

		$this->pll_options = $this->create_options(
			array(
				'default_lang' => 'en',
			)
		);
	}

	protected function init_frontend(): PLL_Frontend {
		$this->pll_model     = new PLL_Model( $this->pll_options );
		$links_model         = $this->pll_model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Frontend( $links_model );
		$GLOBALS['polylang']->init();
		return $GLOBALS['polylang'];
	}

	/**
	 * Returns a new instance of the switcher.
	 *
	 * @param PLL_Frontend $pll_env      Polylang's instance.
	 * @param array        $settings_arr Optional settings.
	 * @return Switcher
	 */
	protected function get_new_switcher( PLL_Frontend $pll_env, array $settings_arr = array() ): Switcher {
		return new Switcher( new Settings( $settings_arr ), $pll_env->links );
	}

	/**
	 * Inits Polylang and returns a new instance of the switcher.
	 *
	 * @param array $settings_arr Optional settings.
	 * @return Switcher
	 */
	protected function get_switcher( array $settings_arr = array() ): Switcher {
		return $this->get_new_switcher( $this->init_frontend(), $settings_arr );
	}

	/**
	 * Returns an instance of `DOMXpath`.
	 *
	 * @param string $html HTML as a string.
	 * @return DOMXpath
	 */
	protected function get_domxpath( string $html ): DOMXpath {
		$doc = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR );
		return new DOMXpath( $doc );
	}
}
