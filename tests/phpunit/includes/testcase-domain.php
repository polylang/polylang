<?php

class PLL_Domain_UnitTestCase extends PLL_UnitTestCase {
	protected $structure = '/%postname%/';
	protected $hosts;
	protected static $server;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::$server = $_SERVER; // backup

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE' );
	}

	public static function wpTearDownAfterClass() {
		parent::wpTearDownAfterClass();

		$_SERVER = self::$server;
	}

	public function init_links_model() {
		global $wp_rewrite;

		// Refresh languages.
		self::$model->clean_languages_cache();
		self::$model->get_languages_list();

		// switch to pretty permalinks
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $this->structure );
		$this->links_model = self::$model->get_links_model();
	}
}
