<?php
abstract class PLL_Context {

	/**
	 * @var PLL_Base
	 */
	protected $polylang;

	/**
	 * Initialize Polylang.
	 *
	 * @param array $settings Polylang settings.
	 */
	public function __construct( array $settings = array() ) {
		global $wp_rewrite;

		$default_lang = get_terms( array( 'taxonomy' => 'language', 'hide_empty' => false, 'orderby' => 'term_id', 'fields' => 'slugs' ) );

		$options = array_merge( PLL_Install::get_default_options(), array( 'default_lang' => reset( $default_lang ) ) );

		if ( isset( $settings['options'] ) && is_array( $settings['options'] ) && ! empty( $settings['options'] ) ) {
			$options = array_merge( $options, $settings['options'] );
		}
		$model = $this->get_model( $options );

		// switch to pretty permalinks
		// useless with plain permalinks, check before running
		if ( isset( $settings['permalink_structure'] ) && ! empty( $settings['permalink_structure'] ) ) {
			$wp_rewrite->init();
			$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
			$wp_rewrite->set_permalink_structure( $settings['permalink_structure'] );
		}

		$links_model = $model->get_links_model();

		// if $static_pages array not empty update WordPress options 'show_on_front', 'page_on_front', 'page_for_posts'.

		$class_name     = $this->get_name();
		$this->polylang = new $class_name( $links_model );

		$model->maybe_create_language_terms();

		/**
		 * Fires after the $polylang object is created and before the API is loaded
		 *
		 * @since 2.0
		 *
		 * @param object $polylang
		 */
		do_action_ref_array( 'pll_pre_init', array( &$this->polylang ) );

		$tests_dir = dirname( __DIR__ ); // `/polylang-pro/tests/phpunit`.
		$root_dir  = dirname( $tests_dir, 3 ); // `/polylang`.
		// Loads the API.
		require_once "{$root_dir}/include/api.php"; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath

		// Loads the modules.
		$load_scripts = glob( "{$root_dir}'/modules/*/load.php", GLOB_NOSORT );
		if ( is_array( $load_scripts ) ) {
			foreach ( $load_scripts as $load_script ) {
				require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}

		$this->polylang->init();
		$GLOBALS['polylang'] = $this->polylang;

		do_action_ref_array( 'pll_init', array( &$this->polylang ) );

		$this->do_wordpress_actions();
	}

	protected function get_model( array $options ) {
		// PLL_Admin_Model for Settings need to be overriden.
		return new PLL_Model( $options );
	}

	protected function do_pll_actions( string $hook_name, ...$args ) {
		global $wp_filter;

		// Backups wp_filter variable.
		$wp_filter_backup = $wp_filter;

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return;
		}

		// Loops on wp_filter global variable and keep only Polylang callbacks.
		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $key => $callback ) {
				if ( ! is_array( $callback['function'] ) || ! isset( $callback['function'][0] ) ) {
					unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $key ] );
					continue;
				}

				if ( is_object( $callback['function'][0] ) ) {
					$callback_name = get_class( $callback['function'][0] );
				} else {
					$callback_name = $callback['function'][0];
				}

				if ( 0 !== strpos( $callback_name, 'PLL_' ) ) {
					unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $key ] );
				}
			}
		}

		do_action( $hook_name, $args );

		// Restores wp_filter variable.
		$wp_filter = $wp_filter_backup;
	}

	abstract protected function do_wordpress_actions();

	abstract protected function get_name();

	public function get() {
		return $this->polylang;
	}
}
