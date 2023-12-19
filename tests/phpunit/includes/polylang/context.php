<?php

abstract class PLL_Context {

	/**
	 * @var PLL_Base
	 */
	protected $polylang;

	/**
	 * Initializes Polylang.
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

		// Switch to pretty permalinks.
		// Useless with plain permalinks, check before running.
		if ( isset( $settings['permalink_structure'] ) && ! empty( $settings['permalink_structure'] ) ) {
			$wp_rewrite->init();
			$wp_rewrite->extra_rules_top = array(); // brute force since WP does not do it :(
			$wp_rewrite->set_permalink_structure( $settings['permalink_structure'] );
		}

		// If `$static_pages` array not empty update WordPress options 'show_on_front', 'page_on_front', 'page_for_posts'.

		$class_name     = $this->get_name();
		$this->polylang = Polylang::_init( $class_name, $model );

		$this->do_wordpress_actions();
	}

	/**
	 * Returns the model according to the context.
	 *
	 * @since 3.6
	 *
	 * @param array $options Polylang options.
	 * @return PLL_Model
	 */
	protected function get_model( array $options ): PLL_Model {
		return new PLL_Model( $options );
	}

	/**
	 * Removes non-polylang callbacks in `wp_filter` before running the `do_action` so that only polylang filters are run.
	 *
	 * @since 3.6
	 *
	 * @global WP_Hook[] $wp_filter Stores all the filters and actions.
	 *
	 * @param string $hook_name The name of the action to be executed.
	 * @param mixed  ...$args   Additional arguments which are passed on to the functions hooked to the action.
	 */
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
				if ( is_string( $callback['function'] ) && 0 !== strpos( $callback['function'], 'pll_' ) ) {
					unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $key ] );
					continue;
				}

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

		foreach ( $wp_filter as $filter_name => $filter ) {
			if ( empty( $wp_filter_backup[ $filter_name ] ) ) {
				// Keep filters adding by Polylang after the previous `do_action`.
				$wp_filter_backup[ $filter_name ] = $filter;
			}
		}

		// Restores wp_filter variable.
		$wp_filter = $wp_filter_backup;
	}

	/**
	 * Executes Polylang actions on filters that need to be run according to context.
	 * Also refresh WordPress’ rewrite rules.
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	abstract protected function do_wordpress_actions();

	/**
	 * Gets the context class name.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	abstract protected function get_name(): string;

	/**
	 * Gets the Polylang instance.
	 *
	 * @since 3.6
	 *
	 * @return PLL_Base
	 */
	public function get(): PLL_Base {
		return $this->polylang;
	}
}
