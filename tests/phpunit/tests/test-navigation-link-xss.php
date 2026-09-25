<?php

use WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation;

class Navigation_Link_Xss_Test extends PLL_UnitTestCase {

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );
	}

	public function set_up() {
		parent::set_up();

		$options     = self::create_options();
		$model       = new PLL_Model( $options );
		$links_model = $model->get_links_model();
		$links_model->init();
		$polylang        = new PLL_Frontend( $links_model );
		$polylang->links = new PLL_Frontend_Links( $polylang );

		( new Navigation\Block( $polylang ) )->init();
	}

	public function tear_down() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'polylang/navigation-language-switcher' ) ) {
			WP_Block_Type_Registry::get_instance()->unregister( 'polylang/navigation-language-switcher' );
		}

		parent::tear_down();
	}

	/**
	 * @dataProvider malicious_block_provider
	 *
	 * @param string $block_name Block name to render.
	 * @param array  $overrides  Attribute overrides containing the payload.
	 * @param array  $context    Optional block context.
	 * @return void
	 */
	public function test_stored_attributes_cannot_inject_javascript( $block_name, $overrides, $context ) {
		$html = $this->render_navigation_block( $block_name, $this->get_block_attributes( $overrides ), $context );

		$this->assert_stored_payload_is_rejected( $html );
	}

	public function test_attributes_of_internal_blocks_cannot_be_overridden_at_runtime() {
		do_action( 'init' );

		foreach ( array( 'en', 'fr' ) as $lang ) {
			$post_id = self::factory()->post->create();
			self::$model->post->set_language( $post_id, $lang );
		}

		$inject_payload = static function ( $block_content, $block, $instance ) {
			$instance->attributes = array_merge(
				$instance->attributes,
				array(
					'pll_label'        => '<img src="x" onerror="alert(document.domain)">',
					'pll_locale'       => '" autofocus tabindex="1" onfocus="alert(document.domain)',
					'pll_label_markup' => '<img src="x" onerror="alert(document.domain)">',
					'pll_flag'         => '" autofocus tabindex="1" onfocus="alert(document.domain)',
					'pll_name'         => '" onclick="alert(document.domain)',
					'lang'             => '" autofocus tabindex="1" onfocus="alert(document.domain)',
					'hreflang'         => '" onclick="alert(document.domain)',
				)
			);

			return $block_content;
		};

		add_filter( 'render_block_core/navigation-link', $inject_payload, 9, 3 ); // Before Polylang's own filter.

		$html = $this->render_navigation_block( 'polylang/navigation-language-switcher', array() );

		$this->assertStringContainsString( 'lang="en-US"', $html );
		$this->assertStringContainsString( 'lang="fr-FR"', $html );
		$this->assertStringContainsString( 'English', $html );
		$this->assertStringContainsString( 'Français', $html );
		$this->assertStringNotContainsString( Navigation\Block::PLACEHOLDER, $html );
		$this->assert_xss_payload_is_absent( $html );
	}

	public function malicious_block_provider() {
		$blocks   = array(
			'Link'                   => array( 'core/navigation-link', array() ),
			'Submenu'                => array( 'core/navigation-submenu', array() ),
			'Submenu, open on click' => array( 'core/navigation-submenu', array( 'openSubmenusOnClick' => true ) ),
		);
		$payloads = array(
			'Quote-breakout flag'         => array(
				'pll_flag' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
			'Image onerror flag'          => array(
				'pll_flag' => '<img src="x" onerror="alert(document.domain)">',
			),
			'Quoted name'                 => array(
				'pll_show_flags' => false,
				'pll_name'       => '" onclick="alert(document.domain)',
			),
			'Quote-breakout label markup' => array(
				'pll_label_markup' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
			'Image onerror label markup'  => array(
				'pll_label_markup' => '<img src="x" onerror="alert(document.domain)">',
			),
			'Quote-breakout label'        => array(
				'pll_label' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
			'Image onerror label'         => array(
				'pll_label' => '<img src="x" onerror="alert(document.domain)">',
			),
			'Quote-breakout lang'         => array(
				'lang' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
			'Quote-breakout locale'       => array(
				'pll_locale' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
			'Unknown lang'                => array(
				'lang'     => 'xx-XX',
				'pll_flag' => '" autofocus tabindex="1" onfocus="alert(document.domain)',
			),
		);

		foreach ( $blocks as $block_label => $block ) {
			foreach ( $payloads as $payload_label => $overrides ) {
				yield $block_label . ': ' . $payload_label => array( $block[0], $overrides, $block[1] );
			}
		}
	}

	/**
	 * @param string $block_name Block name.
	 * @param array  $attributes Block attributes.
	 * @param array  $context    Optional block context.
	 * @return string
	 */
	private function render_navigation_block( $block_name, $attributes, $context = array() ) {
		$block = new WP_Block(
			array(
				'blockName' => $block_name,
				'attrs'     => $attributes,
			),
			$context
		);

		return $block->render();
	}

	/**
	 * @param array $overrides Attribute overrides.
	 * @return array
	 */
	private function get_block_attributes( $overrides = array() ) {
		return array_merge(
			array(
				'label'            => Navigation\Block::PLACEHOLDER,
				'title'            => Navigation\Block::PLACEHOLDER,
				'url'              => '#',
				'pll_show_flags'   => true,
				'pll_show_names'   => true,
				'lang'             => 'en',
				'hreflang'         => 'en-US',
				'pll_label_markup' => '',
			),
			$overrides
		);
	}

	/**
	 * @param string $html Rendered HTML.
	 * @return void
	 */
	private function assert_stored_payload_is_rejected( $html ) {
		$this->assert_xss_payload_is_absent( $html );
		$this->assertStringContainsString( Navigation\Block::PLACEHOLDER, $html );
	}

	/**
	 * @param string $html Rendered HTML.
	 * @return void
	 */
	private function assert_xss_payload_is_absent( $html ) {
		$this->assertDoesNotMatchRegularExpression( '/\son(?:focus|error|click)\s*=/i', $html );
		$this->assertStringNotContainsString( 'autofocus', $html );
		$this->assertStringNotContainsString( 'alert(document.domain)', $html );
		$this->assertStringNotContainsString( 'onerror=', $html );
	}
}
