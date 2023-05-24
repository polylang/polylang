<?php

if ( is_multisite() ) :

	class Multisite_Option_Test extends PLL_UnitTestCase {

		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			parent::wpSetUpBeforeClass( $factory );

			self::create_language( 'en_US' );

			require_once POLYLANG_DIR . '/include/api.php';
		}

		public function set_up() {
			parent::set_up();

			$links_model = self::$model->get_links_model();
			$GLOBALS['polylang'] = new PLL_Admin( $links_model );
		}

		public function tear_down() {
			parent::tear_down();

			unset( $GLOBALS['polylang'] );
		}

		/**
		 * @ticket #1685
		 * @see https://github.com/polylang/polylang-pro/issues/1685
		 */
		public function test_translate_blogname() {
			$blog_id = self::factory()->blog->create( array( 'title' => 'The new blog to test' ) );

			$language = self::$model->get_language( 'en' );

			$mo = new PLL_MO();
			$mo->add_entry( $mo->make_entry( 'Some string', 'Some translation' ) );
			$GLOBALS['l10n']['pll_string'] = &$mo;

			PLL()->curlang = $language;

			new PLL_Translate_Option( 'blogname', array(), array( 'context' => 'WordPress' ) );

			$this->assertSame( 'Test Blog', get_site( 1 )->blogname ); // The default blog.
			$this->assertSame( 'The new blog to test', get_site( $blog_id )->blogname ); // The blog that we have created.
		}
	}

endif;
