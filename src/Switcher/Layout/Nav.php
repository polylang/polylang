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
		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$out = $cr;

		foreach ( $this->get_elements() as $element ) {
			$out .= $element->get();
		}

		if ( empty( $out ) || ! $this->settings->show_wrapper ) {
			return $out;
		}

		return $this->wrap( "<ul>{$out}</ul>" );
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
