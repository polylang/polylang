<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher\Switchers\Element;

defined( 'ABSPATH' ) || exit;

/**
 * Data representing an item.
 *
 * @since 3.9
 */
class Select extends Abstract_Element {
	/**
	 * Returns the markup of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get(): string {
		$item_atts = sprintf(
			'lang="%1$s" value="%2$s"%3$s',
			esc_attr( $this->locale ),
			esc_url( $this->url ),
			selected( $this->is_current, true, false )
		);

		if ( ! empty( $this->item_classes ) ) {
			$item_atts .= sprintf(
				' class="%s"',
				esc_attr( implode( ' ', $this->item_classes ) )
			);
		}

		$out = sprintf(
			'<option %s>%s</option>',
			$item_atts,
			$this->get_label()
		);

		if ( ! $this->settings->preserve_spacing ) {
			return $out;
		}

		return "\t{$out}\n";
	}
}
