<?php

use WP_Syntex\Polylang\Widgets\Languages;

class Widget_Languages_Test extends PLL_UnitTestCase {
	use PLL_Widgets_Trait;

	private const SIDEBAR_ID = 'sidebar-1';
	private const WIDGET_ID = 'polylang-2';

	private static $widget_index;
	private static $posts;

	private $pll_options;

	/**
	 * @param PLL_UnitTest_Factory $factory
	 * @return void
	 */
	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 3 );

		self::$widget_index = (int) preg_replace( '/[^\d]/', '', self::WIDGET_ID );

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

		// Cleanup: the widget must be registered after its settings are stored in the DB.
		// See also `clean_up_global_scope()`.
		unregister_widget( Languages::class );

		$this->pll_options = $this->create_options(
			array(
				'default_lang' => 'en',
			)
		);
	}

	/**
	 * Makes sure that the widget is correctly registered.
	 *
	 * @return void
	 */
	public function test_widget_should_be_registered(): void {
		global $wp_widget_factory;

		$this->init_frontend();
		do_action( 'widgets_init' );

		$this->assertInstanceOf( WP_Widget_Factory::class, $wp_widget_factory );
		$this->assertIsArray( $wp_widget_factory->widgets );
		$this->assertContainsObjectOfType(
			Languages::class,
			$wp_widget_factory->widgets,
			'WP_Widget_Factory::$widgets should contain a language switcher widget.'
		);
	}

	/**
	 * Makes sure that all settings are taken into account when displaying the widget.
	 *
	 * @return void
	 */
	public function test_display_widget_with_non_default_settings(): void {
		$this->store_settings(
			array(
				'show_wrapper' => false,             // Will be forced to `true`.
				'unique_id'    => 'foobar',          // Will be overwritten to `pll-switcher-widget-2`.
				'link_classes' => array( 'bidule' ), // Added by 3rd party code, should be rendered.
			)
		);
		$this->init_frontend();
		$this->register_sidebar();
		$this->add_widget_to_sidebar();

		$this->go_to( get_permalink( self::$posts['en'] ) ); // Allows to set `hide_if_no_translation` to `true` and still get at least one <li>.
		do_action( 'widgets_init' );

		ob_start();
		dynamic_sidebar();
		$widgets = ob_get_clean();

		// The switcher should display only 1 item: FR.
		$xpath       = $this->get_domxpath( $widgets );
		$fr_language = $this->pll_model->get_language( 'fr' );

		$sidebars = $xpath->query( sprintf( '//li[@id="%s"]', self::WIDGET_ID ) );
		$this->assertSame( 1, $sidebars->count() );
		$sidebar = $sidebars->item( 0 );

		$titles = $xpath->query( '//h2', $sidebar );
		$this->assertSame( 1, $titles->count() );
		$this->assertSame( 'Widget Horizontal Layout', $titles->item( 0 )->nodeValue );

		$wrappers = $xpath->query( sprintf( '//div[@id="pll-switcher-widget-%d"]', self::$widget_index ), $sidebar );
		$this->assertSame( 1, $wrappers->count() );
		$wrapper = $wrappers->item( 0 );
		$this->assertSame( 'Choose a language', $wrapper->getAttribute( 'aria-label' ) );
		$this->assertSameSets(
			array( 'pll-switcher', 'pll-layout-horizontal', 'pll-alignment-stretched', 'pll-aspect-ratio-11' ),
			explode( ' ', $wrapper->getAttribute( 'class' ) )
		);

		$lis = $xpath->query( '//ul/li', $wrapper );
		$this->assertSame( 1, $lis->count() ); // hide_current = true (en) + hide_empty = true (de).
		$li = $lis->item( 0 );
		$this->assertSameSets(
			array( 'lang-item', 'lang-item-' . $fr_language->term_id, 'lang-item-fr', 'lang-item-first' ),
			explode( ' ', $li->getAttribute( 'class' ) )
		);

		$links = $xpath->query( '//a', $li );
		$this->assertSame( 1, $links->count() );
		$link = $links->item( 0 );
		$this->assertSame( 'fr-FR', $link->getAttribute( 'lang' ) );
		$this->assertSame( 'fr-FR', $link->getAttribute( 'hreflang' ) );
		$this->assertSame( '', $link->getAttribute( 'aria-current' ) );
		$this->assertSame( $fr_language->get_home_url(), $link->getAttribute( 'href' ) );
		$this->assertSame( 'bidule', $link->getAttribute( 'class' ) );

		$spans = $xpath->query( '//span', $link );
		$this->assertSame( 2, $spans->count() );
		$this->assertSame( 'pll-switcher-flag', $spans->item( 0 )->getAttribute( 'class' ) );
		$this->assertSame( 'pll-switcher-label', $spans->item( 1 )->getAttribute( 'class' ) );

		$flags = $xpath->query( '//img', $spans->item( 0 ) );
		$this->assertSame( 1, $flags->count() );
		$this->assertSame( '', $flags->item( 0 )->getAttribute( 'alt' ) );

		$this->assertSame( 'FR', $spans->item( 1 )->nodeValue );
	}

	/**
	 * Makes sure that all settings are taken into account when displaying the form.
	 *
	 * @return void
	 */
	public function test_display_form_with_non_default_settings(): void {
		global $wp_registered_widgets;

		$this->store_settings();
		$this->init_admin();
		$this->register_sidebar();
		$this->add_widget_to_sidebar();

		set_current_screen( 'widgets' );
		do_action( 'widgets_init' );

		$widget = $wp_registered_widgets[ self::WIDGET_ID ]['callback'][0];

		ob_start();
		$widget->form_callback( self::$widget_index );
		$form = ob_get_clean();

		$prefix = 'widget-polylang-' . self::$widget_index;
		$this->assertStringContainsString( "id=\"{$prefix}-title\"", $form );
		$this->assertStringContainsString( "id=\"{$prefix}-layout\"", $form );
		$this->assertMatchesRegularExpression( '/<option value=["\']horizontal["\'] selected=["\']selected["\']>/', $form );
		$this->assertStringContainsString( "id=\"{$prefix}-alignment\"", $form );
		$this->assertMatchesRegularExpression( '/<option value=["\']stretched["\'] selected=["\']selected["\']>/', $form );
		$this->assertMatchesRegularExpression( "/id=[\"']{$prefix}-show_flags[\"'][^>]* checked=[\"']checked[\"']/", $form );
		$this->assertStringContainsString( "id=\"{$prefix}-flag_aspect_ratio\"", $form );
		$this->assertMatchesRegularExpression( '/<option value=["\']1:1["\'] selected=["\']selected["\']>/', $form );
		$this->assertStringContainsString( "id=\"{$prefix}-show_labels\"", $form );
		$this->assertMatchesRegularExpression( '/<option value=["\']codes["\'] selected=["\']selected["\']>/', $form );
		$this->assertMatchesRegularExpression( "/id=[\"']{$prefix}-force_home[\"'][^>]* checked=[\"']checked[\"']/", $form );
		$this->assertMatchesRegularExpression( "/id=[\"']{$prefix}-hide_current[\"'][^>]* checked=[\"']checked[\"']/", $form );
		$this->assertMatchesRegularExpression( "/id=[\"']{$prefix}-hide_if_no_translation[\"'][^>]* checked=[\"']checked[\"']/", $form );
	}

	/**
	 * Stores the widget's options into the database.
	 *
	 * @param array $options Optional. Options.
	 * @return void
	 */
	private function store_settings( array $options = array() ): void {
		$options = array_merge(
			array(
				'layout'                 => 'horizontal',
				'alignment'              => 'stretched',
				'show_flags'             => true,
				'flag_aspect_ratio'      => '1:1',
				'show_labels'            => 'codes',
				'force_home'             => true,
				'hide_current'           => true,
				'hide_if_no_translation' => true,
				'title'                  => 'Widget Horizontal Layout',
				'dropdown'               => 0,
				'show_names'             => 1,
			),
			$options
		);
		$widgets_option = array(
			self::$widget_index => $options,
			'_multiwidget'      => 1,
		);
		update_option( 'widget_polylang', $widgets_option );
	}

	private function register_sidebar(): void {
		register_sidebar(
			array(
				'name'          => 'Sidebar',
				'id'            => self::SIDEBAR_ID,
				'before_widget' => '<li id="%1$s" class="widget %2$s">',
				'after_widget'  => '</li>',
				'before_title'  => '<h2 class="widgettitle">',
				'after_title'   => '</h2>',
			)
		);
	}

	private function add_widget_to_sidebar(): void {
		wp_assign_widget_to_sidebar( self::WIDGET_ID, self::SIDEBAR_ID );
	}

	private function init_frontend(): PLL_Frontend {
		$this->pll_model     = new PLL_Model( $this->pll_options );
		$links_model         = $this->pll_model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Frontend( $links_model );
		$GLOBALS['polylang']->init();
		return $GLOBALS['polylang'];
	}

	private function init_admin(): PLL_Admin {
		$this->pll_model     = new PLL_Admin_Model( $this->pll_options );
		$links_model         = $this->pll_model->get_links_model();
		$GLOBALS['polylang'] = new PLL_Admin( $links_model );
		$GLOBALS['polylang']->init();
		return $GLOBALS['polylang'];
	}

	/**
	 * Asserts that an array contains at least one object of the given type.
	 *
	 * @param string $type    Class name.
	 * @param array  $array   List.
	 * @param string $message Optional. Message to display in case of failure.
	 * @return void
	 */
	private function assertContainsObjectOfType( string $type, array $array, string $message = '' ): void {
		$found = false;

		foreach ( $array as $obj ) {
			if ( get_class( $obj ) === $type ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, $message );
	}

	/**
	 * Returns an instance of `DOMXpath`.
	 *
	 * @param string $html HTML as a string.
	 * @return DOMXpath
	 */
	private function get_domxpath( string $html ): DOMXpath {
		$this->assertNotSame( '', $html );
		$doc = new DOMDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR );
		return new DOMXpath( $doc );
	}
}
