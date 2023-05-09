<?php

class Upgrade_Test extends PLL_UnitTestCase {
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function test_upgrade() {
		// Fake old version.
		$options = get_option( 'polylang' );
		$options['version'] = '3.3';
		update_option( 'polylang', $options );

		// Fake old transient.
		self::$model->set_languages_ready();
		self::$model->get_languages_list();
		$languages = get_transient( 'pll_languages_list' );
		foreach ( $languages as $i => $language ) {
			unset( $language['term_props'] );
			$languages[ $i ] = $language;
		}
		set_transient( 'pll_languages_list', $languages );

		$pll = new Polylang();

		try {
			$pll->init();
		} catch ( \Throwable $th ) {
			$this->assertTrue( false, "Polylang upgrade failed with the error {$th}" );
		}

		$this->assertTrue( true );
	}
}
