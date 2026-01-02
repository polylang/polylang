<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use Closure;
use PLL_Admin_Strings;

/**
 * Class to translate strings.
 *
 * @since 3.8
 */
class Translator {
	/**
	 * The name of the string.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The string to translate.
	 *
	 * @var string
	 */
	private $string;

	/**
	 * The context of the string.
	 *
	 * @var string
	 */
	private $context;

	/**
	 * Whether the string is multiline.
	 *
	 * @var bool
	 */
	private $multiline;

	/**
	 * The sanitization callback for the string.
	 *
	 * @var callable|null
	 */
	private $sanitize_callback;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param string        $string            The string to translate.
	 * @param string        $name              The name as defined in pll_register_string.
	 * @param string        $context           The context of the string.
	 * @param bool          $multiline         Whether the string is multiline.
	 * @param callable|null $sanitize_callback The sanitization callback for the string.
	 */
	public function __construct(
		string $string,
		string $name,
		string $context = 'Polylang',
		bool $multiline = false,
		?callable $sanitize_callback = null
	) {
		$this->string            = $string;
		$this->name              = $name;
		$this->context           = $context;
		$this->multiline         = $multiline;
		$this->sanitize_callback = $sanitize_callback ?? Closure::fromCallable( array( $this, 'default_sanitization' ) );

		add_filter( 'pll_sanitize_string_translation', array( $this, 'sanitize' ), 10, 5 );

		$this->register();
	}

	/**
	 * Sanitizes the string.
	 *
	 * @since 3.8
	 *
	 * @param string $translation The string translation.
	 * @param string $name        The name as defined in pll_register_string.
	 * @param string $context     The context as defined in pll_register_string.
	 * @param string $original    The original string to translate.
	 * @param string $previous    The previous translation if any.
	 * @return string The sanitized string.
	 */
	public function sanitize( $translation, $name, $context, $original, $previous ): string {
		if ( ! $this->is_matching( $name, $context ) ) {
			return $translation;
		}

		if ( trim( $previous ) === trim( $translation ) ) {
			return $translation;
		}

		return call_user_func( $this->sanitize_callback, $translation, $name, $context, $original );
	}

	/**
	 * Registers the string.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	private function register(): void {
		PLL_Admin_Strings::register_string( $this->name, $this->string, $this->context, $this->multiline );
	}

	/**
	 * Checks if the string matches.
	 *
	 * @since 3.8
	 *
	 * @param string $name          The name to compare.
	 * @param string $context       The context to compare.
	 * @return bool Whether the strings match.
	 */
	private function is_matching( string $name, string $context ): bool {
		return $this->name === $name && $this->context === $context;
	}

	/**
	 * The default sanitization callback.
	 *
	 * @since 3.8
	 *
	 * @param string $string The string to sanitize.
	 * @return string The sanitized string.
	 */
	private function default_sanitization( $string ) {
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return wp_kses_post( $string );
		}

		return $string;
	}
}
