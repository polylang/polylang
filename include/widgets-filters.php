<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Widgets_Filters
 *
 * Registers filters for {@see https://developer.wordpress.org/reference/classes/wp_widget/ WP_Widget} methods.
 */
class PLL_Widgets_Filters {

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * PLL_Widgets_Filters constructor.
	 *
	 * @param PLL_Base $polylang
	 * @return void
	 *
	 * @since 3.0 Moved actions from PLL_Admin_Filters.
	 */
	public function __construct( $polylang ) {
		$this->model = $polylang->model;

		add_action( 'in_widget_form', array( $this, 'in_widget_form' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 10, 4 );
	}

	/**
	 * @param WP_Widget $widget
	 * @param null      $return
	 * @param array     $instance
	 * @return void
	 *
	 * @since 3.0 Moved PLL_Admin_Filters
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$dropdown = new PLL_Walker_Dropdown();

		$dropdown_html = $dropdown->walk(
			array_merge(
				array( (object) array('slug' => 0, 'name' => __( 'All languages', 'polylang' )) ),
				$this->model->get_languages_list()
			),
			-1,
			array(
				'name' => $widget->id . '_lang_choice',
				'class' => 'tags-input pll-lang-choice',
				'selected' => empty( $instance['pll_lang'] ) ? '' : $instance['pll_lang'],
			)
		);

		printf(
			'<p><label for="%1$s">%2$s %3$s</label></p>',
			esc_attr( $widget->id . '_lang_choice' ),
			esc_html__( 'The widget is displayed for:', 'polylang' ),
			$dropdown_html // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}

	/**
	 * Called when widget options are saved.
	 * Saves the language associated to the widget.
	 *
	 * @param array     $instance Widget options.
	 * @param array     $new_instance Not used.
	 * @param array     $old_instance Not used.
	 * @param WP_Widget $widget WP_Widget object.
	 * @return array Widget options.
	 * @since 0.3
	 * @since 3.0 Moved from PLL_Admin_Filters
	 */
	public function widget_update_callback( $instance, $new_instance, $old_instance, $widget ) {
		$key = $widget->id . '_lang_choice';

		if ( ! empty( $_POST[ $key ] ) && $lang = $this->model->get_language( sanitize_key( $_POST[ $key ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$instance['pll_lang'] = $lang->slug;
		} else {
			unset( $instance['pll_lang'] );
		}

		return $instance;
	}
}
