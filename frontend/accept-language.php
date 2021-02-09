<?php
/**
 * @package Polylang
 */

/**
 * Class Accept_Language.
 *
 * Represents an Accept-Language HTTP Header, as defined in RFC 2616 Section 14.4 {@see https://tools.ietf.org/html/rfc2616.html#section-14.4}.
 *
 * @since 3.0
 */
class PLL_Accept_Language {
	const SUBTAG_PATTERNS = array(
		'language' => '(\b[a-z]{2,3}|[a-z]{4}|[a-z]{5-8}\b)',
		'language-extension' => '(?:-(\b[a-z]{3}){1,3}\b)?',
		'script' => '(?:-(\b[a-z]{4})\b)?',
		'region' => '(?:-(\b[a-z]{2}|[0-9]{3})\b)?',
		'variant' => '(?:-(\b[0-9][a-z]{1,3}|[a-z][a-z0-9]{4,7})\b)?',
		'extension' => '(?:-(\b[a-wy-z]-[a-z0-9]{2,8})\b)?',
		'private-use' => '(?:-(\bx-[a-z0-9]{1,8})\b)?',
	);

	/**
	 * @var string[] {
	 *  @type string $language           Usually 2 or three letters (ISO 639).
	 *  @type string $language-extension Up to three groups of 3 letters.
	 *  @type string $script             Four letters.
	 *  @type string $region             Either two letters of three digits.
	 *  @type string $variant            Either one digit followed by 1 to 3 letters, or a letter followed by 2 to 7 alphanumerical characters.
	 *  @type string $extension          One letter that cannot be an 'x', followed by 2 to 8 alphanumerical characters.
	 *  @type string $private-use        Starts by 'x-', followed by 1 to 8 alphanumerical characters.
	 * }
	 */
	protected $subtags;

	/**
	 * @var float
	 */
	protected $quality;

	/**
	 * PLL_Accept_Language constructor.
	 *
	 * @since 3.0
	 *
	 * @param string[] $subtags With subtag name as keys and subtag values as names.
	 * @param float    $quality
	 */
	public function __construct( $subtags, $quality = 1.0 ) {
		$this->subtags = $subtags;
		$this->quality = $quality;
	}

	/**
	 * Creates a new instance from an array resulting of a PHP {@see preg_match()} or {@see preg_match_all()} call.
	 *
	 * @since 3.0
	 *
	 * @param string[] $matches Expects first entry to be full match, following entries to be subtags and last entry to be quality factor.
	 * @return PLL_Accept_Language
	 */
	public static function from_array( $matches ) {
		$subtags = array_combine(
			array_keys( array_slice( self::SUBTAG_PATTERNS, 0, count( $matches ) - 1 ) ),
			array_slice( $matches, 1, count( self::SUBTAG_PATTERNS ) )
		);
		$quality = count( $matches ) === 9 ? floatval( $matches[8] ) : 1.0;

		return new PLL_Accept_Language( $subtags, $quality );
	}

	/**
	 * Returns the full language tag.
	 *
	 * @since 3.0
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
	 * @since 3.0
	 *
	 * @return float
	 */
	public function get_quality() {
		return $this->quality;
	}

	/**
	 * Returns a subtag from the language tag.
	 *
	 * @since 3.0
	 *
	 * @param string $name A valid subtag name, {@see PLL_Accept_Language::SUBTAG_PATTERNS} for available subtag names.
	 * @return string
	 */
	public function get_subtag( $name ) {
		return isset( $this->subtags[ $name ] ) ? $this->subtags[ $name ] : '';
	}
}
