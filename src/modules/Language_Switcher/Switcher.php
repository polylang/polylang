<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher;

use PLL_Links;
use WP_Syntex\Polylang\Language_Switcher\Settings\Settings;

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
		$switcher = $this->settings->get_switcher( $this->links );

		if ( empty( $switcher ) ) {
			return '';
		}

		$html = $this->maybe_filter_legacy_switcher( $switcher->get() );

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
	 * @return Switchers\Element\Abstract_Element[]
	 */
	public function get_elements(): array {
		$switcher = $this->settings->get_switcher( $this->links );

		if ( empty( $switcher ) ) {
			return array();
		}

		return $switcher->get_elements();
	}

	/**
	 * Returns the switcher's markup after applying the deprecated filter `pll_the_languages`.
	 * However, 100% backward compatibility is not ensured since the markup is different.
	 *
	 * @since 3.9
	 *
	 * @param string $html The switcher's markup.
	 * @return string
	 */
	private function maybe_filter_legacy_switcher( string $html ): string {
		if ( ! has_filter( 'pll_the_languages' ) ) {
			return $html;
		}

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
		return (string) apply_filters_deprecated(
			'pll_the_languages',
			array( $html, $this->settings->get_legacy() ),
			'3.9.0',
			'pll_language_switcher'
		);
	}
}
