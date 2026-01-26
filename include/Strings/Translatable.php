<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use Closure;
use Translation_Entry;
use WP_Syntex\Polylang\Strings\Database_Repository;

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
	private string $name;

	/**
	 * The string value to translate.
	 *
	 * @var string
	 */
	private string $source;

	/**
	 * The string value to translate.
	 *
	 * @var string
	 */
	private string $translation;

	/**
	 * The previous string value.
	 *
	 * @var string
	 */
	private string $previous_translation;

	/**
	 * The context/group of the string.
	 *
	 * @var string
	 */
	private string $context;

	/**
	 * Whether the string is multiline.
	 *
	 * @var bool
	 */
	private bool $multiline;

	/**
	 * The sanitization callback for the string.
	 *
	 * @var callable
	 */
	private $sanitize_callback;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param string        $source            The string to translate.
	 * @param string        $translation       The translation of the string.
	 * @param string        $name              The name as defined in pll_register_string.
	 * @param string        $context           The context of the string.
	 * @param callable|null $sanitize_callback The sanitization callback for the string.
	 * @param bool          $multiline         Whether the string is multiline.
	 */
	public function __construct(
		string $source,
		string $translation,
		string $name,
		?string $context = null,
		?callable $sanitize_callback = null,
		bool $multiline = false,
	) {
		$context                    = $context ?? 'Polylang';
		$this->id                   = md5( $source . $context );
		$this->source               = $source;
		$this->translation          = $translation;
		$this->previous_translation = $translation;
		$this->name                 = $name;
		$this->context              = $context;
		$this->multiline            = $multiline;
		$this->sanitize_callback    = $sanitize_callback ?? Closure::fromCallable( array( $this, 'default_sanitization' ) );

		add_filter( 'pll_sanitize_string_translation', array( $this, 'sanitize' ), 10, 5 );
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
	public function get_translation(): string {
		return $this->translation;
	}

	/**
	 * Gets the string value.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_source(): string {
		return $this->source;
	}

	/**
	 * Gets the previous string value.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_previous_translation(): string {
		return $this->previous_translation;
	}

	/**
	 * Sets the string value.
	 *
	 * @since 3.8
	 *
	 * @param string $translation The translation value.
	 * @return self The current instance.
	 */
	public function set_translation( string $translation ): self {
		$this->previous_translation = $this->translation;
		$this->translation          = $translation;

		return $this;
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
	 * Gets the translation entry for the current translatable string.
	 *
	 * @since 3.8
	 *
	 * @return Translation_Entry The translation entry.
	 */
	public function get_entry(): Translation_Entry {
		return new Translation_Entry(
			array(
				'singular'     => $this->source,
				'translations' => array( $this->translation ),
				'context'      => $this->context,
			)
		);
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
			// Don't overwrite the translation to prevent breaking the string.
			return $translation;
		}

		return call_user_func( $this->sanitize_callback, $translation, $name, $context, $original, $previous );
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
}
