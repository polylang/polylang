<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Widgets_Filters
 *
 * @since 3.0
 *
 * Adds new options to {@see https://developer.wordpress.org/reference/classes/wp_widget/ WP_Widget} and saves them.
 */
class PLL_Admin_Filters_Widgets_Options extends PLL_Filters_Widgets_Options {
	/**
	 * Modifies the widgets forms to add our language dropdown list.
	 *
	 * @since 0.3
	 * @since 3.0 Moved from PLL_Admin_Filters
	 *
	 * @param WP_Widget $widget Widget instance.
	 * @param null      $return Not used.
	 * @param array     $instance Widget settings.
	 * @return void
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$screen = get_current_screen();

		// Test the Widgets screen and the Customizer to avoid displaying the option in page builders
		// Saving the widget reloads the form. And curiously the action is in $_REQUEST but neither in $_POST, nor in $_GET.
		if ( ( isset( $screen ) && in_array( $screen->base, array( 'widgets', 'appearance_page_gutenberg-widgets' ) ) ) || ( isset( $_REQUEST['action'] ) && 'save-widget' === $_REQUEST['action'] ) || isset( $GLOBALS['wp_customize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			parent::in_widget_form( $widget, $return, $instance );
		}
	}

	/**
	 * Called when widget options are saved.
	 * Saves the language associated to the widget.
	 *
	 * @since 3.0
	 *
	 * @param array     $instance The current Widget's options.
	 * @param array     $new_instance The new Widget's options.
	 * @param array     $old_instance Not used.
	 * @param WP_Widget $widget The Widget object.
	 * @return array The processed Widget options.
	 */
	public function widget_update_callback( $instance, $new_instance, $old_instance, $widget ) {
		$key = $this->get_language_key( $widget );
		if ( ! empty( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$new_instance[ $key ] = sanitize_key( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return parent::widget_update_callback( $instance, $new_instance, $old_instance, $widget );
	}

}
