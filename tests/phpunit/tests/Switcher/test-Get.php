<?php

namespace WP_Syntex\Polylang\Tests\Switcher;

class Get_Test extends TestCase {
	public function test_default_values(): void {
		$html  = $this->get_switcher()->get();
		$xpath = $this->get_domxpath( $html );

		$wrappers = $xpath->query( '//div' );
		$this->assertSame( 1, $wrappers->count() );
		$wrapper = $wrappers->item( 0 );
		$this->assertMatchesRegularExpression( '/^pll-switcher-\d+$/', $wrapper->getAttribute( 'id' ) );
		$this->assertSame( 'Choose a language', $wrapper->getAttribute( 'aria-label' ) );
		$this->assertSameSets(
			array( 'pll-switcher', 'pll-layout-vertical', 'pll-alignment-left' ),
			explode( ' ', $wrapper->getAttribute( 'class' ) )
		);

		$lis = $xpath->query( '//div/ul/li' );
		$this->assertSame( 2, $lis->count() ); // The 3rd language is hadden because `hide_if_empty` is `true`.

		// The first language is the default one (en).
		$en_language = $this->pll_model->get_language( 'en' );
		$this->assertSameSets(
			array( 'lang-item', 'lang-item-' . $en_language->term_id, 'lang-item-en', 'current-lang', 'no-translation', 'lang-item-first' ),
			explode( ' ', $lis->item( 0 )->getAttribute( 'class' ) )
		);

		// The remaining language is fr.
		$fr_language = $this->pll_model->get_language( 'fr' );
		$this->assertSameSets(
			array( 'lang-item', 'lang-item-' . $fr_language->term_id, 'lang-item-fr', 'no-translation' ),
			explode( ' ', $lis->item( 1 )->getAttribute( 'class' ) )
		);

		$links = $xpath->query( '//div/ul/li/a' );
		$this->assertSame( 2, $links->count() );

		$this->assertSame( 'en-US', $links->item( 0 )->getAttribute( 'lang' ) );
		$this->assertSame( 'en-US', $links->item( 0 )->getAttribute( 'hreflang' ) );
		$this->assertSame( 'true', $links->item( 0 )->getAttribute( 'aria-current' ) );
		$this->assertSame( $en_language->get_home_url(), $links->item( 0 )->getAttribute( 'href' ) );
		$this->assertSame( 'English', $links->item( 0 )->nodeValue );

		$this->assertSame( 'fr-FR', $links->item( 1 )->getAttribute( 'lang' ) );
		$this->assertSame( 'fr-FR', $links->item( 1 )->getAttribute( 'hreflang' ) );
		$this->assertSame( '', $links->item( 1 )->getAttribute( 'aria-current' ) );
		$this->assertSame( $fr_language->get_home_url(), $links->item( 1 )->getAttribute( 'href' ) );
		$this->assertSame( 'Français', $links->item( 1 )->nodeValue );

		$spans = $xpath->query( '//div/ul/li/a/span[@class="pll-switcher-flag"]' );
		$this->assertSame( 0, $spans->count() ); // No flags.
	}

	public function test_layout_unknown(): void {
		$html = $this->get_switcher( array( 'layout' => 'unknown' ) )->get();

		$wrappers = $this->get_domxpath( $html )->query( '//div' );
		$this->assertSame( 1, $wrappers->count() );
		$wrapper = $wrappers->item( 0 );
		$classes = $wrapper->getAttribute( 'class' );
		$this->assertStringContainsString( ' pll-layout-vertical ', " $classes " );
	}

	public function test_layout_select(): void {
		$html  = $this->get_switcher( array( 'layout' => 'select' ) )->get();
		$xpath = $this->get_domxpath( $html );

		$wrappers = $xpath->query( '//div' );
		$this->assertSame( 1, $wrappers->count() );
		$wrapper = $wrappers->item( 0 );
		$classes = $wrapper->getAttribute( 'class' );
		$this->assertStringContainsString( ' pll-layout-select ', " $classes " );

		$labels = $xpath->query( '//div/label' );
		$this->assertSame( 1, $labels->count() );
		$label = $labels->item( 0 );
		$this->assertSame( 'screen-reader-text', $label->getAttribute( 'class' ) );
		$label_for = $label->getAttribute( 'for' );
		$this->assertMatchesRegularExpression( '/^pll-switcher-\d+$/', $label_for );
		$this->assertSame( 'Choose a language', $label->nodeValue ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$selects = $xpath->query( '//div/select' );
		$this->assertSame( 1, $selects->count() );
		$select = $selects->item( 0 );
		$this->assertSame( 'pll-switcher-select', $select->getAttribute( 'class' ) );
		$this->assertSame( $label_for, $select->getAttribute( 'id' ) );

		$options = $xpath->query( '//div/select/option' );
		$this->assertSame( 2, $options->count() );

		// The first language is the default one (en).
		$en_language = $this->pll_model->get_language( 'en' );
		$en_option   = $options->item( 0 );

		$this->assertSame( 'en-US', $en_option->getAttribute( 'lang' ) );
		$this->assertSame( $en_language->get_home_url(), $en_option->getAttribute( 'value' ) );
		$this->assertSame( 'selected', $en_option->getAttribute( 'selected' ) );
		$this->assertSameSets(
			array( 'lang-item', 'lang-item-' . $en_language->term_id, 'lang-item-en', 'current-lang', 'no-translation', 'lang-item-first' ),
			explode( ' ', $en_option->getAttribute( 'class' ) )
		);
		$this->assertSame( 'English', $en_option->nodeValue ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// The remaining language is fr.
		$fr_language = $this->pll_model->get_language( 'fr' );
		$fr_option   = $options->item( 1 );

		$this->assertSame( 'fr-FR', $fr_option->getAttribute( 'lang' ) );
		$this->assertSame( $fr_language->get_home_url(), $fr_option->getAttribute( 'value' ) );
		$this->assertSame( '', $fr_option->getAttribute( 'selected' ) );
		$this->assertSameSets(
			array( 'lang-item', 'lang-item-' . $fr_language->term_id, 'lang-item-fr', 'no-translation' ),
			explode( ' ', $fr_option->getAttribute( 'class' ) )
		);
		$this->assertSame( 'Français', $fr_option->nodeValue ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	public function test_layout_dropdown(): void {
		$html  = $this->get_switcher( array( 'layout' => 'dropdown' ) )->get();
		$xpath = $this->get_domxpath( $html );

		$wrappers = $xpath->query( '//div' );
		$this->assertSame( 1, $wrappers->count() );
		$wrapper = $wrappers->item( 0 );
		$classes = $wrapper->getAttribute( 'class' );
		$this->assertStringContainsString( ' pll-layout-dropdown ', " $classes " );

		$top_level_links = $xpath->query( '//div/a' );
		$this->assertSame( 1, $top_level_links->count() );
		$this->assertSame(
			$this->pll_model->get_language( 'en' )->get_home_url(),
			$top_level_links->item( 0 )->getAttribute( 'href' )
		);

		$uls = $xpath->query( '//div/ul' );
		$this->assertSame( 1, $uls->count() );
	}

	public function test_layouts_horizontal_vertical(): void {
		$pll_env = $this->init_frontend();

		foreach ( array( 'horizontal', 'vertical' ) as $layout ) {
			$html  = $this->get_new_switcher( $pll_env, array( 'layout' => $layout ) )->get();
			$xpath = $this->get_domxpath( $html );

			$wrappers = $xpath->query( '//div' );
			$this->assertSame( 1, $wrappers->count() );
			$wrapper = $wrappers->item( 0 );
			$classes = $wrapper->getAttribute( 'class' );
			$this->assertStringContainsString( " pll-layout-$layout ", " $classes " );

			$uls = $xpath->query( '//div/ul' );
			$this->assertSame( 1, $uls->count() );
		}
	}

	public function test_nav_tag(): void {
		add_theme_support( 'html5', array( 'navigation-widgets' ) );

		$html  = $this->get_switcher()->get();
		$xpath = $this->get_domxpath( $html );

		$wrapper = $xpath->query( '//nav' );
		$this->assertSame( 1, $wrapper->count() );
	}
}
