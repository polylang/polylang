<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Layout;

use PLL_Language;
use WP_Syntex\Polylang\Switcher\Assets;
use WP_Syntex\Polylang\Switcher\Element\Nav as Element;

defined( 'ABSPATH' ) || exit;

/**
 * Class that displays a language switcher as a dropdown.
 *
 * @since 3.9
 */
class Dropdown extends Abstract_Layout {
	/**
	 * Returns the markup of the switcher.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get(): string {
		$current_item = $this->get_top_level_item();

		if ( empty( $current_item ) ) {
			return '';
		}

		$out = '';

		foreach ( $this->get_elements() as $element ) {
			$out .= $element->get();
		}

		if ( empty( $out ) || ! $this->settings->show_wrapper ) {
			return $out;
		}

		Assets::enqueue_frontend_scripts();

		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$out = sprintf(
			"{$cr}%s{$cr}%s{$cr}<ul>{$cr}%s</ul>",
			$current_item,
			$this->get_button(),
			$out
		);
		$out = sprintf(
			'<%1$s id="%2$s" class="%3$s" aria-label="%4$s">%5$s</%1$s>',
			$this->get_nav_tag(),
			esc_attr( $this->settings->unique_id ),
			esc_attr( implode( ' ', $this->get_wrapper_classes() ) ),
			esc_attr( __( 'Choose a language', 'polylang' ) ),
			$out
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

	/**
	 * Returns the markup of the current item.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	private function get_top_level_item(): string {
		$current_element = null;

		foreach ( $this->elements as $element ) {
			if ( $element->is_current ) {
				$current_element = $element;
				break;
			}
		}

		if ( empty( $current_element ) ) {
			return '';
		}

		return sprintf(
			'<a lang="%1$s" hreflang="%1$s" href="%2$s" class="current-lang" aria-current="true">%3$s</a>',
			esc_attr( $current_element->locale ),
			esc_url( $current_element->get_url() ),
			$current_element->get_label()
		);
	}

	/**
	 * Returns the markup of the button.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	private function get_button(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.5 4L6 8L10.5 4" stroke-width="1.5"></path></svg>';
		return sprintf(
			'<button aria-label="%1$s" class="pll-submenu-toggle">%2$s</button>',
			esc_attr( __( 'Open languages submenu', 'polylang' ) ),
			$svg
		);
	}
}
