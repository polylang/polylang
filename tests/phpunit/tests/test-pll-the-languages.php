<?php

class PLL_The_Languages_Test extends PLL_UnitTestCase {

	private $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );
		$factory->language->create_many( 4 );
		self::require_api();
	}

	public function set_up() {
		parent::set_up();

		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$this->pll_model = new PLL_Model( $options );
		$links_model     = $this->pll_model->get_links_model();
		$this->frontend  = new PLL_Frontend( $links_model );

		$this->frontend->init();
		$this->frontend->links->cache->clean();

		$GLOBALS['polylang'] = $this->frontend;
	}

	public function test_raw_arg_should_return_raw() {
		$posts = $this->init_test_raw();

		$url_en = get_permalink( $posts['en'] );
		$this->go_to( $url_en );

		$elements = pll_the_languages(
			array(
				'raw' => 1,
			)
		);

		$this->assertIsArray( $elements );
		$this->assertCount( 3, $elements );
		$this->assertArrayHasKey( 'en', $elements );
		$this->assertArrayHasKey( 'fr', $elements );
		$this->assertArrayHasKey( 'de', $elements );
		$this->assertSame( 'en', array_key_first( $elements ) );

		$language_en = $this->pll_model->get_language( 'en' );
		$expected_en = array(
			'id'             => $language_en->term_id,
			'order'          => 0,
			'slug'           => 'en',
			'locale'         => 'en-US',
			'is_rtl'         => false,
			'name'           => 'English',
			'url'            => $url_en,
			'flag'           => $language_en->flag_url,
			'current_lang'   => true,
			'no_translation' => false,
			'classes'        => array(
				'lang-item',
				'lang-item-' . $language_en->term_id,
				'lang-item-en',
				'current-lang',
				'lang-item-first',
			),
			'link_classes'     => array(),
			// New values.
			'label'            => 'English',
			'direction'        => 'ltr',
			'has_translations' => true,
			'is_current'       => true,
			'is_empty'         => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_en->term_id,
				'lang-item-en',
				'current-lang',
				'lang-item-first',
			),
		);

		$this->assertSameSetsWithIndex( $expected_en, $elements['en'] );

		$language_fr = $this->pll_model->get_language( 'fr' );
		$expected_fr = array(
			'id'             => $language_fr->term_id,
			'order'          => 0,
			'slug'           => 'fr',
			'locale'         => 'fr-FR',
			'is_rtl'         => false,
			'name'           => 'Français',
			'url'            => get_permalink( $posts['fr'] ),
			'flag'           => $language_fr->flag_url,
			'current_lang'   => false,
			'no_translation' => false,
			'classes'        => array(
				'lang-item',
				'lang-item-' . $language_fr->term_id,
				'lang-item-fr',
			),
			'link_classes'   => array(),
			// New values.
			'label'            => 'Français',
			'direction'        => 'ltr',
			'has_translations' => true,
			'is_current'       => false,
			'is_empty'         => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_fr->term_id,
				'lang-item-fr',
			),
		);

		$this->assertSameSetsWithIndex( $expected_fr, $elements['fr'] );

		$language_de = $this->pll_model->get_language( 'de' );
		$expected_de = array(
			'id'             => $language_de->term_id,
			'order'          => 0,
			'slug'           => 'de',
			'locale'         => 'de-DE',
			'is_rtl'         => false,
			'name'           => 'Deutsch',
			'url'            => $language_de->get_home_url(), // No translation.
			'flag'           => $language_de->flag_url,
			'current_lang'   => false,
			'no_translation' => true,
			'classes'        => array(
				'lang-item',
				'lang-item-' . $language_de->term_id,
				'lang-item-de',
				'no-translation',
			),
			'link_classes'   => array(),
			// New values.
			'label'            => 'Deutsch',
			'direction'        => 'ltr',
			'has_translations' => false,
			'is_current'       => false,
			'is_empty'         => false,
			'item_classes'     => array(
				'lang-item',
				'lang-item-' . $language_de->term_id,
				'lang-item-de',
				'no-translation',
			),
		);

		$this->assertSameSetsWithIndex( $expected_de, $elements['de'] );
	}

	public function test_raw_hide_arg_should_return_raw() {
		$this->setExpectedDeprecated( 'pll_the_languages()' ); // `display_names_as` is deprecated.
		$posts = $this->init_test_raw();

		$url_en = get_permalink( $posts['en'] );
		$this->go_to( $url_en );

		$args = array(
			'raw'                    => 1,
			'force_home'             => 1,
			'hide_current'           => 1,
			'hide_if_no_translation' => 1,
			'display_names_as'       => 'slug',
		);
		$elements = pll_the_languages( $args );

		$this->assertIsArray( $elements );
		$this->assertCount( 1, $elements ); // Only fr in the array.
		$this->assertArrayHasKey( 'fr', $elements );

		$language_fr = $this->pll_model->get_language( 'fr' );

		$this->assertSame( $language_fr->get_home_url(), $elements['fr']['url'] ); // force_home
		$this->assertSame( 'fr', $elements['fr']['name'] ); // display_name_as
	}

	public function test_raw_hide_if_empty_arg_should_return_raw() {
		$posts = $this->init_test_raw();

		$url_en = get_permalink( $posts['en'] );
		$this->go_to( $url_en );

		$args = array(
			'raw'           => 1,
			'hide_if_empty' => 0,
		);
		$elements = pll_the_languages( $args );

		$this->assertIsArray( $elements );
		$this->assertArrayHasKey( 'es', $elements ); // The "empty" language is present.

		$language_es = $this->pll_model->get_language( 'es' );

		$this->assertSame( $language_es->get_home_url(), $elements['es']['url'] );
	}

	public function test_raw_post_id_arg_should_return_raw() {
		$posts = $this->init_test_raw();
		$this->go_to( home_url( '/' ) );

		$args = array(
			'raw'     => 1,
			'post_id' => $posts['en'],
		);
		$elements = pll_the_languages( $args );

		$this->assertIsArray( $elements );
		$this->assertArrayHasKey( 'fr', $elements );
		$this->assertSame( get_permalink( $posts['fr'] ), $elements['fr']['url'] );
	}

	/**
	 * Very basic tests for the switcher as list.
	 */
	public function test_should_return_list() {
		$posts = $this->init_test_raw();

		$url_en = get_permalink( $posts['en'] );
		$this->go_to( $url_en );

		$switcher = pll_the_languages( array( 'echo' => 0 ) );
		$xpath    = $this->get_domxpath( $switcher );

		$a = $xpath->query( '//li/a[@lang="en-US"]' )->item( 0 );
		$this->assertNotEmpty( $a, 'There should be a link.' );
		$this->assertSame( $url_en, $a->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' )->item( 0 );
		$this->assertNotEmpty( $a, 'There should be a link.' );
		$this->assertSame( get_permalink( $posts['fr'] ), $a->getAttribute( 'href' ) );

		// Bug fixed in 2.6.10.
		$args     = array( 'hide_current' => 1, 'echo' => 0 );
		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$li = $xpath->query( '//li' )->item( 0 );
		$this->assertNotEmpty( $li, 'There should be a li tag.' );
		$this->assertNotFalse( strpos( $li->getAttribute( 'class' ), 'lang-item-first' ) );
	}

	public function test_should_print_list() {
		$posts = $this->init_test_raw();

		$url_en = get_permalink( $posts['en'] );
		$this->go_to( $url_en );

		ob_start();
		pll_the_languages( array( 'echo' => 1 ) );
		$this->assertNotEmpty( ob_get_clean() );
	}

	/**
	 * Very basic tests for the switcher as list with deprecated arguments.
	 * Bug fixed in 2.6.3: No label when showing only flags.
	 */
	public function test_should_return_list_with_deprecated_arguments() {
		$this->setExpectedDeprecated( 'pll_the_languages()' ); // `show_names` is deprecated.
		$posts = $this->init_test_raw();
		$this->go_to( get_permalink( $posts['en'] ) );

		$args     = array( 'show_names' => 0, 'show_flags' => 1, 'echo' => 0 );
		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$this->assertEmpty( $xpath->query( '//li/a[@lang="en-US"]/span[@class="pll-switcher-label"]' )->length );

		$args     = array( 'show_names' => 1, 'show_flags' => 1, 'echo' => 0 );
		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$span = $xpath->query( '//li/a[@lang="en-US"]/span[@class="pll-switcher-label"]' )->item( 0 );
		$this->assertNotEmpty( $span );
		$this->assertSame( 'English', $span->nodeValue );
	}

	/**
	 * Very basic tests for the switcher as select.
	 */
	public function test_should_return_select() {
		$this->setExpectedDeprecated( 'pll_the_languages()' ); // `dropdown` is deprecated.
		$posts = $this->init_test_raw();
		$this->go_to( get_permalink( $posts['en'] ) );

		$args = array(
			'dropdown' => 1,
			'echo'     => 0,
		);
		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$option = $xpath->query( '//select/option[.="English"]' )->item( 0 );
		$this->assertNotEmpty( $option, 'There should be an option tag.' );
		$this->assertSame( 'selected', $option->getAttribute( 'selected' ) );
		$this->assertNotEmpty( $xpath->query( '//select/option[.="Français"]' )->length );
		$this->assertEmpty( $xpath->query( '//script' )->length ); // No `script` tag anymore.
		$lang_attributes = $xpath->query( '//select/option/@lang' );
		$this->assertSame( 'en-US', $lang_attributes->item( 0 )->value );
		$this->assertSame( 'fr-FR', $lang_attributes->item( 1 )->value );
	}

	/**
	 * @ticket #1890
	 * @see https://github.com/polylang/polylang-pro/issues/1890.
	 */
	public function test_flags_a11y_without_names_displayed() {
		$this->setExpectedDeprecated( 'pll_the_languages()' ); // `show_names` is deprecated.
		$args = array(
			'show_flags'    => 1,
			'show_names'    => 0, // Don't display names.
			'echo'          => 0,
			'hide_if_empty' => 0,
		);
		$this->frontend->links->curlang = $this->pll_model->get_language( 'en' );

		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$img = $xpath->query( '//li/a[@lang="en-US"]/span/img' )->item( 0 );
		$this->assertNotEmpty( $img, 'There should be an image.' );
		$this->assertSame( 'English', $img->getAttribute( 'alt' ), 'Alternative text value should be "English".' );

		$img = $xpath->query( '//li/a[@lang="fr-FR"]/span/img' )->item( 0 );
		$this->assertNotEmpty( $img, 'There should be an image.' );
		$this->assertSame( 'Français', $img->getAttribute( 'alt' ), 'Alternative text value should be "Français".' );
	}

	/**
	 * @ticket #1890
	 * @see https://github.com/polylang/polylang-pro/issues/1890.
	 */
	public function test_flags_a11y_with_names_displayed() {
		$this->setExpectedDeprecated( 'pll_the_languages()' ); // `show_names` is deprecated.
		$args = array(
			'show_flags'    => 1,
			'show_names'    => 1, // Display names.
			'echo'          => 0,
			'hide_if_empty' => 0,
		);
		$this->frontend->links->curlang = $this->pll_model->get_language( 'en' );

		$switcher = pll_the_languages( $args );
		$xpath    = $this->get_domxpath( $switcher );

		$img = $xpath->query( '//li/a[@lang="en-US"]/span/img' )->item( 0 );
		$this->assertNotEmpty( $img, 'There should be an image.' );
		$this->assertEmpty( $img->getAttribute( 'alt' ), 'There should be no alternative texts.' );

		$img = $xpath->query( '//li/a[@lang="fr-FR"]/span/img' )->item( 0 );
		$this->assertNotEmpty( $img, 'There should be an image.' );
		$this->assertEmpty( $img->getAttribute( 'alt' ), 'There should be no alternative texts.' );
	}

	private function init_test_raw(): array {
		$posts = self::factory()->post->create_translated(
			array(
				'lang' => 'en',
			),
			array(
				'lang' => 'fr',
			)
		);
		$de = self::factory()->post->create(
			array(
				'lang' => 'de',
			)
		);

		$this->frontend->links->curlang = $this->pll_model->get_language( 'en' );

		return array_merge( $posts, array( 'de' => $de ) );
	}

	/**
	 * Returns an instance of `DOMXpath`.
	 *
	 * @param string $html HTML as a string.
	 * @return DOMXpath
	 */
	protected function get_domxpath( string $html ): DOMXpath {
		$this->assertNotEmpty( $html );
		$doc = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR );
		return new DOMXpath( $doc );
	}
}
