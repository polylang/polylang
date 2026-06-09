<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Widgets;

use PLL_Base;
use PLL_Links;
use WP_Widget;
use WP_Syntex\Polylang\Switcher\Assets;
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
	 * @var PLL_Links|null
	 */
	private ?PLL_Links $links;

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Base $polylang The Polylang object.
	 */
	public function __construct( PLL_Base &$polylang ) {
		$this->links = &$polylang->links;

		parent::__construct(
			'polylang',
			__( 'Language switcher', 'polylang' ),
			array(
				'description'                 => __( 'Displays a language switcher', 'polylang' ),
				'customize_selective_refresh' => true,
			)
		);

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_enqueue_scripts', array( Assets::class, 'enqueue_frontend_styles' ) );
		}
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
		if ( empty( $this->links ) ) {
			return;
		}

		$instance['unique_id']    = "pll-switcher-widget-{$this->number}";
		$instance['show_wrapper'] = true;

		$instance = Fields::remove_legacy_settings( $instance );
		$settings = new Settings( $instance );
		$list     = ( new Switcher( $settings, $this->links ) )->get();

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
		$validated = Fields::to_db( new Settings( $new_instance ) );

		$validated['title'] = ! empty( $new_instance['title'] ) && is_string( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';

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
		$settings = new Settings( Fields::remove_legacy_settings( $instance ) );
		$title    = ! empty( $instance['title'] ) && is_string( $instance['title'] ) ? $instance['title'] : '';

		// Title.
		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'polylang' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);

		echo '<table role="presentation" class="polylang-language-switcher-widget-content"><tbody>';

		foreach ( Fields::get() as $key => $field ) {
			if ( ! empty( $field['choices'] ) ) {
				$this->print_select( $key, $field, $settings );
			} else {
				$this->print_checkbox( $key, $field, $settings );
			}
		}

		echo '</tbody></table>';
	}

	/**
	 * Prints a `<select>` setting.
	 *
	 * @since 3.9
	 *
	 * @param string   $key      Setting key.
	 * @param array    $field    Field labels and other data.
	 * @param Settings $settings Widget's settings.
	 * @return void
	 */
	private function print_select( string $key, array $field, Settings $settings ): void {
		printf( '<tr%s>', $this->get_wrapper_class_attr( $key, $field, $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf(
			'<th><label for="%s">%s</label></th>',
			esc_attr( $this->get_field_id( $key ) ),
			esc_html( $field['label'] )
		);
		printf(
			'<td><select data-key="%1$s" id="%2$s" name="%3$s">',
			esc_attr( $key ),
			esc_attr( $this->get_field_id( $key ) ),
			esc_attr( $this->get_field_name( $key ) )
		);
		foreach ( $field['choices'] as $value => $label ) {
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
	 * @param string   $key      Setting key.
	 * @param array    $field    Field labels and other data.
	 * @param Settings $settings Widget's settings.
	 * @return void
	 */
	private function print_checkbox( string $key, array $field, Settings $settings ): void {
		printf( '<tr%s>', $this->get_wrapper_class_attr( $key, $field, $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf(
			'<td colspan="2"><input type="checkbox" data-key="%1$s" class="checkbox" id="%2$s" name="%3$s"%4$s/><label for="%2$s">%5$s</label></td>',
			esc_attr( $key ),
			esc_attr( $this->get_field_id( $key ) ),
			esc_attr( $this->get_field_name( $key ) ),
			checked( $settings->$key, true, false ),
			esc_html( $field['label'] )
		);
		echo '</tr>';
	}

	/**
	 * Returns the outer wrapper's `class` attribute.
	 *
	 * @since 3.9
	 *
	 * @param string   $key      Setting key.
	 * @param array    $field    Field labels and other data.
	 * @param Settings $settings Widget's settings.
	 * @return string
	 */
	private function get_wrapper_class_attr( string $key, array $field, Settings $settings ): string {
		if ( empty( $field['hide_if'] ) ) {
			return '';
		}

		$classes = array();

		foreach ( $field['hide_if'] as $k => $value ) {
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
