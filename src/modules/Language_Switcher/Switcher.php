<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Language_Switcher;

use WP_Syntex\Polylang\Language_Switcher\Settings\Settings;
use WP_Syntex\Polylang\Language_Switcher\Switchers\Element\Abstract_Element;

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
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param Settings $settings  Instance of `Settings`.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
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
		$switcher = $this->settings->get_switcher();

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
		 */
		return (string) apply_filters( 'pll_language_switcher', $html, $this->settings );
	}

	/**
	 * Returns the switcher's raw data.
	 *
	 * @since 3.9
	 *
	 * @return Abstract_Element[]
	 */
	public function get_elements(): array {
		$switcher = $this->settings->get_switcher();

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

		$args = $this->settings->convert_to_legacy( $this->settings->to_array() );

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
