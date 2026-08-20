<?php

namespace WP_Syntex\Polylang\Tests\Switcher;

class GetElements_Test extends TestCase {
	public function test_default_values(): void {
		$elements = $this->get_switcher_and_init_frontend()->get_elements();

		$this->assertCount( 2, $elements );
		$this->assertArrayHasKey( 'en', $elements );
		$this->assertArrayHasKey( 'fr', $elements );
		$this->assertSame( 'en', array_key_first( $elements ) );

		$language_en = $this->pll_model->get_language( 'en' );
		$expected_en = array(
			'id'               => $language_en->term_id,
			'slug'             => 'en',
			'locale'           => 'en-US',
			'url'              => $language_en->get_home_url(),
			'label'            => 'English',
			'flag'             => '',
			'direction'        => 'ltr',
			'order'            => 0,
			'has_translations' => false,
			'is_empty'         => false,
			'is_current'       => true,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_en->term_id,
				'lang-item-en',
				'current-lang',
				'no-translation',
				'lang-item-first',
			),
			'link_classes'     => array(),
		);

		$this->assertSameSetsWithIndex( $expected_en, get_object_vars( $elements['en'] ) );

		$language_fr = $this->pll_model->get_language( 'fr' );
		$expected_fr = array(
			'id'               => $language_fr->term_id,
			'slug'             => 'fr',
			'locale'           => 'fr-FR',
			'url'              => $language_fr->get_home_url(),
			'label'            => 'Français',
			'flag'             => '',
			'direction'        => 'ltr',
			'order'            => 0,
			'has_translations' => false,
			'is_empty'         => false,
			'is_current'       => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_fr->term_id,
				'lang-item-fr',
				'no-translation',
			),
			'link_classes'     => array(),
		);

		$this->assertSameSetsWithIndex( $expected_fr, get_object_vars( $elements['fr'] ) );
	}

	public function test_non_default_values(): void {
		// `$post_id` and `$hide_if_no_translation` have dedicated tests.
		// `$preserve_spacing`, `$show_wrapper`, `$wrapper_classes`, and `$unique_id` have no effects on elements.
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'layout'            => 'horizontal',
				'alignment'         => 'stretched',
				'show_flags'        => true,
				'flag_aspect_ratio' => '1:1',
				'show_labels'       => 'codes',
				'hide_current'      => true,  // Hide EN.
				'hide_if_empty'     => false, // Display DE.
				'force_home'        => true,
				'item_classes'      => array( 'foobar_item_1', 'foobar_item_2' ),
				'link_classes'      => array( 'barbaz_link_1', 'barbaz_link_2' ),
			)
		)->get_elements();

		$this->assertCount( 2, $elements );
		$this->assertArrayHasKey( 'fr', $elements );
		$this->assertArrayHasKey( 'de', $elements );

		$language_fr = $this->pll_model->get_language( 'fr' );
		$expected_fr = array(
			'id'               => $language_fr->term_id,
			'slug'             => 'fr',
			'locale'           => 'fr-FR',
			'url'              => $language_fr->get_home_url(),
			'label'            => 'FR',
			'flag'             => $this->replace_flag_style( $language_fr->get_display_flag( 'no-alt' ) ),
			'direction'        => 'ltr',
			'order'            => 0,
			'has_translations' => false,
			'is_empty'         => false,
			'is_current'       => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_fr->term_id,
				'lang-item-fr',
				'foobar_item_1',
				'foobar_item_2',
				'no-translation',
				'lang-item-first',
			),
			'link_classes'     => array(
				'barbaz_link_1',
				'barbaz_link_2',
			),
		);

		$this->assertSameSetsWithIndex( $expected_fr, get_object_vars( $elements['fr'] ) );

		$language_de = $this->pll_model->get_language( 'de' );
		$expected_de = array(
			'id'               => $language_de->term_id,
			'slug'             => 'de',
			'locale'           => 'de-DE',
			'url'              => $language_de->get_home_url(),
			'label'            => 'DE',
			'flag'             => $this->replace_flag_style( $language_de->get_display_flag( 'no-alt' ) ),
			'direction'        => 'ltr',
			'order'            => 0,
			'has_translations' => false,
			'is_empty'         => true,
			'is_current'       => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_de->term_id,
				'lang-item-de',
				'foobar_item_1',
				'foobar_item_2',
				'no-translation',
			),
			'link_classes'     => array(
				'barbaz_link_1',
				'barbaz_link_2',
			),
		);

		$this->assertSameSetsWithIndex( $expected_de, get_object_vars( $elements['de'] ) );
	}

	public function test_current_element(): void {
		$pll_env = $this->init_frontend();
		$pll_env->curlang = $pll_env->model->languages->get( 'fr' );

		$elements = $this->get_new_switcher( $pll_env )->get_elements();

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertArrayHasKey( 'fr', $elements );

		$this->assertFalse( $elements['en']->is_current );
		$this->assertTrue( $elements['fr']->is_current );
	}

	public function test_post_id(): void {
		$switcher = $this->get_switcher_and_init_frontend( array( 'post_id' => self::$posts['en'] ) );
		$elements = $switcher->get_elements();

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertArrayHasKey( 'fr', $elements );

		$this->assertSame( get_permalink( self::$posts['en'] ), $elements['en']->url );
		$this->assertSame( get_permalink( self::$posts['fr'] ), $elements['fr']->url );
		$this->assertTrue( $elements['en']->has_translations );
		$this->assertTrue( $elements['fr']->has_translations );
		$this->assertNotContains( 'no-translation', $elements['en']->item_classes );
		$this->assertNotContains( 'no-translation', $elements['fr']->item_classes );
	}

	public function test_hide_if_no_translation(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'post_id'                => self::$posts['en'],
				'hide_if_empty'          => false, // For DE.
				'hide_if_no_translation' => true,
			)
		)->get_elements();

		$this->assertCount( 2, $elements ); // EN + FR.
		$this->assertArrayNotHasKey( 'de', $elements );
	}

	public function test_show_if_no_translation(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'post_id'                => self::$posts['en'],
				'hide_if_empty'          => false, // For DE.
				'hide_if_no_translation' => false,
			)
		)->get_elements();

		$this->assertCount( 3, $elements ); // EN + FR + DE.
		$this->assertArrayHasKey( 'de', $elements );
		$this->assertFalse( $elements['de']->has_translations );
		$this->assertSame( $this->pll_model->get_language( 'de' )->get_home_url(), $elements['de']->url );
	}

	public function test_label_with_flags_and_names(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'show_flags' => true,
			)
		)->get_elements();

		$this->assertArrayHasKey( 'en', $elements );
		$label = $elements['en']->get_label();

		$this->assertMatchesRegularExpression(
			'/^<span class="pll-switcher-flag"[^>]*><img[^>]* alt=""[^>]*><\/span><span class="pll-switcher-label"[^>]*>English<\/span>$/', // Empty alt.
			$label
		);
		$this->assertStringContainsString(
			'style="display:inline-block;flex-shrink:0;width:var(--pll-flag-width,18px);overflow:hidden;border-radius:calc(var(--pll-flag-border-radius,0)*.5*1%);aspect-ratio:3/2"',
			$label
		);
		$this->assertStringContainsString(
			'style="display:block;object-fit:cover;object-position:center;width:100%;height:100%"',
			$label
		);
		$this->assertStringContainsString(
			'style="margin-inline-start:var(--pll-flag-label-spacing,0.3em);writing-mode:horizontal-tb"',
			$label
		);
	}

	public function test_label_with_flags_and_codes(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'show_flags'  => true,
				'show_labels' => 'codes',
			)
		)->get_elements();

		$this->assertMatchesRegularExpression(
			'/^<span class="pll-switcher-flag"[^>]*><img[^>]* alt=""[^>]*><\/span><span class="pll-switcher-label"[^>]*>EN<\/span>$/', // Empty alt.
			$elements['en']->get_label()
		);
	}

	public function test_label_with_flags_only(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'show_flags'  => true,
				'show_labels' => '',
			)
		)->get_elements();

		$this->assertMatchesRegularExpression(
			'/^<span class="pll-switcher-flag"[^>]*><img[^>]* alt="[^"]+"[^>]*><\/span>$/', // Non empty alt.
			$elements['en']->get_label()
		);
	}

	public function test_label_falls_back_to_names_when_empty(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'show_flags'  => false,
				'show_labels' => '',
			)
		)->get_elements();

		$this->assertSame( '<span class="pll-switcher-label">English</span>', $elements['en']->get_label() );
	}

	public function test_label_for_select_layout(): void {
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'layout'      => 'select',
				'show_flags'  => true,
				'show_labels' => '',
			)
		)->get_elements();

		$this->assertSame( 'English', $elements['en']->get_label() );
	}

	public function test_home_url_uses_permalink_for_post(): void {
		$post_url = get_permalink( self::$posts['en'] );
		$elements = $this->get_switcher_and_init_frontend( array( 'post_id' => self::$posts['en'] ) )->get_elements();

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertSame( $post_url, $elements['en']->url );
	}

	public function test_home_url_fallback_when_filter_returns_empty_string(): void {
		$post_url = get_permalink( self::$posts['en'] );
		$cb       = function ( $url, $slug ) use ( $post_url ) {
			if ( 'en' !== $slug ) {
				return $url;
			}
			$this->assertSame( $post_url, $url );
			return '';
		};
		add_filter( 'pll_the_language_link', $cb, 10, 2 );
		$elements = $this->get_switcher_and_init_frontend( array( 'post_id' => self::$posts['en'] ) )->get_elements();
		remove_filter( 'pll_the_language_link', $cb );

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertSame( $this->pll_model->get_language( 'en' )->get_home_url(), $elements['en']->url );
	}

	public function test_home_url_fallback_when_filter_returns_non_string(): void {
		$post_url = get_permalink( self::$posts['en'] );
		$cb       = function ( $url, $slug ) use ( $post_url ) {
			if ( 'en' !== $slug ) {
				return $url;
			}
			$this->assertSame( $post_url, $url );
			return array();
		};
		add_filter( 'pll_the_language_link', $cb, 10, 2 );
		$elements = $this->get_switcher_and_init_frontend( array( 'post_id' => self::$posts['en'] ) )->get_elements();
		remove_filter( 'pll_the_language_link', $cb );

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertSame( $this->pll_model->get_language( 'en' )->get_home_url(), $elements['en']->url );
	}

	public function test_home_url_when_force_home_is_true(): void {
		$post_url = get_permalink( self::$posts['en'] );
		$cb       = function ( $url, $slug ) use ( $post_url ) {
			if ( 'en' === $slug ) {
				$this->assertSame( $post_url, $url );
			}
			return $url;
		};
		add_filter( 'pll_the_language_link', $cb, 10, 2 );
		$elements = $this->get_switcher_and_init_frontend(
			array(
				'post_id'    => self::$posts['en'],
				'force_home' => true,
			)
		)->get_elements();
		remove_filter( 'pll_the_language_link', $cb );

		$this->assertArrayHasKey( 'en', $elements );
		$this->assertSame( $this->pll_model->get_language( 'en' )->get_home_url(), $elements['en']->url );
	}

	/**
	 * Replaces the `style` attribute of a flag by the one set by `Abstract_Element`.
	 *
	 * @param string $string Markup.
	 * @return string
	 */
	private function replace_flag_style( string $string ): string {
		return preg_replace(
			'/style=(["\']).*?\1/',
			'style="display:block;object-fit:cover;object-position:center;width:100%;height:100%"',
			$string
		);
	}
}
