<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Abstract_String;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 */
class Language_Slug extends Abstract_String {
	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Optional. Option value.
	 *
	 * @phpstan-param non-falsy-string $key
	 */
	public function __construct( string $key, $value = null ) {
		parent::__construct( $key, $value, '' );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string', pattern: '^[a-z_-]+$'}
	 */
	protected function get_specific_schema(): array {
		$string_schema            = parent::get_specific_schema();
		$string_schema['pattern'] = '^[a-z_-]+$';

		return $string_schema;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Slug of the default language.', 'polylang' );
	}
}
