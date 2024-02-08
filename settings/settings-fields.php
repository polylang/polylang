<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Display settings fields.
 *
 * @since 3.6
 */
class PLL_Settings_Fields {
	/**
	 * Base of the name attribute used by the inputs.
	 *
	 * @var string
	 */
	private $input_base_name;

	/**
	 * Stores the fields' options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @since 3.6
	 *
	 * @param string $input_base_name Base of the name attribute used by the inputs.
	 * @param array  $options         Fields' options. Make sure all keys are set, and with the appropriate type.
	 *
	 * @phpstan-param non-falsy-string $input_base_name
	 */
	public function __construct( string $input_base_name, array $options ) {
		$this->input_base_name = $input_base_name;
		$this->options         = $options;
	}

	/**
	 * Returns a HTML string for a `<input type="password"/>` tag.
	 *
	 * @since 3.6
	 *
	 * @param array $field_atts {
	 *    Some attributes for the tag.
	 *
	 *    @type string $option The option name.
	 *    @type string $id     The `id` attribute (without the `pll-` prefix).
	 *    @type array  $data   Optional. List of `data-*` attributes (without the `data-` prefix).
	 * }
	 * @return string
	 *
	 * @phpstan-param array{
	 *    option: non-falsy-string,
	 *    id: non-falsy-string,
	 *    data?: array<non-falsy-string, scalar>
	 * } $field_atts
	 */
	public function get_password_tag( array $field_atts ): string {
		return sprintf(
			'<input id="pll-%s" name="%s[%s]" type="password" value="%s" class="regular-text code" %s/>',
			esc_attr( $field_atts['id'] ),
			esc_attr( $this->input_base_name ),
			esc_attr( $field_atts['option'] ),
			esc_attr( $this->options[ $field_atts['option'] ] ),
			$this->build_attributes( $field_atts['data'] ?? array(), 'data-' )
		);
	}

	/**
	 * Returns a HTML string for a `<select/>` tag.
	 *
	 * @since 3.6
	 *
	 * @param array $field_atts {
	 *    Some attributes for the tag.
	 *
	 *    @type string $option The option name.
	 *    @type string $id     The `id` attribute (without the `pll-` prefix).
	 *    @type array  $values A list of values/labels pairs.
	 *    @type array  $data   Optional. List of `data-*` attributes (without the `data-` prefix).
	 * }
	 * @return string
	 *
	 * @phpstan-param array{
	 *    option: non-falsy-string,
	 *    id: non-falsy-string,
	 *    values: non-empty-array<scalar, string>,
	 *    data?: array<non-falsy-string, scalar>
	 * } $field_atts
	 */
	public function get_select_tag( array $field_atts ): string {
		$content = sprintf(
			'<select id="pll-%s" name="%s[%s]" %s>',
			esc_attr( $field_atts['id'] ),
			esc_attr( $this->input_base_name ),
			esc_attr( $field_atts['option'] ),
			$this->build_attributes( $field_atts['data'] ?? array(), 'data-' )
		);

		foreach ( $field_atts['values'] as $value => $label ) {
			$content .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( (string) $value ),
				selected( $this->options[ $field_atts['option'] ], $value, false ),
				esc_html( $label )
			);
		}

		return "{$content}</select>";
	}

	/**
	 * Returns a HTML string for a description text.
	 *
	 * @since 3.6
	 *
	 * @param array $field_atts {
	 *    Some attributes for the tag.
	 *
	 *    @type string $content The content text.
	 *    @type string $class   Optional. A class attribute.
	 * }
	 * @return string
	 *
	 * @phpstan-param array{
	 *    content: string,
	 *    class?: string
	 * } $field_atts
	 */
	public function get_description_tag( array $field_atts ): string {
		return sprintf(
			'<p class="description %s">%s</p>',
			$field_atts['class'] ?? '',
			$field_atts['content']
		);
	}

	/**
	 * Returns a HTML string for a `<label/>` tag.
	 *
	 * @since 3.6
	 *
	 * @param array $field_atts {
	 *    Some attributes for the tag.
	 *
	 *    @type string $label The label text.
	 *    @type string $for   The `for` attribute (without the `pll-` prefix).
	 * }
	 * @return string
	 *
	 * @phpstan-param array{
	 *    for: non-falsy-string,
	 *    label: string
	 * } $field_atts
	 */
	public function get_label_tag( array $field_atts ): string {
		return sprintf(
			'<label for="pll-%s">%s</label>',
			esc_attr( $field_atts['for'] ),
			esc_html( $field_atts['label'] )
		);
	}

	/**
	 * Returns a HTML string for a `<button/>` tag.
	 *
	 * @since 3.6
	 *
	 * @param array $field_atts {
	 *    Some attributes for the tag.
	 *
	 *    @type string $label The label text.
	 *    @type string $id    Optional. The `id` attribute (without the `pll-` prefix).
	 *    @type string $class Optional. A class attribute.
	 *    @type array  $data  Optional. List of `data-*` attributes (without the `data-` prefix).
	 * }
	 * @return string
	 *
	 * @phpstan-param array{
	 *    label: string,
	 *    id?: non-falsy-string,
	 *    class?: non-falsy-string,
	 *    data?: array<non-falsy-string, scalar>
	 * } $field_atts
	 */
	public function get_button_tag( array $field_atts ): string {
		$id = ! empty( $field_atts['id'] ) ? sprintf( 'id="pll-%s"', esc_attr( $field_atts['id'] ) ) : '';

		return sprintf(
			'<button %s class="button button-secondary %s" type="button" %s>%s</button>',
			$id,
			esc_attr( $field_atts['class'] ?? '' ),
			$this->build_attributes( $field_atts['data'] ?? array(), 'data-' ),
			esc_html( $field_atts['label'] )
		);
	}

	/**
	 * Returns a HTML string to display a progress bar.
	 *
	 * @since 3.6
	 *
	 * @param float $count Progress count.
	 * @param float $limit Progress limit.
	 * @return string
	 */
	public function get_progress_bar( float $count, float $limit ): string {
		$percent  = round( $count * 100 / $limit, 1 );
		$percent  = (float) min( $percent, 100 );
		$decimals = 1;

		if ( floor( $percent ) === $percent ) {
			$decimals = 0;
		}

		return sprintf(
			'<div class="pll-progress-bar-wrapper">%1$s<div style="width: %2$s;">%1$s</div></div>',
			esc_html( number_format_i18n( $percent, $decimals ) ) . '%',
			esc_attr( (string) $percent ) . '%'
		);
	}

	/**
	 * Returns a string after building HTML attributes.
	 *
	 * @since 3.6
	 *
	 * @param array  $atts   Attributes as an array. Attribute names as array keys and attribute values as array values.
	 * @param string $prefix Optional. Prefix to add to all attributes. Ex: `data-`. Default is an empty string.
	 * @return string
	 */
	public function build_attributes( array $atts, string $prefix = '' ): string {
		return implode(
			' ',
			array_map(
				function ( $key ) use ( $atts, $prefix ) {
					if ( is_bool( $atts[ $key ] ) ) {
						$val = (int) $atts[ $key ];
					} else {
						$val = esc_attr( (string) $atts[ $key ] );
					}
					return sprintf( '%s="%s"', esc_attr( "{$prefix}{$key}" ), $val );
				},
				array_keys( $atts )
			)
		);
	}
}
