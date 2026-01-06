<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use Closure;
use PLL_Admin_Strings;

/**
 * Entity representing a translatable string with registration and sanitization.
 *
 * @since 3.8
 */
class Translatable {
	/**
	 * The unique identifier for the string.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The name of the string.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The string value to translate.
	 *
	 * @var string
	 */
	private $string;

	/**
	 * The context/group of the string.
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
		$this->id                = md5( $string );
		$this->string            = $string;
		$this->name              = $name;
		$this->context           = $context;
		$this->multiline         = $multiline;
		$this->sanitize_callback = $sanitize_callback ?? Closure::fromCallable( array( $this, 'default_sanitization' ) );

		add_filter( 'pll_sanitize_string_translation', array( $this, 'sanitize' ), 10, 5 );

		$this->register();
	}

	/**
	 * Gets the unique identifier.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Gets the name.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Gets the string value.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_value(): string {
		return $this->string;
	}

	/**
	 * Gets the context.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_context(): string {
		return $this->context;
	}

	/**
	 * Checks if the string is multiline.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_multiline(): bool {
		return $this->multiline;
	}

	/**
	 * Registers the string.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function register(): void {
		PLL_Admin_Strings::register_string( $this->name, $this->string, $this->context, $this->multiline );
	}

	/**
	 * Sanitizes the string translation.
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
	 * Checks if the string matches the given name and context.
	 *
	 * @since 3.8
	 *
	 * @param string $name    The name to compare.
	 * @param string $context The context to compare.
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

	/**
	 * Converts the entity to an array representation.
	 *
	 * @since 3.8
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'        => $this->id,
			'name'      => $this->name,
			'string'    => $this->string,
			'context'   => $this->context,
			'multiline' => $this->multiline,
		);
	}
}
