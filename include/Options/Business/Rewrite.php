<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Error;
use WP_Syntex\Polylang\Options\Primitive\Abstract_Boolean;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Remove /language/ in pretty permalinks" boolean option.
 *
 * @since 3.7
 */
class Rewrite extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'rewrite'
	 */
	public static function key(): string {
		return 'rewrite';
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
		if ( $options->get( self::key() ) ) {
			$value = '1: ' . sprintf(
				/* translators: %s is a URL slug: `/language/`. */
				__( 'Remove %s in pretty permalinks', 'polylang' ),
				'`/language/`'
			);
		} else {
			$value = '0: ' . sprintf(
				/* translators: %s is a URL slug: `/language/`. */
				__( 'Keep %s in pretty permalinks', 'polylang' ),
				'`/language/`'
			);
		}

		return $this->get_site_health_info( $info, $value, self::key() );
	}
	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return bool
	 */
	protected function get_default() {
		return true;
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
			/* translators: %1$s is a URL slug: `/language/`. %2$s and %3$s are "true/false" values. */
			__( 'Remove %1$s in pretty permalinks: %2$s to remove, %3$s to keep.', 'polylang' ),
			'`/language/`',
			'`true`',
			'`false`'
		);
	}
}
