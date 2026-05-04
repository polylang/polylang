<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher\Switchers;

use PLL_Language;
use WP_Syntex\Polylang\Language_Switcher\Settings\Settings;
use WP_Syntex\Polylang\Language_Switcher\Switchers\Element\Abstract_Element;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to display a certain type of language switcher.
 *
 * @since 3.9
 */
abstract class Abstract_Switcher {
	/**
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * @var Abstract_Element[]
	 */
	protected array $elements = array();

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings  Instance of `Settings`.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		foreach ( $this->settings->get_links()->model->languages->get_list() as $language ) {
			$this->elements[ $language->slug ] = $this->get_element( $language );
		}
	}

	/**
	 * Returns the markup of the switcher.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	abstract public function get(): string;

	/**
	 * Returns the switcher's data.
	 *
	 * @since 3.9
	 *
	 * @return Abstract_Element[]
	 *
	 * @phpstan-return array<non-empty-string, Abstract_Element>
	 */
	public function get_elements(): array {
		$out      = array();
		$is_first = true;

		foreach ( $this->elements as $element ) {
			if ( $this->settings->hide_current && $element->is_current ) {
				// Hide current item.
				continue;
			}

			if ( $this->settings->hide_if_empty && $element->is_empty ) {
				// Hide empty item.
				continue;
			}

			if ( $this->settings->hide_if_no_translation && ! $element->has_translations ) {
				// Hide item with no translations.
				continue;
			}

			if ( empty( $element->url ) ) {
				// Failed to get a URL.
				continue;
			}

			if ( $is_first ) {
				$is_first = false;
				$element  = clone( $element ); // We don't want the item class to be added permanently to the object.

				$element->item_classes[] = 'lang-item-first';
			}

			$out[ $element->slug ] = $element;
		}

		return $out;
	}

	/**
	 * Returns an instance of `Abstract_Element`.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Language $language Instance of `PLL_Language`.
	 * @return Abstract_Element
	 */
	abstract protected function get_element( PLL_Language $language ): Abstract_Element;

	/**
	 * Returns the list of HTML classes to add to the wrapper tag.
	 *
	 * @since 3.9
	 *
	 * @return string[]
	 */
	protected function get_wrapper_classes(): array {
		$classes = array_merge(
			$this->settings->wrapper_classes,
			array(
				'pll-switcher',
				"pll-layout-{$this->settings->layout}",
				"pll-alignment-{$this->settings->alignment}",
			)
		);

		if ( $this->settings->show_flags ) {
			$classes[] = 'pll-aspect-ratio-' . str_replace( ':', '', $this->settings->flag_aspect_ratio );
		}

		return $classes;
	}

	/**
	 * Tells whether the current theme supports HTML5 features.
	 *
	 * @since 3.9
	 *
	 * @return bool
	 */
	protected function supports_html5(): bool {
		$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

		/** This filter is documented in wp-includes/widgets/class-wp-nav-menu-widget.php */
		$format = apply_filters( 'navigation_widgets_format', $format );

		return 'html5' === $format;
	}
}
