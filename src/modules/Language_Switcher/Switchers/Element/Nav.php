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
class Nav extends Abstract_Element {
	/**
	 * Returns the markup of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get(): string {
		$link_atts = sprintf(
			'lang="%1$s" hreflang="%1$s" href="%2$s"',
			esc_attr( $this->locale ),
			esc_url( $this->url )
		);

		if ( ! empty( $this->link_classes ) ) {
			$link_atts .= sprintf(
				' class="%s"',
				esc_attr( implode( ' ', $this->link_classes ) )
			);
		}

		if ( $this->is_current ) {
			$link_atts .= ' aria-current="true"';
		}

		$out = sprintf(
			'<li class="%s"><a %s>%s</a></li>',
			esc_attr( implode( ' ', $this->item_classes ) ),
			$link_atts,
			$this->get_label()
		);

		if ( ! $this->settings->preserve_spacing ) {
			return $out;
		}

		return "\t{$out}\n";
	}

	/**
	 * Returns the markup of the label of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_label(): string {
		if ( ! empty( $this->flag ) && ! empty( $this->label ) ) {
			return sprintf(
				'<span class="pll-switcher-flag">%s</span><span>%s</span>',
				(string) preg_replace( '/ style="[^"]*"/', '', $this->flag ),
				esc_html( $this->label )
			);
		}

		if ( ! empty( $this->flag ) ) {
			return (string) preg_replace( '/ style="[^"]*"/', '', $this->flag );
		}

		return esc_html( $this->label );
	}
}
