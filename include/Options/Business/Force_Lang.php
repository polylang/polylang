<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Determine how the current language is defined" option.
 *
 * @since 3.7
 */
class Force_Lang extends Abstract_Option {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'force_lang'
	 */
	public static function key(): string {
		return 'force_lang';
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
		switch ( $options->get( self::key() ) ) {
			case '0':
				$value = '0: ' . __( 'The language is set from content', 'polylang' );
				break;
			case '1':
				$value = '1: ' . __( 'The language is set from the directory name in pretty permalinks', 'polylang' );
				break;
			case '2':
				$value = '2: ' . __( 'The language is set from the subdomain name in pretty permalinks', 'polylang' );
				break;
			case '3':
				$value = '3: ' . __( 'The language is set from different domains', 'polylang' );
				break;
			default:
				$value = '';
				break;
		}

		return $this->get_site_health_info( $info, $value, self::key() );
	}
	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return int
	 */
	protected function get_default() {
		return 1;
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'integer', enum: list<0|1|2|3>|list<1|2|3>}
	 */
	protected function get_data_structure(): array {
		return array(
			'type' => 'integer',
			'enum' => 'yes' === get_option( 'pll_language_from_content_available' ) ? array( 0, 1, 2, 3 ) : array( 1, 2, 3 ),
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
		return __( 'Determine how the current language is defined.', 'polylang' );
	}
}
