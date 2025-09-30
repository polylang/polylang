<?php

class Widgets_Filter_Test extends PLL_UnitTestCase {

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		$this->links_model = self::$model->get_links_model();

		self::require_api(); // Usually loaded only if an instance of Polylang exists

		update_option(
			'widget_search',
			array(
				2              => array( 'title' => '' ),
				'_multiwidget' => 1,
			)
		);
	}

	/**
	 * Copied from WP widgets tests
	 */
	public function clean_up_global_scope() {
		global $_wp_sidebars_widgets, $wp_widget_factory, $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

		$_wp_sidebars_widgets = array();
		$wp_registered_sidebars = array();
		$wp_registered_widgets = array();
		$wp_registered_widget_controls = array();
		$wp_registered_widget_updates = array();
		$wp_widget_factory->widgets = array();

		parent::clean_up_global_scope();
	}

	public function test_form() {
		global $wp_registered_widgets;

		set_current_screen( 'widgets' );
		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];

		$pll_admin = new PLL_Admin( $this->links_model );
		new PLL_Admin_Filters_Widgets_Options( $pll_admin );

		ob_start();
		$wp_widget_search->form_callback( 2 );
		$form = ob_get_clean();
		$this->assertNotFalse( strpos( $form, 'widget-search-2-pll_lang' ) );
	}

	protected function update_lang_choice( $widget, $lang ) {
		$pll_admin = new PLL_Admin( $this->links_model );
		new PLL_Admin_Filters_Widgets_Options( $pll_admin );


		$_POST = array(
			'widget-id'     => $widget->id,
			'id_base'       => $widget->id_base,
			'widget_number' => $widget->number,
			'multi_number'  => '',
		);

		$_POST[ 'widget-' . $widget->id_base ][2]['pll_lang'] = $lang;
		$widget->update_callback();
	}

	public function test_display_with_filter() {
		global $wp_registered_widgets;

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 'en' );

		$frontend = new PLL_Frontend( $this->links_model );
		new PLL_Frontend_Filters( $frontend );

		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
		$frontend->curlang = self::$model->get_language( 'en' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );

		$frontend->curlang = self::$model->get_language( 'fr' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_display_with_no_filter() {
		global $wp_registered_widgets;

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 0 );

		$frontend = new PLL_Frontend( $this->links_model );
		new PLL_Frontend_Filters( $frontend );

		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );
		$frontend->curlang = self::$model->get_language( 'en' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );

		$frontend->curlang = self::$model->get_language( 'fr' );
		ob_start();
		$wp_widget_search->display_callback( $args, 2 );
		$this->assertNotEmpty( ob_get_clean() );
	}


	public function test_widget_media_image() {
		self::$model->options['media_support'] = 1;
		$pll_admin = new PLL_Admin( $this->links_model );

		$pll_admin->filters_media = new PLL_Admin_Filters_Media( $pll_admin );
		$pll_admin->posts = new PLL_CRUD_Posts( $pll_admin );

		$pll_admin->pref_lang = self::$model->get_language( 'en' );
		$filename = __DIR__ . '/../data/image.jpg';

		$en = self::factory()->attachment->create_upload_object( $filename );
		wp_update_post(
			array(
				'ID'           => $en,
				'post_title'   => 'Test image EN',
				'post_excerpt' => 'Caption EN',
			)
		);
		update_post_meta( $en, '_wp_attachment_image_alt', 'Alt text EN' );

		$fr = $pll_admin->model->post->create_media_translation( $en, 'fr' );
		wp_update_post(
			array(
				'ID'           => $fr,
				'post_title'   => 'Test image FR',
				'post_excerpt' => 'Caption FR',
			)
		);
		update_post_meta( $fr, '_wp_attachment_image_alt', 'Alt text FR' );

		// Switch to frontend
		$frontend = new PLL_Frontend( $this->links_model );
		$GLOBALS['polylang'] = $frontend; // We use PLL().
		new PLL_Frontend_Filters( $frontend );

		$frontend->curlang = self::$model->get_language( 'fr' );

		$widget = new WP_Widget_Media_Image();
		$args = array( 'before_title' => '', 'after_title' => '', 'before_widget' => '', 'after_widget' => '' );

		// Empty fields in Edit Image
		$instance = array( 'attachment_id' => $en, 'caption' => null ); // Need to explicitly set 'caption' to null since WP 4.9. See #42350
		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( "wp-image-{$fr}", $output ); // CSS class
		$this->assertStringContainsString( 'Alt text FR', $output );
		$this->assertStringContainsString( 'Caption FR', $output );
		$this->assertStringNotContainsString( 'Test image FR', $output );

		// Edit Image fields are filled
		$instance = array( 'attachment_id' => $en, 'alt' => 'Custom alt', 'caption' => 'Custom caption', 'image_title' => 'Custom title' );
		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Alt text FR', $output );
		$this->assertStringContainsString( 'Caption FR', $output );
		$this->assertStringContainsString( 'Test image FR', $output );

		unset( $GLOBALS['polylang'] );
	}

	public function test_wp_get_sidebars_widgets() {
		global $wp_registered_widgets;

		update_option(
			'sidebars_widgets',
			array(
				'wp_inactive_widgets' => array(),
				'sidebar-1'           => array( 'search-2' ),
			)
		);

		wp_widgets_init();
		$wp_widget_search = $wp_registered_widgets['search-2']['callback'][0];
		$this->update_lang_choice( $wp_widget_search, 'en' );

		$frontend = new PLL_Frontend( $this->links_model );
		$frontend->filters_widgets = new PLL_Frontend_Filters_Widgets( $frontend );
		$frontend->curlang = self::$model->get_language( 'en' );

		$sidebars = wp_get_sidebars_widgets();
		$this->assertTrue( in_array( 'search-2', $sidebars['sidebar-1'] ) );

		$frontend->curlang = self::$model->get_language( 'fr' );
		$frontend->filters_widgets->cache->clean();

		$sidebars = wp_get_sidebars_widgets();
		$this->assertFalse( in_array( 'search-2', $sidebars['sidebar-1'] ) );
	}

	public function test_widgets_language_filter_is_not_displayed_for_page_builders() {
		set_current_screen( 'post' );
		$options = self::create_options(
			array(
				'default_lang' => 'en',
			)
		);
		$model = new PLL_Admin_Model( $options );
		$links_model = new PLL_Links_Default( $model );
		$polylang = new PLL_Admin( $links_model );

		new PLL_Admin_Filters_Widgets_Options( $polylang );

		$widget_mock = new WP_Widget( 'test_widget', 'Test Widget' );
		$widget_mock->_set( 1 );

		ob_start();
		do_action_ref_array( 'in_widget_form', array( $widget_mock, array(), array() ) );
		$widget_form = ob_get_clean();

		$this->assertEmpty( $widget_form );
	}
}
