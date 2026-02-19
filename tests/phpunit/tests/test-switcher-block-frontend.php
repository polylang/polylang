<?php

use WP_Syntex\Polylang\Blocks\Language_Switcher\Standard;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

class Switcher_Block_Frontend_Test extends PLL_UnitTestCase {

	const PLL_SWITCHER_BLOCKS_DIR = PLL_TEST_DATA_DIR . 'fixtures/language-switcher-blocks/';

	private $switcher_block;
	private $navigation_block;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Model( $options );
		$links_model = new PLL_Links_Default( $model );
		$polylang = new PLL_Frontend( $links_model );

		// Mock the links to always return the same values
		$polylang->links = $this->getMockBuilder( PLL_Frontend_Links::class )
			->setConstructorArgs( array( &$polylang ) )
			->setMethods( array( 'get_translation_url' ) )
			->getMock();
		$polylang->links->method( 'get_translation_url' )
			->willReturnCallback(
				function ( $language ) use ( $options ) {
					return $language->slug === $options['default_lang'] ?
					'http://example.org/' :
					'http://example.org/' . $language->slug;
				}
			);

		$this->switcher_block   = ( new Standard\Block( $polylang ) )->init();
		$this->navigation_block = ( new Navigation\Block( $polylang ) )->init();

		// Need to register the block types
		do_action( 'init' );
	}

	public function tear_down() {
		WP_Block_Type_Registry::get_instance()->unregister( 'polylang/language-switcher' );
		WP_Block_Type_Registry::get_instance()->unregister( 'polylang/navigation-language-switcher' );

		parent::tear_down();
	}

	/**
	 * @dataProvider switcher_options_provider
	 * @param array  $options A list of options to pass to the switcher (through the block attributes).
	 * @param array  $context Context inherited from the core/navigation block.
	 * @param string $expected The name of a file containing the expected result.
	 */
	public function test_render_polylang_navigation_switcher_block( $options, $context, $expected ) {
		global $wp_version;

		if ( version_compare( $wp_version, '6.8-alpha', '<' ) ) {
			$this->markTestSkipped( 'Test on navigation language switcher block HTML rendering requires WP 6.8.' );
		}

		// We need to have some content in the defined languages
		$en = self::factory()->post->create();
		self::$model->post->set_language( $en, 'en' );

		$fr = self::factory()->post->create();
		self::$model->post->set_language( $fr, 'fr' );

		$switcher_args = array(
			'blockName'    => 'polylang/navigation-language-switcher',
			'innerContent' => array(),
			'attrs'        => $options,
		);

		$switcher = new WP_Block( $switcher_args, $context );

		// Backward compatibility with WordPress < 7.0.
		if ( version_compare( $wp_version, '7.0-alpha', '<' ) && str_contains( $expected, 'dropdown-with-icon' ) ) {
			$expected = str_replace( '.html', '-wp69.html', $expected );
			$this->assertFileExists( self::PLL_SWITCHER_BLOCKS_DIR . $expected );
		}

		$this->assertStringMatchesFormatFile(
			self::PLL_SWITCHER_BLOCKS_DIR . $expected,
			$switcher->render(),
			'The navigation language switcher block should have the same format as ' . $expected
		);
	}

	public function switcher_options_provider() {
		return array(
			'Display as list'                                        => array(
				'options'  => array(),
				'context'  => array(),
				'expected' => 'navigation-language-switcher-list.html',
			),
			'Display as list with font and colors'                   => array(
				'options'  => array( 'dropdown' => 0 ),
				'context'  => array(
					'textColor'              => 'primary',
					'backgroundColor'        => 'secondary',
					'overlayTextColor'       => 'pale-pink',
					'overlayBackgroundColor' => 'vivid-red',
					'fontSize'               => 'x-large',
				),
				'expected' => 'navigation-language-switcher-list-with-font-and-color.html',
			),
			'Display as list with custom font and custom colors' => array(
				'options'  => array(
					'dropdown'  => 0,
					'className' => 'test-class',
				),
				'context'  => array(
					'customTextColor'              => '#1a3647',
					'customBackgroundColor'        => '#a34a4a',
					'customOverlayTextColor'       => '#3f1a47',
					'customOverlayBackgroundColor' => '#d6c8d9',
					'style'                        => array(
						'typography' => array(
							'fontStyle'  => 'normal',
							'fontWeight' => '600',
							'fontSize'   => '17px',
						),
					),
				),
				'expected' => 'navigation-language-switcher-list-with-custom-font-and-color.html',
			),
			'Display as dropdown'                                    => array(
				'options'  => array( 'dropdown' => 1 ),
				'context'  => array(),
				'expected' => 'navigation-language-switcher-dropdown.html',
			),
			'Display as dropdown with flags'                         => array(
				'options'  => array(
					'dropdown'   => 1,
					'show_flags' => 1,
				),
				'context'  => array(),
				'expected' => 'navigation-language-switcher-dropdown-with-flags.html',
			),
			'Display as dropdown with submenu icon'                  => array(
				'options'  => array( 'dropdown' => 1 ),
				'context'  => array(
					'showSubmenuIcon' => true,
				),
				'expected' => 'navigation-language-switcher-dropdown-with-icon.html',
			),
			'Display as dropdown with open on click'                 => array(
				'options'  => array( 'dropdown' => 1 ),
				'context'  => array(
					'openSubmenusOnClick' => true,
				),
				'expected' => 'navigation-language-switcher-dropdown-on-click.html',
			),
			'Display as dropdown with font and colors'               => array(
				'options'  => array( 'dropdown' => 1 ),
				'context'  => array(
					'textColor'              => 'primary',
					'backgroundColor'        => 'secondary',
					'overlayTextColor'       => 'pale-pink',
					'overlayBackgroundColor' => 'vivid-red',
					'fontSize'               => 'x-large',
				),
				'expected' => 'navigation-language-switcher-dropdown-with-font-and-color.html',
			),
			'Display as dropdown with custom font and custom colors' => array(
				'options'  => array(
					'dropdown'  => 1,
					'className' => 'test-class',
				),
				'context'  => array(
					'customTextColor'              => '#1a3647',
					'customBackgroundColor'        => '#a34a4a',
					'customOverlayTextColor'       => '#3f1a47',
					'customOverlayBackgroundColor' => '#d6c8d9',
					'style'                        => array(
						'typography' => array(
							'fontStyle'  => 'normal',
							'fontWeight' => '600',
							'fontSize'   => '17px',
						),
					),
				),
				'expected' => 'navigation-language-switcher-dropdown-with-custom-font-and-color.html',
			),
			'Displayed as dropdown with unauthorized CSS'            => array(
				'options'  => array(
					'dropdown' => 1,
				),
				'context'  => array(
					/**
					 * Add an unauthorized CSS property which must be filtered by safecss_filter_attr()
					 * from WP_Style_Engine_CSS_Declarations::filter_declaration().
					 * Also add some evil JavaScript (all JS evil anyway).
					 */
					'customOverlayBackgroundColor' => '#00ff00; word-spacing: 30px;" onclick="alert(\'toto\')"/><script>alert(\'toto\');<script/><ul style="',
					'style'                        => array(
						'typography' => array(
							'fontSize' => '0.9rem; word-spacing: 30px;" onclick="alert(\'toto\')"/><script>alert(\'toto\');<script/><ul style="',
						),
					),
				),
				'expected' => 'navigation-language-switcher-dropdown-bad-css.html',
			),
		);
	}
}
