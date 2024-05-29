<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining navigation menus array option.
 *
 * @since 3.7
 */
class Nav_Menus extends Abstract_Option {
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
		parent::__construct( $key, $value, array() );
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{
	 *     type: 'object',
	 *     patternProperties: array{
	 *         '^\w+$': array{
	 *             type: 'object',
	 *             context: list<'edit'>,
	 *             patternProperties: array{
	 *                 '^[a-z_-]+$': array{
	 *                     type: 'integer',
	 *                     minimum: 0
	 *                 }
	 *             },
	 *             additionalProperties: false
	 *         }
	 *     },
	 *     additionalProperties: false
	 * }
	 */
	protected function get_specific_schema(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP, @see{https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types}.
			'patternProperties'    => array(
				'^\\w+$' => array( // Any word characters as key, correspond to a theme slug.
					'type'              => 'object',
					'context'           => array( 'edit' ),
					'patternProperties' => array(
						'^[a-z_-]+$' => array( // Language slug as key.
							'type'    => 'integer',
							'minimum' => 0, // A post ID.
						),
					),
					'additionalProperties' => false,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Translated navigation menus for each theme.', 'polylang' );
	}
}
