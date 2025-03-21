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
		/** @phpstan-var array<non-falsy-string> */
		return array_diff(
			PLL()->model->translatable_objects->get_taxonomy_names( array( 'language' ) ),
			// Exclude the post and term language taxonomies from the list.
			array( PLL()->model->post->get_tax_language(), PLL()->model->term->get_tax_language() )
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
