<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher;

use WP_Syntex\Polylang\Language_Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class that can display a language switcher.
 *
 * @since 3.9
 */
class Switcher {
	/**
	 * Prints the switcher.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Settings.
	 * @return void
	 */
	public function print( Settings $settings ): void {
		echo $this->get( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Returns the switcher's markup.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Settings.
	 * @return string
	 */
	public function get( Settings $settings ): string {
		$switcher = $settings->get_switcher();

		if ( empty( $switcher ) ) {
			return '';
		}

		$html = $this->maybe_filter_legacy_switcher( $switcher->get(), $settings );

		/**
		 * Filter the whole switcher markup.
		 *
		 * @since 3.9
		 *
		 * @param string   $html     Switcher markup.
		 * @param Settings $settings Switcher settings.
		 */
		return (string) apply_filters( 'pll_language_switcher', $html, $settings );
	}

	/**
	 * Returns the switcher's raw data.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings Settings.
	 * @return Abstract_Element[]
	 */
	public function get_elements( Settings $settings ): array {
		$switcher = $settings->get_switcher();

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
	 * @param string   $html     The switcher's markup.
	 * @param Settings $settings Settings.
	 * @return string
	 */
	private function maybe_filter_legacy_switcher( string $html, Settings $settings ): string {
		if ( ! has_filter( 'pll_the_languages' ) ) {
			return $html;
		}

		$args = $settings->convert_to_legacy( $settings->to_array() );

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
		return (string) apply_filters_deprecated( 'pll_the_languages', array( $html, $args ), '3.9.0', 'pll_language_switcher' );
	}
}
