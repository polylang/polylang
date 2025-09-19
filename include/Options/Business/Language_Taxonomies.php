<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use PLL_Translatable_Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining post types list option.
 *
 * @since 3.7
 */
class Language_Taxonomies extends Abstract_Object_Types {
	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'language_taxonomies'
	 */
	public static function key(): string {
		return 'language_taxonomies';
	}

	/**
	 * Returns language taxonomies, except the ones for posts and taxonomies.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 *
	 * @phpstan-return array<non-falsy-string>
	 */
	protected function get_object_types(): array {
		$translatable_objects = new PLL_Translatable_Objects();

		/** @phpstan-var array<non-falsy-string> */
		return array_diff(
			$translatable_objects->get_taxonomy_names( array( 'language' ) ),
			// Exclude the post and term language taxonomies from the list.
			array(
				$translatable_objects->get( 'post' )->get_tax_language(),
				$translatable_objects->get( 'term' )->get_tax_language(),
			)
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
		return __( 'List of language taxonomies used for custom DB tables.', 'polylang' );
	}
}
