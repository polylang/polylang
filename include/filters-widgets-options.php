<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Widgets_Filters
 *
 * @since 3.0
 *
 * Add new options to {@see https://developer.wordpress.org/reference/classes/wp_widget/ WP_Widget} and saves them.
 */
class PLL_Filters_Widgets_Options {

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * PLL_Widgets_Filters constructor.
	 *
	 * @since 3.0 Moved actions from PLL_Admin_Filters.
	 *
	 * @param PLL_Base $polylang The Polylang object.
	 * @return void
	 */
	public function __construct( $polylang ) {
		$this->model = $polylang->model;

		add_action( 'in_widget_form', array( $this, 'in_widget_form' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 10, 2 );
	}

	/**
	 * Add the language filter field to the widgets options form.
	 *
	 * @since 3.0 Moved PLL_Admin_Filters.
	 * @since 3.1 Rename lang_choice field name and id to pll_lang as the widget setting.
	 *
	 * @param WP_Widget $widget   The widget instance (passed by reference).
	 * @param null      $return   Return null if new fields are added.
	 * @param array     $instance An array of the widget's settings.
	 * @return void
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$dropdown = new PLL_Walker_Dropdown();

		$dropdown_html = $dropdown->walk(
			array_merge(
				array( (object) array( 'slug' => 0, 'name' => __( 'All languages', 'polylang' ) ) ),
				$this->model->get_languages_list()
			),
			-1,
			array(
				'id' => $widget->get_field_id( 'pll_lang' ),
				'name' => $widget->get_field_name( 'pll_lang' ),
				'class' => 'tags-input pll-lang-choice',
				'selected' => empty( $instance['pll_lang'] ) ? '' : $instance['pll_lang'],
			)
		);

		printf(
			'<p><label for="%1$s">%2$s %3$s</label></p>',
			esc_attr( $widget->get_field_id( 'pll_lang' ) ),
			esc_html__( 'The widget is displayed for:', 'polylang' ),
			$dropdown_html // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}

	/**
	 * Called when widget options are saved.
	 * Saves the language associated to the widget.
	 *
	 * @since 0.3
	 * @since 3.0 Moved from PLL_Admin_Filters.
	 * @since 3.1 Remove unused $old_instance and $widget parameters.
	 *
	 * @param array $instance     The current Widget's options.
	 * @param array $new_instance The new Widget's options.
	 * @return array Widget options.
	 */
	public function widget_update_callback( $instance, $new_instance ) {
		if ( ! empty( $new_instance['pll_lang'] ) && $lang = $this->model->get_language( $new_instance['pll_lang'] ) ) {
			$instance['pll_lang'] = $lang->slug;
		} else {
			unset( $instance['pll_lang'] );
		}

		return $instance;
	}
}
