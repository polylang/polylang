<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Layout;

use PLL_Language;
use WP_Syntex\Polylang\Switcher\Element\Nav as Element;

defined( 'ABSPATH' ) || exit;

/**
 * Class that displays a language switcher as a list.
 *
 * @since 3.9
 */
class Nav extends Abstract_Layout {
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
			$out .= $element->get();
		}

		if ( empty( $out ) || ! $this->settings->show_wrapper ) {
			return $out;
		}

		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$tag = $this->get_nav_tag();
		$out = sprintf(
			'<%1$s%2$s id="%3$s" class="%4$s" aria-label="%5$s">%6$s</%1$s>',
			$tag,
			'div' === $tag ? ' role="navigation"' : '',
			esc_attr( $this->settings->unique_id ),
			esc_attr( implode( ' ', $this->get_wrapper_classes() ) ),
			esc_attr( __( 'Choose a language', 'polylang' ) ),
			"{$cr}<ul>{$cr}{$out}</ul>"
		);

		return "{$cr}{$out}{$cr}";
	}

	/**
	 * Returns an instance of `Element\Nav`.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Language $language Instance of `PLL_Language`.
	 * @return Element
	 */
	protected function get_element( PLL_Language $language ): Element {
		return new Element( $language, $this->settings, $this->links );
	}
}
