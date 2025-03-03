<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

use PLL_Language;
use WP_Syntex\Polylang\Model\Languages;

defined( 'ABSPATH' ) || exit;

/**
 * A class for translator capabilities.
 *
 * @since 3.8
 */
class Tools {
	public const PREFIX = 'pll_cap';

	/**
	 * Serializes a list of capabilities after adding all translator capabilities to it.
	 * Serialized capabilities are built as follow: `pll_cap|{Native WP capa}|{Custom PLL capa}|{Other custom PLL capa}`,
	 * where `pll_cap` is a prefix.
	 * This works like OR operators: `{Native WP capa}` OR `{Custom PLL capa}` OR `{Other custom PLL capa}`.
	 *
	 * @since 3.8
	 *
	 * @param array $capabilities List of capabilities.
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public static function serialize_capabilities( array $capabilities ): string {
		return implode(
			'|',
			array_merge(
				array( self::PREFIX ),
				$capabilities,
				self::get_all_translator_capabilities()
			)
		);
	}

	/**
	 * Unserializes a list of capabilities.
	 *
	 * @since 3.8
	 *
	 * @param string $capability A serialized list of capabilities.
	 * @return string[]
	 */
	public static function unserialize_capabilities( string $capability ): array {
		return array_diff(
			explode( '|', $capability ),
			array( self::PREFIX )
		);
	}

	/**
	 * Tells if the given capability is serialized.
	 *
	 * @since 3.8
	 *
	 * @param string $capability A serialized capability.
	 * @return bool
	 */
	public static function is_serialized_capability( string $capability ): bool {
		return str_starts_with( $capability, self::PREFIX );
	}

	/**
	 * Returns the translator capability related to the given language.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language|string $language A language object or language slug.
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public static function get_translator_capability( $language ): string {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		return "translate_{$language}";
	}

	/**
	 * Tells if the given capability is a translator capability (will also match languages that don't exist).
	 *
	 * @since 3.8
	 *
	 * @param string $capability A capability.
	 * @return bool
	 */
	public static function is_translator_capability( string $capability ): bool {
		$pattern = self::get_translator_capability( Languages::INNER_SLUG_PATTERN );
		return (bool) preg_match( "/^{$pattern}$/", $capability );
	}

	/**
	 * Returns all translator capabilities related to all existing languages.
	 *
	 * @since 3.8
	 *
	 * @return string[] Capabilities as array values, language slugs as array keys.
	 *
	 * @phpstan-return array<non-falsy-string, non-falsy-string>
	 */
	public static function get_all_translator_capabilities(): array {
		$translate_caps = array();

		foreach ( PLL()->model->languages->get_list() as $language ) {
			$translate_caps[ $language->slug ] = self::get_translator_capability( $language );
		}

		return $translate_caps;
	}
}
