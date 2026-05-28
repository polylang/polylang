<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Widgets;

use WP_Widget;
use WP_Syntex\Polylang\Switcher\Switcher;
use WP_Syntex\Polylang\Switcher\Fields\Widget as Fields;
use WP_Syntex\Polylang\Switcher\Settings\Settings;

/**
 * The advanced language switcher widget.
 *
 * @since 3.9
 *
 * @phpstan-type NewInstance array{
 *     title: string,
 *     layout: 'horizontal'|'vertical'|'dropdown'|'select',
 *     alignment: 'left'|'center'|'right'|'stretched',
 *     show_flags: bool,
 *     flag_aspect_ratio: '3:2'|'1:1',
 *     show_labels: ''|'names'|'codes',
 *     hide_if_no_translation: bool,
 *     hide_current: bool,
 *     force_home: bool,
 *     dropdown: 0|1,
 *     show_names: 0|1
 * }
 * @phpstan-type OldInstance array{
 *     title: string,
 *     dropdown: 0|1,
 *     show_flags: 0|1,
 *     show_names: 0|1,
 *     hide_if_no_translation: 0|1,
 *     hide_current: 0|1,
 *     force_home: 0|1
 * }
 * @extends WP_Widget<T>
 * @phpstan-template T of array
 */
class Languages extends WP_Widget {
	/**
	 * Constructor.
	 *
	 * @since 3.9
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
	 * Displays the widget.
	 *
	 * @since 3.9
	 *
	 * @param array $args     Arguments, including `before_title`, `after_title`, `before_widget`, and `after_widget`.
	 * @param array $instance The settings for the particular instance of the widget.
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
	 * @phpstan-param NewInstance|OldInstance $instance
	 */
	public function widget( $args, $instance ): void {
		if ( empty( PLL()->links ) ) {
			return;
		}

		$instance['unique_id']    = "pll-switcher-widget-{$this->number}";
		$instance['show_wrapper'] = true;

		if ( isset( $instance['layout'] ) ) {
			// For backward compatibility, some legacy options are saved along the new ones (see the end of `Languages::update()`).
			unset( $instance['dropdown'], $instance['show_names'] );
		}

		$settings = new Settings( $instance );
		$list     = ( new Switcher( $settings, PLL()->links ) )->get();

		if ( empty( $list ) ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo $list; // phpcs:ignore WordPress.Security.EscapeOutput

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Updates the widget options.
	 *
	 * @since 3.9
	 *
	 * @param array $new_instance New settings for this instance as input by the user via `form()`.
	 * @param array $old_instance Old settings for this instance.
	 * @return array|bool Settings to save or bool false to cancel saving.
	 *
	 * @phpstan-param NewInstance $new_instance
	 * @phpstan-param OldInstance $old_instance
	 */
	public function update( $new_instance, $old_instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$validated = Fields::filter( new Settings( $new_instance, array( 'filter_settings' => false ) ) );

		$validated['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';

		// Keep the legacy keys in database for backward compatibility.
		$validated['dropdown']   = 'select' === $validated['layout'] ? 1 : 0;
		$validated['show_names'] = ! empty( $validated['show_labels'] ) ? 1 : 0;

		return $validated;
	}

	/**
	 * Displays the widget form.
	 *
	 * @since 3.9
	 *
	 * @param array $instance Current settings.
	 * @return void
	 *
	 * @phpstan-param NewInstance|OldInstance $instance
	 */
	public function form( $instance ): void {
		$labels_and_data = Fields::get();

		if ( isset( $instance['layout'] ) ) {
			// For backward compatibility, some legacy options are saved along the new ones (see the end of `Languages::update()`).
			unset( $instance['dropdown'], $instance['show_names'] );
		}

		$settings = new Settings( $instance, array( 'filter_settings' => false ) );

		// Title.
		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'polylang' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $instance['title'] ?? '' )
		);

		echo '<table role="presentation" class="polylang-language-switcher-widget-content"><tbody>';

		// Layout.
		$this->print_select( 'layout', $labels_and_data, $settings );

		// Alignment.
		$this->print_select( 'alignment', $labels_and_data, $settings );

		// Display flags.
		$this->print_checkbox( 'show_flags', $labels_and_data, $settings );

		// Flag aspect ratio.
		$this->print_select( 'flag_aspect_ratio', $labels_and_data, $settings );

		// Display labels.
		$this->print_select( 'show_labels', $labels_and_data, $settings );

		// Force link to front page.
		$this->print_checkbox( 'force_home', $labels_and_data, $settings );

		// Hide current language.
		$this->print_checkbox( 'hide_current', $labels_and_data, $settings );

		// Hide languages when they don't have translations.
		$this->print_checkbox( 'hide_if_no_translation', $labels_and_data, $settings );

		echo '</tbody></table>';
	}

	/**
	 * Prints a `<select>` setting.
	 *
	 * @since 3.9
	 *
	 * @param string   $key             Setting key.
	 * @param array    $labels_and_data Setting labels and other data.
	 * @param Settings $settings        Widget's settings.
	 * @return void
	 */
	private function print_select( string $key, array $labels_and_data, Settings $settings ): void {
		printf( '<tr%s>', $this->get_wrapper_class_attr( $key, $labels_and_data, $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf(
			'<th><label for="%s">%s</label></th>',
			esc_attr( $this->get_field_id( $key ) ),
			esc_html( $labels_and_data[ $key ]['label'] )
		);
		printf(
			'<td><select data-key="%1$s" id="%2$s" name="%3$s">',
			esc_attr( $key ),
			esc_attr( $this->get_field_id( $key ) ),
			esc_attr( $this->get_field_name( $key ) )
		);
		foreach ( $labels_and_data[ $key ]['choices'] as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $settings->$key, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select></td></tr>';
	}

	/**
	 * Prints a checkbox setting.
	 *
	 * @since 3.9
	 *
	 * @param string   $key             Setting key.
	 * @param array    $labels_and_data Setting labels and other data.
	 * @param Settings $settings        Widget's settings.
	 * @return void
	 */
	private function print_checkbox( string $key, array $labels_and_data, Settings $settings ): void {
		printf( '<tr%s>', $this->get_wrapper_class_attr( $key, $labels_and_data, $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf(
			'<td colspan="2"><input type="checkbox" data-key="%1$s" class="checkbox" id="%2$s" name="%3$s"%4$s/><label for="%2$s">%5$s</label></td>',
			esc_attr( $key ),
			esc_attr( $this->get_field_id( $key ) ),
			esc_attr( $this->get_field_name( $key ) ),
			checked( $settings->$key, true, false ),
			esc_html( $labels_and_data[ $key ]['label'] )
		);
		echo '</tr>';
	}

	/**
	 * Returns the outer wrapper's `class` attribute.
	 *
	 * @since 3.9
	 *
	 * @param string   $key             Setting key.
	 * @param array    $labels_and_data Setting labels and other data.
	 * @param Settings $settings        Widget's settings.
	 * @return string
	 */
	private function get_wrapper_class_attr( string $key, array $labels_and_data, Settings $settings ): string {
		if ( empty( $labels_and_data[ $key ]['hide_if'] ) ) {
			return '';
		}

		$classes = array();

		foreach ( $labels_and_data[ $key ]['hide_if'] as $k => $value ) {
			if ( $settings->$k === $value ) {
				$classes[] = "pll-hidden-by-{$k}";
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			$classes[] = "pll-hidden-if-{$k}-{$value}";
		}

		return sprintf(
			' class="%s"',
			esc_attr( implode( ' ', $classes ) )
		);
	}
}
