<?php
/**
 * @package Polylang
 */

/**
 * A class to match values against a given format.
 *
 * @since 3.6
 * @since 3.7 Moved from Polylang Pro to Polylang.
 */
class PLL_Format_Util {
	/**
	 * Cache for regex patterns.
	 * Useful when using `filter_list()` for example.
	 *
	 * @var string[] Formats as array keys, patterns as array values.
	 *
	 * @phpstan-var array<non-empty-string, non-empty-string>
	 */
	private $patterns = array();

	/**
	 * Filters the given list to return only the values whose the key or value matches the given format.
	 *
	 * @since 3.6
	 * @since 3.7 Only accepts arrays as first parameter.
	 *
	 * @param array  $list   A list with keys or values to match against `$format`.
	 * @param string $format A format, where `*` means "any characters" (`.*`), unless escaped.
	 * @param string $mode   Optional. Tell if we should filter the keys or values from `$list`.
	 *                       Possible values are `'use_keys'` and `'use_values'`. Default is `'use_keys'`.
	 * @return array
	 *
	 * @template TArrayValue
	 * @phpstan-param ($mode is 'use_keys' ? array<string, TArrayValue> : array<string>) $list
	 * @phpstan-param 'use_keys'|'use_values' $mode
	 * @phpstan-return ($mode is 'use_keys' ? array<string, TArrayValue> : array<string>)
	 */
	public function filter_list( array $list, string $format, string $mode = 'use_keys' ): array {
		$filter = function ( $key ) use ( $format ) {
			return $this->matches( (string) $key, $format );
		};

		if ( 'use_values' === $mode ) {
			return array_filter( $list, $filter );
		}

		return array_filter( $list, $filter, ARRAY_FILTER_USE_KEY );
	}

	/**
	 * Tells if the given string matches the given format.
	 *
	 * @since 3.6
	 *
	 * @param string $key    A string to test.
	 * @param string $format A format, where `*` means "any characters" (`.*`), unless escaped.
	 * @return bool
	 */
	public function matches( string $key, string $format ): bool {
		if ( ! $this->is_format( $format ) ) {
			return $key === $format;
		}

		if ( '*' === $format ) {
			return true;
		}

		if ( empty( $this->patterns[ $format ] ) ) {
			$pattern = addcslashes( $format, '.+?[^]$(){}=!<>|:-#/' ); // Escape regular expression characters (list from `preg_quote()` but `*` and `\` are ignored).
			$pattern = preg_replace(
				array(
					'/\\\(?!\*)/', // Escape `\` characters except if followed by `*`.
					'/(?<!\\\)\*/', // Replace `*` characters by `.*` except if preceded by `\`.
				),
				array( '\\', '.*' ),
				$pattern
			);

			if ( empty( $pattern ) ) {
				// Error.
				return false;
			}

			$this->patterns[ $format ] = $pattern;
		} else {
			$pattern = $this->patterns[ $format ];
		}

		return (bool) preg_match( "/^{$pattern}$/", $key );
	}

	/**
	 * Tells if the given string is a format (that includes a `*`).
	 *
	 * @since 3.7
	 *
	 * @param string $format Format to test.
	 * @return bool
	 *
	 * @phpstan-assert-if-true non-empty-string $format
	 */
	public function is_format( string $format ): bool {
		return (bool) preg_match( '/(?<!\\\)\*/', $format ); // Match `*` characters unless if preceded by `\`.
	}
}
