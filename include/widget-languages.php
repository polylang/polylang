<?php
/**
 * @package Polylang
 */

/**
 * The language switcher widget
 *
 * @since 0.1
 *
 * @extends WP_Widget<T>
 * @phpstan-template T of array{
 *     title: string,
 *     dropdown: 0|1,
 *     show_names: 0|1,
 *     show_flags: 0|1,
 *     force_home: 0|1,
 *     hide_current: 0|1,
 *     hide_if_no_translation: 0|1
 * }
 */
class PLL_Widget_Languages extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct(
			'polylang',
			__( 'Language switcher', 'polylang' ),
			array(
				'description'                 => __( 'Displays a language switcher', 'polylang' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Displays the widget
	 *
	 * @since 0.1
	 *
	 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 * @return void
	 *
	 * @phpstan-param array{
	 *     name: string,
	 *     id: string,
	 *     description: string,
	 *     class: string,
	 *     before_widget: string,
	 *     after_widget: string,
	 *     before_title: string,
	 *     after_title: string,
	 *     before_sidebar: string,
	 *     after_sidebar: string,
	 *     show_in_rest: boolean,
	 *     widget_id: string,
	 *     widget_name: string
	 * } $args
	 * @phpstan-param T $instance
	 */
	public function widget( $args, $instance ) {
		// Sets a unique id for dropdown.
		$instance['dropdown'] = empty( $instance['dropdown'] ) ? 0 : $this->id;
		$instance['echo']     = 0;
		$instance['raw']      = 0;
		$list                 = pll_the_languages( $instance );

		if ( $list ) {
			$title = empty( $instance['title'] ) ? '' : $instance['title'];

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

			echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			// The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
			$aria_label = trim( wp_strip_all_tags( $title ) );
			if ( ! $aria_label ) {
				$aria_label = __( 'Choose a language', 'polylang' );
			}

			if ( $instance['dropdown'] ) {
				echo '<label class="screen-reader-text" for="' . esc_attr( 'lang_choice_' . $instance['dropdown'] ) . '">' . esc_html( $aria_label ) . '</label>';
				echo $list; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

				/** This filter is documented in wp-includes/widgets/class-wp-nav-menu-widget.php */
				$format = apply_filters( 'navigation_widgets_format', $format );

				if ( 'html5' === $format ) {
					echo '<nav aria-label="' . esc_attr( $aria_label ) . '">';
				}

				echo "<ul>\n" . $list . "</ul>\n"; // phpcs:ignore WordPress.Security.EscapeOutput

				if ( 'html5' === $format ) {
					echo '</nav>';
				}
			}

			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Updates the widget options
	 *
	 * @since 0.4
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 *
	 * @phpstan-param T $new_instance
	 * @phpstan-param T $old_instance
	 */
	public function update( $new_instance, $old_instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$instance = array( 'title' => sanitize_text_field( $new_instance['title'] ) );
		foreach ( array_keys( PLL_Switcher::get_switcher_options( 'widget' ) ) as $key ) {
			$instance[ $key ] = ! empty( $new_instance[ $key ] ) ? 1 : 0;
		}

		return $instance;
	}

	/**
	 * Displays the widget form.
	 *
	 * @since 0.4
	 *
	 * @param array $instance Current settings.
	 * @return string
	 *
	 * @phpstan-param T $instance
	 */
	public function form( $instance ) {
		// Default values
		$instance = wp_parse_args( (array) $instance, array_merge( array( 'title' => '' ), PLL_Switcher::get_switcher_options( 'widget', 'default' ) ) );

		// Title
		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'polylang' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $instance['title'] )
		);

		foreach ( PLL_Switcher::get_switcher_options( 'widget' ) as $key => $str ) {
			printf(
				'<div%5$s%6$s><input type="checkbox" class="checkbox %7$s" id="%1$s" name="%2$s"%3$s /><label for="%1$s">%4$s</label></div>',
				esc_attr( $this->get_field_id( $key ) ),
				esc_attr( $this->get_field_name( $key ) ),
				checked( $instance[ $key ], true, false ),
				esc_html( $str ),
				in_array( $key, array( 'show_names', 'show_flags', 'hide_current' ) ) ? sprintf( ' class="no-dropdown-%s"', esc_attr( $this->id ) ) : '',
				( ! empty( $instance['dropdown'] ) && in_array( $key, array( 'show_names', 'show_flags', 'hide_current' ) ) ? ' style="display:none;"' : '' ),
				esc_attr( 'pll-' . $key )
			);
		}

		return ''; // Because the parent class returns a string, however not used.
	}
}
