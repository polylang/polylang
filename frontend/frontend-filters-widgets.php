<?php
/**
 * @package Polylang
 */

/**
 * Filters widgets by language on frontend
 *
 * @since 3.1
 */
class PLL_Frontend_Filters_Widgets {
	/**
	 * Internal non persistent cache object.
	 *
	 * @var PLL_Cache
	 */
	public $cache;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->curlang = &$polylang->curlang;
		$this->cache = new PLL_Cache();

		add_filter( 'sidebars_widgets', array( $this, 'sidebars_widgets' ) );
	}

	/**
	 * Remove widgets from sidebars if they are not visible in the current language
	 * Needed to allow is_active_sidebar() to return false if all widgets are not for the current language. See #54
	 *
	 * @since 2.1
	 * @since 2.4 The result is cached as the function can be very expensive in case there are a lot of widgets
	 *
	 * @param array $sidebars_widgets An associative array of sidebars and their widgets
	 * @return array
	 */
	public function sidebars_widgets( $sidebars_widgets ) {
		global $wp_registered_widgets;

		if ( empty( $wp_registered_widgets ) ) {
			return $sidebars_widgets;
		}

		$cache_key         = md5( maybe_serialize( $sidebars_widgets ) );
		$_sidebars_widgets = $this->cache->get( "sidebars_widgets_{$cache_key}" );

		if ( false !== $_sidebars_widgets ) {
			return $_sidebars_widgets;
		}

		$sidebars_widgets = $this->filter_widgets_sidebars( $sidebars_widgets, $wp_registered_widgets );

		$this->cache->set( "sidebars_widgets_{$cache_key}", $sidebars_widgets );

		return $sidebars_widgets;
	}

	/**
	 * Method that handles the removal of widgets in the sidebars depending on their display language.
	 *
	 * @since 3.1
	 *
	 * @param array  $widget_data      An array containing the widget data
	 * @param array  $sidebars_widgets An associative array of sidebars and their widgets
	 * @param string $sidebar          Sidebar name
	 * @param int    $key              Widget number
	 * @return array                   An associative array of sidebars and their widgets
	 */
	public function handle_widget_in_sidebar_callback( $widget_data, $sidebars_widgets, $sidebar, $key ) {
		// Remove the widget if not visible in the current language
		if ( ! empty( $widget_data['settings'][ $widget_data['number'] ]['pll_lang'] ) && $widget_data['settings'][ $widget_data['number'] ]['pll_lang'] !== $this->curlang->slug ) {
			unset( $sidebars_widgets[ $sidebar ][ $key ] );
		}
		return $sidebars_widgets;
	}

	/**
	 * Browse the widgets sidebars and sort the ones that should be displayed or not.
	 *
	 * @since 3.1
	 *
	 * @param  array $sidebars_widgets       An associative array of sidebars and their widgets
	 * @param  array $wp_registered_widgets  Array of all registered widgets.
	 * @return array                         An associative array of sidebars and their widgets
	 */
	public function filter_widgets_sidebars( $sidebars_widgets, $wp_registered_widgets ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $key => $widget ) {
				if ( ! $this->is_widget_object( $wp_registered_widgets, $widget ) ) {
					continue;
				}

				$widget_data = $this->get_widget_data( $wp_registered_widgets, $widget );

				$sidebars_widgets = $this->handle_widget_in_sidebar_callback( $widget_data, $sidebars_widgets, $sidebar, $key );
			}
		}
		return $sidebars_widgets;
	}

	/**
	 * Test if the widget is an object.
	 *
	 * @since 3.1
	 *
	 * @param  array  $wp_registered_widgets Array of all registered widgets.
	 * @param  string $widget                String that identifies the widget.
	 * @return bool                          True if object, false otherwise.
	 */
	protected function is_widget_object( $wp_registered_widgets, $widget ) {
		// Nothing can be done if the widget is created using pre WP2.8 API :(
		// There is no object, so we can't access it to get the widget options
		return isset( $wp_registered_widgets[ $widget ]['callback'] ) &&
				is_array( $wp_registered_widgets[ $widget ]['callback'] ) &&
				isset( $wp_registered_widgets[ $widget ]['callback'][0] ) &&
				is_object( $wp_registered_widgets[ $widget ]['callback'][0] ) &&
				method_exists( $wp_registered_widgets[ $widget ]['callback'][0], 'get_settings' );
	}

	/**
	 * Get widgets settings and number.
	 *
	 * @since 3.1
	 *
	 * @param array  $wp_registered_widgets Array of all registered widgets.
	 * @param string $widget                String that identifies the widget.
	 * @return array An array containing the widget settings and number.
	 */
	protected function get_widget_data( $wp_registered_widgets, $widget ) {
		$widget_settings = $wp_registered_widgets[ $widget ]['callback'][0]->get_settings();
		$number          = $wp_registered_widgets[ $widget ]['params'][0]['number'];

		return array(
			'settings' => $widget_settings,
			'number'   => $number,
		);
	}
}
