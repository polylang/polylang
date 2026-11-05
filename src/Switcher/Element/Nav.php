<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Element;

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
		$out = sprintf(
			'<li class="%s">%s</li>',
			esc_attr( implode( ' ', $this->item_classes ) ),
			$this->get_link()
		);

		if ( ! $this->settings->preserve_spacing ) {
			return $out;
		}

		return "\t{$out}\n";
	}

	/**
	 * Returns the markup of a link.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_link(): string {
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

		return sprintf( '<a %s>%s</a>', $link_atts, $this->get_label() );
	}

	/**
	 * Returns the markup of the label of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_label(): string {
		$label = '';

		if ( ! empty( $this->flag ) ) {
			$label .= sprintf(
				'<span class="pll-switcher-flag">%s</span>',
				$this->flag
			);
		}

		// Keep these inline styles in sync with `css/src/lib/switcher-flags.css`.
		if ( ! empty( $this->label ) ) {
			$style = empty( $this->flag ) ? '' : ' style="margin-inline-start:var(--pll-flag-label-spacing,0.3em);writing-mode:horizontal-tb"';
			$label .= sprintf( '<span class="pll-switcher-label"%1$s>%2$s</span>', $style, esc_html( $this->label ) );
		}

		return $label;
	}
}
