<?php
/**
 * @package Polylang
 */

/**
 * Class Accept_Language
 *
 * Represents an Accept-Language HTTP Header, as defined in RFC 2616 Section 14.4 {@see https://tools.ietf.org/html/rfc2616.html#section-14.4}.
 */
class PLL_Accept_Language {
	/**
	 * @var string[]
	 */
	protected $subtags;

	/**
	 * @var float
	 */
	protected $quality;

	/**
	 * @var string[] Regular expression patterns.
	 */
	public static $subtag_patterns = array(
		'language' => '(\b[a-z]{2,3}|[a-z]{4}|[a-z]{5-8}\b)',
		'language-extension' => '(?:-(\b[a-z]{3}){1,3}\b)?',
		'script' => '(?:-(\b[a-z]{4})\b)?',
		'region' => '(?:-(\b[a-z]{2}|[0-9]{3})\b)?',
		'variant' => '(?:-(\b[0-9][a-z]{1,3}|[a-z][a-z0-9]{4,7})\b)?',
		'extension' => '(?:-(\b[a-wy-z]-[a-z0-9]{2,8})\b)?',
		'private-use' => '(?:-(\bx-[a-z0-9]{1,8})\b)?',
	);

	/**
	 * Parse Accept-Language HTTP header according to IETF BCP 47.
	 *
	 * TODO: Add grand-fathered language codes.
	 *
	 * @param string $http_header Value of the Accept-Language HTTP Header. Formatted as stated BCP 47 for language tags {@see https://tools.ietf.org/html/bcp47}.
	 * @return array {
	 * @since 3.0
	 */
	public static function parse_accept_language_header( $http_header ) {
		$lang_parse = array();
		// Break up string into pieces ( languages and q factors )
		$language_pattern = implode( '', self::$subtag_patterns );
		$quality_pattern = '\s*;\s*q\s*=\s*((?>1|0)(?>\.[0-9]+)?)';
		$full_pattern = "/{$language_pattern}(?:{$quality_pattern})?/i";

		preg_match_all(
			$full_pattern,
			sanitize_text_field( wp_unslash( $http_header ) ),
			$lang_parse,
			PREG_SET_ORDER
		);

		$accept_langs = array_map(
			array( self::class, 'from_array' ),
			$lang_parse
		);

		return $accept_langs;
	}

	/**
	 * PLL_Accept_Language constructor.
	 *
	 * @param string[] $subtags With subtag name as keys and subtag values as names.
	 * @param int      $quality
	 */
	public function __construct( $subtags, $quality = 1.0 ) {
		$this->subtags = $subtags;
		$this->quality = $quality;
	}

	/**
	 * Creates a new instance from an array resulting of a PHP {@see preg_match()} or {@see preg_match_all()} call.
	 *
	 * @param string[] $matches Expects first entry to be full match, following entries to be subtags and last entry to be quality factor.
	 * @return PLL_Accept_Language
	 */
	public static function from_array( $matches ) {
		$subtags = array_combine(
			array_keys( array_slice( self::$subtag_patterns, 0, count( $matches ) - 1 ) ),
			array_slice( $matches, 1, count( self::$subtag_patterns ) )
		);
		$quality = count( $matches ) === 9 ? floatval( $matches[8] ) : 1.0;

		return new PLL_Accept_Language( $subtags, $quality );
	}

	/**
	 * Returns the full language tag.
	 *
	 * @return string
	 */
	public function __toString() {
		$subtags = array_filter(
			$this->subtags,
			function ( $subtag ) {
				return ! empty( trim( $subtag ) );
			}
		);
		return implode( '-', $subtags );
	}

	/**
	 * Returns the quality factor as negotiated by the browser agent.
	 *
	 * @return float
	 */
	public function get_quality() {
		return $this->quality;
	}

	/**
	 * Returns a subtag from the language tag.
	 *
	 * @see PLL_Accept_Language::$subtag_patterns for available subtag names.
	 *
	 * @return string
	 */
	public function get_subtag( $name ) {
		return isset( $this->subtags[ $name ] ) ? $this->subtags[ $name ] : '';
	}
}
