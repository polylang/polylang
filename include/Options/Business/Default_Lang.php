<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Primitive\Abstract_String;
use WP_Syntex\Polylang\Model\Languages;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining language slug string option.
 *
 * @since 3.7
 */
class Default_Lang extends Abstract_String {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'default_lang'
	 */
	public static function key(): string {
		return 'default_lang';
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string', pattern: Languages::SLUG_PATTERN}
	 */
	protected function get_data_structure(): array {
		$string_schema            = parent::get_data_structure();
		$string_schema['pattern'] = Languages::SLUG_PATTERN;

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
