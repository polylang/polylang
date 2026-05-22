<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

use PLL_Links;
use WP_Syntex\Polylang\Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class that can display a language switcher.
 *
 * @since 3.9
 */
class Switcher {
	/**
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * @var PLL_Links
	 */
	private PLL_Links $links;

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param Settings  $settings Instance of `Settings`.
	 * @param PLL_Links $links    Instance of `PLL_Links`.
	 */
	public function __construct( Settings $settings, PLL_Links $links ) {
		$this->settings = $settings;
		$this->links    = $links;
	}

	/**
	 * Prints the switcher.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public function print(): void {
		echo $this->get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Returns the switcher's markup.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get(): string {
		$switcher_class = $this->settings->get_switcher_class();

		if ( ! class_exists( $switcher_class ) ) {
			return '';
		}

		$html = ( new $switcher_class( $this->settings, $this->links ) )->get();

		if ( has_filter( 'pll_the_languages' ) ) {
			/**
			 * Filter the whole HTML markup returned by the 'pll_the_languages' template tag.
			 *
			 * @since 0.8
			 * @since 3.9 Deprecated.
			 * @deprecated
			 *
			 * @param string $html HTML returned/outputted by the template tag.
			 * @param array  $args Arguments passed to the template tag.
			 */
			$html = (string) apply_filters_deprecated(
				'pll_the_languages',
				array( $html, $this->settings->get_legacy() ),
				'3.9.0',
				'pll_language_switcher_output'
			);
		}

		/**
		 * Filter the whole switcher markup.
		 *
		 * @since 3.9
		 *
		 * @param string   $html     Switcher markup.
		 * @param Settings $settings Switcher settings.
		 * @param Switcher $switcher Switcher's instance.
		 */
		return (string) apply_filters( 'pll_language_switcher_output', $html, $this->settings, $this );
	}

	/**
	 * Returns the switcher's raw data.
	 *
	 * @since 3.9
	 *
	 * @return Element\Abstract_Element[]
	 */
	public function get_elements(): array {
		$switcher_class = $this->settings->get_switcher_class();

		if ( ! class_exists( $switcher_class ) ) {
			return array();
		}

		$switcher = new $switcher_class( $this->settings, $this->links );

		return $switcher->get_elements();
	}
}
