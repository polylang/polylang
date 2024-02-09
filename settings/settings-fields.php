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
	 *
	 * @phpstan-var non-falsy-string
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
	 * @param array  $options         Fields' options. Make sure the keys are set with the appropriate type.
	 *
	 * @phpstan-param non-falsy-string $input_base_name
	 */
	public function __construct( string $input_base_name, array $options ) {
		$this->input_base_name = $input_base_name;
		$this->options         = $options;
	}

	/**
	 * Prints a view.
	 *
	 * @since 3.6
	 *
	 * @param string $view Name of the view.
	 * @param array  $atts Data to print. See views headers.
	 * @return void
	 */
	public function print_view( string $view, array $atts = array() ) {
		if ( is_readable( POLYLANG_DIR . "/settings/view-{$view}.php" ) ) {
			include POLYLANG_DIR . "/settings/view-{$view}.php";
		}
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

	/**
	 * Returns a field's option value.
	 *
	 * @since 3.6
	 *
	 * @param string $option  Field option name.
	 * @param mixed  $default Optional. Default value if the option is not set.
	 * @return mixed
	 */
	public function get_option( string $option, $default = '' ) {
		return $this->options[ $option ] ?? $default;
	}

	/**
	 * Returns the base of the name attribute used by the inputs.
	 *
	 * @since 3.6
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public function get_input_base_name(): string {
		return $this->input_base_name;
	}
}
