<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Layout;

use PLL_Language;
use WP_Syntex\Polylang\Switcher\Assets;
use WP_Syntex\Polylang\Switcher\Element\Select as Element;

defined( 'ABSPATH' ) || exit;

/**
 * Class that displays a language switcher as a selector.
 *
 * @since 3.9
 */
class Select extends Abstract_Layout {
	/**
	 * Returns the markup of the switcher.
	 *
	 * The `<select>` tag is always output. `show_wrapper` only controls the outer
	 * `<div>` and its screen-reader label.
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

		if ( empty( $out ) ) {
			return $out;
		}

		Assets::enqueue_frontend_scripts();

		$select = sprintf(
			'<select class="pll-switcher-select" id="%1$s">%2$s</select>',
			esc_attr( $this->settings->unique_id ),
			$out
		);

		if ( ! $this->settings->show_wrapper ) {
			return "{$cr}{$select}{$cr}";
		}

		$label = sprintf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label>',
			esc_attr( $this->settings->unique_id ),
			esc_html( __( 'Choose a language', 'polylang' ) )
		);

		return $this->wrap( "{$label}{$cr}{$select}" );
	}

	/**
	 * Returns an instance of `Element\Select`.
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
	 * Wraps the given content into a `<div>` tag.
	 *
	 * @since 3.9
	 *
	 * @param string $content The content.
	 * @return string
	 */
	protected function wrap( string $content ): string {
		$cr  = $this->settings->preserve_spacing ? "\n" : '';
		$out = sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', $this->get_wrapper_classes() ) ),
			"{$cr}{$content}{$cr}"
		);

		return "{$cr}{$out}{$cr}";
	}
}
