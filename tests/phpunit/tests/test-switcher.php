<?php

class Switcher_Test extends PLL_UnitTestCase {
	private $structure = '/%postname%/';

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );

		require_once POLYLANG_DIR . '/include/api.php';
		$GLOBALS['polylang'] = &self::$polylang;

		self::$polylang->model->post->register_taxonomy(); // Needed for post counting
	}

	function setUp() {
		parent::setUp();

		self::$polylang = new PLL_Frontend( self::$polylang->links_model );
		self::$polylang->init();

		// De-activate cache for links
		self::$polylang->links->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->links->cache->method( 'get' )->willReturn( false );

		$this->switcher = new PLL_Switcher();
	}

	function test_the_languages_raw() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$de = $this->factory->post->create();
		self::$polylang->model->post->set_language( $de, 'de' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		self::$polylang->links->curlang = self::$polylang->model->get_language( 'en' );
		$this->go_to( get_permalink( $en ) );

		// Raw with default arguments
		$args = array(
			'raw' => 1,
		);
		$arr = $this->switcher->the_languages( self::$polylang->links, $args );

		$this->assertCount( 3, $arr );
		$this->assertTrue( $arr['de']['no_translation'] );
		$this->assertTrue( $arr['en']['current_lang'] );
		$this->assertEquals( get_permalink( $en ), $arr['en']['url'] );
		$this->assertEquals( get_permalink( $fr ), $arr['fr']['url'] );
		$this->assertEquals( home_url( '?lang=de' ), $arr['de']['url'] ); // No translation
		$this->assertEquals( 'English', $arr['en']['name'] );
		$this->assertEquals( 'en-US', $arr['en']['locale'] );
		$this->assertEquals( 'en', $arr['en']['slug'] );
		$this->assertEquals( plugins_url( '/flags/us.png', POLYLANG_FILE ), $arr['en']['flag'] );

		// Other arguments
		$args = array_merge(
			$args,
			array(
				'force_home'             => 1,
				'hide_current'           => 1,
				'hide_if_no_translation' => 1,
				'display_names_as'       => 'slug',
			)
		);
		$arr = $this->switcher->the_languages( self::$polylang->links, $args );

		$this->assertCount( 1, $arr ); // Only fr in the array
		$this->assertEquals( home_url( '?lang=fr' ), $arr['fr']['url'] ); // force_home
		$this->assertEquals( 'fr', $arr['fr']['name'] ); // display_name_as

		$this->go_to( home_url( '/' ) );

		// Post_id
		$args = array(
			'raw'     => 1,
			'post_id' => $en,
		);
		$arr = $this->switcher->the_languages( self::$polylang->links, $args );
		$this->assertEquals( get_permalink( $fr ), $arr['fr']['url'] );
	}

	/**
	 *  Very basic tests for the switcher as list
	 */
	function test_list() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		self::$polylang->model->clean_languages_cache(); // FIXME for some reason, I need to clear the cache to get an exact count

		self::$polylang->links->curlang = self::$polylang->model->get_language( 'en' );
		$this->go_to( get_permalink( $en ) );

		$args = array( 'echo' => 0 );
		$switcher = $this->switcher->the_languages( self::$polylang->links, $args );
		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$a = $xpath->query( '//li/a[@lang="en-US"]' );
		$this->assertEquals( get_permalink( $en ), $a->item( 0 )->getAttribute( 'href' ) );

		$a = $xpath->query( '//li/a[@lang="fr-FR"]' );
		$this->assertEquals( get_permalink( $fr ), $a->item( 0 )->getAttribute( 'href' ) );

		// Test echo option
		$args = array( 'echo' => 1 );
		ob_start();
		$this->switcher->the_languages( self::$polylang->links, $args );
		$this->assertNotEmpty( ob_get_clean() );

		// Bug fixed in 2.6.3: No span when showing only flags
		$args = array( 'show_names' => 0, 'show_flags' => 1, 'echo' => 0 );
		$switcher = $this->switcher->the_languages( self::$polylang->links, $args );
		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$this->assertEmpty( $xpath->query( '//li/a[@lang="en-US"]/span' )->length );

		// A span is used when shwoing names and flags
		$args = array( 'show_names' => 1, 'show_flags' => 1, 'echo' => 0 );
		$switcher = $this->switcher->the_languages( self::$polylang->links, $args );
		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$this->assertEquals( 'English', $xpath->query( '//li/a[@lang="en-US"]/span' )->item( 0 )->nodeValue );

		// Bug fixed in 2.6.10.
		$args = array( 'hide_current' => 1, 'echo' => 0 );
		$switcher = $this->switcher->the_languages( self::$polylang->links, $args );
		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$this->assertNotFalse( strpos( $xpath->query( '//li' )->item( 0 )->getAttribute( 'class' ), 'lang-item-first' ) );
	}

	/**
	 * Very basic tests for the switcher as dropdown
	 */
	function test_dropdown() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		self::$polylang->model->post->save_translations( $en, compact( 'fr' ) );

		self::$polylang->model->clean_languages_cache(); // FIXME for some reason, I need to clear the cache to get an exact count

		self::$polylang->links->curlang = self::$polylang->model->get_language( 'en' );
		$this->go_to( get_permalink( $en ) );

		$args = array(
			'dropdown' => 1,
			'echo'     => 0,
		);
		$switcher = $this->switcher->the_languages( self::$polylang->links, $args );
		$switcher = mb_convert_encoding( $switcher, 'HTML-ENTITIES', 'UTF-8' ); // Due to "Français"
		$doc = new DomDocument();
		$doc->loadHTML( $switcher );
		$xpath = new DOMXpath( $doc );

		$option = $xpath->query( '//select/option[.="English"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );
		$this->assertNotEmpty( $xpath->query( '//select/option[.="Français"]' )->length );
		$this->assertNotEmpty( $xpath->query( '//script' )->length );
	}
}
