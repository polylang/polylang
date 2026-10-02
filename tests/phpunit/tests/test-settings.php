<?php

class Settings_Test extends PLL_UnitTestCase {
	use PLL_Handle_WP_Redirect_Trait;

	/**
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		$links_model     = self::$model->get_links_model();
		$pll_admin = new PLL_Admin( $links_model );
		$admin_default_term = new PLL_Admin_Default_Term( $pll_admin );
		$admin_default_term->add_hooks();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	public function set_up() {
		parent::set_up();

		global $pagenow, $hook_suffix, $plugin_page;

		// Setup globals.
		$_GET['page'] = 'mlang';
		$pagenow      = 'admin.php'; // Set $pagenow so `get_admin_page_parent()` doesn't throw PHP deprecation notice with PHP 8.5.
		$hook_suffix  = 'settings_page_mlang';
		$plugin_page  = 'mlang';
		get_admin_page_title();
		set_current_screen();

		// Avoid an API request triggered by wp_get_available_translations() called in languages_page().
		add_filter( 'pre_site_transient_available_translations', '__return_empty_array' );
	}

	/**
	 * Bug introduced and fixed in 1.9alpha.
	 */
	public function test_edit_language() {
		$lang = self::$model->get_language( 'fr' );

		// Setup globals.
		$_GET['lang']           = $lang->term_id;
		$_GET['pll_action']     = 'edit';
		$_REQUEST['pll_action'] = 'edit'; // languages_page() tests $_REQUEST.

		ob_start();
		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );
		$pll_env->languages_page();
		$out = ob_get_clean();
		$doc = new DomDocument();
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $out );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="lang_id"]' );
		$this->assertEquals( $lang->term_id, $input->item( 0 )->getAttribute( 'value' ) ); // Hidden field.

		$input = $xpath->query( '//input[@name="name"]' );
		$this->assertEquals( 'Français', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@name="locale"]' );
		$this->assertEquals( 'fr_FR', $input->item( 0 )->getAttribute( 'value' ) );

		$input = $xpath->query( '//input[@name="slug"]' );
		$this->assertEquals( 'fr', $input->item( 0 )->getAttribute( 'value' ) );

		$option = $xpath->query( '//select[@name="flag"]/option[.="France"]' );
		$this->assertEquals( 'selected', $option->item( 0 )->getAttribute( 'selected' ) );
	}

	public function test_notice_for_objects_with_no_lang() {
		$links_model = self::$model->get_links_model();
		new PLL_Settings( $links_model );
		do_action( 'admin_menu' );
		do_action( 'load-languages_page_mlang' );

		ob_start();
		$id = self::factory()->post->create();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotEmpty( $out );

		ob_start();
		self::$model->post->set_language( $id, 'en' );
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );

		ob_start();
		$id = self::factory()->term->create();
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertNotEmpty( $out );

		ob_start();
		self::$model->term->set_language( $id, 'en' );
		do_action( 'admin_notices' );
		$out = ob_get_clean();

		$this->assertEmpty( $out );
	}

	/**
	 * Bug introduced in 2.1-dev.
	 */
	public function test_display_settings_errors() {
		add_settings_error( 'test', 'test', 'ERROR' );
		$links_model = self::$model->get_links_model();
		$pll_env = new PLL_Settings( $links_model );

		ob_start();
		$pll_env->languages_page();
		$out = ob_get_clean();

		$this->assertNotFalse( strpos( $out, 'ERROR' ) );
	}

	/**
	 * Bug introduced in 3.9-dev.
	 */
	public function test_strings_are_saved_when_submitted() {
		$lang = self::$model->get_language( 'fr' );

		PLL_Admin_Strings::register_string( 'Test', 'Some string' );

		$_GET['page'] = 'mlang_strings';
		$_POST = array(
			'pll_action' => 'string-translation',
			'_wpnonce_string-translation' => wp_create_nonce( 'string-translation' ),
			'submit' => 'Save changes',
			'translation' => array(
				'fr' => array(
					md5( 'Some string' ) => 'The translation',
				),
			),
		);

		$_REQUEST = array_merge( $_GET, $_POST );

		$links_model = self::$model->get_links_model();
		$this->assert_redirect( array( new PLL_Settings( $links_model ), 'languages_page' ) );

		$mo = new PLL_MO();
		$mo->import_from_db( $lang );
		$this->assertSame( 'The translation', $mo->translate( 'Some string' ) );
	}

	public function test_update_language_from_settings_with_fallbacks() {
		$filter = function ( $lang_metas, $args ) {
			if ( ! empty( $args['fallbacks'] ) && is_string( $args['fallbacks'] ) ) {
				$fallbacks = array_map( 'trim', explode( ',', $args['fallbacks'] ) );
				$fallbacks = array_values( array_diff( array_unique( $fallbacks ), array( $args['locale'] ) ) );

				return array_merge( $lang_metas, array( 'fallbacks' => $fallbacks ) );
			}

			if ( empty( $args['lang_id'] ) ) {
				return array_merge( $lang_metas, array( 'fallbacks' => array( 'es_ES' ) ) );
			}

			return $lang_metas;
		};

		add_filter( 'pll_language_metas', $filter, 10, 2 );
		$de = self::create_language( 'de_DE' );

		self::$model->clean_languages_cache();
		$de = self::$model->get_language( 'de' );

		$_POST = array(
			'pll_action'        => 'update',
			'_wpnonce_add-lang' => wp_create_nonce( 'add-lang' ),
			'lang_id'           => $de->term_id,
			'name'              => 'Deutsch',
			'slug'              => 'de',
			'locale'            => 'de_DE',
			'rtl'               => '0',
			'term_group'        => $de->term_group,
			'flag'              => $de->flag_code,
			'fallbacks'         => 'es_ES, de_AT',
		);

		$_REQUEST = array_merge( $_GET, $_POST );

		$links_model = self::$model->get_links_model();
		$pll_env     = new PLL_Settings( $links_model );
		$this->assert_redirect( array( $pll_env, 'handle_actions' ), array( 'update' ) );

		self::$model->clean_languages_cache();
		$de = self::$model->get_language( 'de' );

		$this->assertEqualSets( array( 'es_ES', 'de_AT' ), $de->fallbacks );

		remove_filter( 'pll_language_metas', $filter, 10 );
	}

	public function test_translation_updates_form_when_updates_available() {
		wp_set_current_user( 1 );

		$update_plugins = (object) array(
			'translations' => array(
				(object) array(
					'type'     => 'plugin',
					'slug'     => 'polylang/polylang.php',
					'language' => 'fr_FR',
					'version'  => '1.0',
					'updated'  => '2020-01-01',
					'package'  => 'https://example.com/fr_FR.zip',
				),
			),
		);

		add_filter(
			'pre_site_transient_update_plugins',
			static function () use ( $update_plugins ) {
				return $update_plugins;
			}
		);

		$links_model = self::$model->get_links_model();
		$pll_env     = new PLL_Settings( $links_model );

		$method = new ReflectionMethod( PLL_Settings::class, 'get_translation_updates_form_markup' );
		$method->setAccessible( true );
		$markup = $method->invoke( $pll_env );

		$this->assertStringContainsString( 'upgrade-translations', $markup );
		$this->assertStringContainsString( 'do-translation-upgrade', $markup );
		$this->assertStringContainsString( 'Update language packs', $markup );
	}

	public function test_translation_updates_form_empty_without_updates() {
		wp_set_current_user( 1 );

		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
		delete_site_transient( 'update_core' );

		$links_model = self::$model->get_links_model();
		$pll_env     = new PLL_Settings( $links_model );

		$method = new ReflectionMethod( PLL_Settings::class, 'get_translation_updates_form_markup' );
		$method->setAccessible( true );

		$this->assertSame( '', $method->invoke( $pll_env ) );
	}

	public function test_translation_updates_info_notice_when_updates_available() {
		wp_set_current_user( 1 );

		$update_plugins = (object) array(
			'translations' => array(
				(object) array(
					'type'     => 'plugin',
					'slug'     => 'polylang/polylang.php',
					'language' => 'fr_FR',
					'version'  => '1.0',
					'updated'  => '2020-01-01',
					'package'  => 'https://example.com/fr_FR.zip',
				),
			),
		);

		add_filter(
			'pre_site_transient_update_plugins',
			static function () use ( $update_plugins ) {
				return $update_plugins;
			}
		);

		$links_model = self::$model->get_links_model();
		$pll_env     = new PLL_Settings( $links_model );

		$method = new ReflectionMethod( PLL_Settings::class, 'maybe_add_translation_updates_notice' );
		$method->setAccessible( true );
		$method->invoke( $pll_env );

		$errors = get_settings_errors( 'polylang' );
		$codes  = wp_list_pluck( $errors, 'code' );

		$this->assertContains( 'pll_translation_updates_available', $codes );

		$notice = wp_list_filter( $errors, array( 'code' => 'pll_translation_updates_available' ) );
		$notice = reset( $notice );

		$this->assertSame( 'info', $notice['type'] );
		$this->assertStringContainsString( 'upgrade-translations', $notice['message'] );
	}
}
