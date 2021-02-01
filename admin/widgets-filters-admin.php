<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Widgets_Filters
 */
class PLL_Widgets_Filters_Admin extends PLL_Widgets_Filters {


	/**
	 * Modifies the widgets forms to add our language dropdown list.
	 *
	 * @param WP_Widget $widget Widget instance.
	 * @param null      $return Not used.
	 * @param array     $instance Widget settings.
	 * @return void
	 * @since 0.3
	 * @since 3.0 Moved from PLL_Admin_Filters
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$screen = get_current_screen();

		// Test the Widgets screen and the Customizer to avoid displaying the option in page builders
		// Saving the widget reloads the form. And curiously the action is in $_REQUEST but neither in $_POST, nor in $_GET.
		if ( ( isset( $screen ) && 'widgets' === $screen->base ) || ( isset( $_REQUEST['action'] ) && 'save-widget' === $_REQUEST['action'] ) || isset( $GLOBALS['wp_customize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			parent::in_widget_form( $widget, $return, $instance );
		}
	}

}
