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
		$subtags = array(
			'language' => '([a-z]{2,3}|[a-z]{4}|[a-z]{5-8})\b',
			'language-extension' => '(-(?:[a-z]{3}){1,3}\b)?',
			'script' => '(-[a-z]{4}\b)?',
			'region' => '(-(?:[a-z]{2}|[0-9]{3})\b)?',
			'variant' => '(-(?:[0-9][a-z]{1,3}|[a-z][a-z0-9]{4,7})\b)?',
			'extension' => '(-[a-wy-z]-[a-z0-9]{2,8}\b)?',
			'private-use' => '(-x-[a-z0-9]{1,8}\b)?',
		);
		$language_pattern = "{$subtags['language']}{$subtags['language-extension']}{$subtags['script']}{$subtags['region']}{$subtags['variant']}{$subtags['extension']}{$subtags['private-use']}";
		$quality_pattern = '\s*;\s*q\s*=\s*((?>1|0)(?>\.[0-9]+)?)';
		$full_pattern = "/({$language_pattern})({$quality_pattern})?/i";
		preg_match_all(
			$full_pattern,
			sanitize_text_field( wp_unslash( $http_header ) ),
			$lang_parse
		);
		return array('language' => $lang_parse[1], 'quality' => $lang_parse[10]);
	}
}
