<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining post types list option.
 *
 * @since 3.7
 */
class Post_Types extends Abstract_Object_Types {
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
		parent::__construct( $key, $value, array(), 'string' );
	}

	/**
	 * Returns non-core post types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 *
	 * @phpstan-return array<non-falsy-string>
	 */
	protected function get_object_types(): array {
		/** @phpstan-var array<non-falsy-string> */
		return get_post_types( array( '_builtin' => false ) );
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'List of post types to translate.', 'polylang' );
	}
}
