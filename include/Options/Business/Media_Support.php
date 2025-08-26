<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Primitive\Abstract_Boolean;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Translate media" boolean option.
 *
 * @since 3.7
 */
class Media_Support extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'media_support'
	 */
	public static function key(): string {
		return 'media_support';
	}

	/**
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param array   $info    The current site health information.
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function add_to_site_health_info( array $info, Options $options ): array {
		if ( $this->get() ) {
			$value = '1: ' . __( 'The media are translated', 'polylang' );
		} else {
			$value = '0: ' . __( 'The media are not translated', 'polylang' );
		}

		return $this->get_site_health_info( $info, $value, self::key() );
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return sprintf(
			/* translators: %1$s and %2$s are "true/false" values. */
			__( 'Translate media: %1$s to translate, %2$s otherwise.', 'polylang' ),
			'`true`',
			'`false`'
		);
	}
}
