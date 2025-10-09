<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Primitive;

use WP_Syntex\Polylang\Options\Options;

trait Site_Health_String_Trait {
	/**
	 * Adds information to the site health info array.
	 *
	 * @since 3.8
	 *
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return array(
			'label' => ucfirst( static::key() ),
			'value' => $this->get(),
		);
	}
}
