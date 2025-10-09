<?php
// entete

namespace WP_Syntex\Polylang\Options\Primitive;
use WP_Syntex\Polylang\Options\Options;

trait Site_Health_String_Trait {
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return array(
			'label' => ucfirst( static::key() ),
			'value' => $this->get(),
		);
	}
}
