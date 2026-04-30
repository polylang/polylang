<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher\Switchers;

use PLL_Language;
use WP_Syntex\Polylang\Language_Switcher\Switchers\Elements\Nav as Element;

defined( 'ABSPATH' ) || exit;

/**
 * Class that displays a language switcher as a list.
 *
 * @since 3.9
 */
class Nav extends Abstract_Switcher {
	/**
	 * Returns the markup of the switcher.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get(): string {
		$out = '';

		foreach ( $this->get_elements() as $element ) {
			$out .= $element->get_row();
		}

		if ( empty( $out ) || ! $this->settings->show_wrapper ) {
			return $out;
		}

		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$out = sprintf(
			'<%1$s id="%2$s" class="%3$s" aria-label="%4$s">%5$s</%1$s>',
			$this->supports_html5() ? 'nav' : 'div',
			esc_attr( $this->settings->unique_id ),
			esc_attr( implode( ' ', $this->get_wrapper_classes() ) ),
			esc_attr( __( 'Choose a language', 'polylang' ) ),
			"{$cr}<ul>{$cr}{$out}</ul>"
		);

		return "{$cr}{$out}{$cr}";
	}

	/**
	 * Returns an instance of `Elements\Nav`.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Language $language Instance of `PLL_Language`.
	 * @return Element
	 */
	protected function get_element( PLL_Language $language ): Element {
		return new Element( $language, $this->settings );
	}
}
