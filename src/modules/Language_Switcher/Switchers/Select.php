<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher\Switchers;

use PLL_Language;
use WP_Syntex\Polylang\Language_Switcher\Assets;
use WP_Syntex\Polylang\Language_Switcher\Switchers\Elements\Select as Element;

defined( 'ABSPATH' ) || exit;

/**
 * Class that displays a language switcher as a selector.
 *
 * @since 3.9
 */
class Select extends Abstract_Switcher {
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

		Assets::enqueue_frontend_scripts();

		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$out = sprintf(
			'<div class="%1$s"><label class="screen-reader-text" for="%2$s">%3$s</label><select class="pll-switcher-select" id="%2$s">%4$s</select></div>',
			esc_attr( implode( ' ', $this->get_wrapper_classes() ) ),
			esc_attr( $this->settings->unique_id ),
			esc_html( __( 'Choose a language', 'polylang' ) ),
			"{$cr}{$out}"
		);

		return "{$cr}{$out}{$cr}";
	}

	/**
	 * Returns an instance of `Elements\Select`.
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
