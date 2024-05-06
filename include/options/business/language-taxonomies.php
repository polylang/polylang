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
	 * Returns non-core, public post types.
	 *
	 * @since 3.7
	 *
	 * @return string[] Object type names list.
	 */
	protected function get_object_types(): array {
		return array_diff(
			PLL()->model->translatable_objects->get_taxonomy_names( array( 'language' ) ),
			// Exclude the post and term language taxonomies from the list.
			array( PLL()->model->post->get_tax_language(), PLL()->model->term->get_tax_language() )
		);
	}
}
