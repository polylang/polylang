<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\User;

use WP_User;
use PLL_Language;

/**
 * An interface for user with translation management feature.
 *
 * @since 3.8
 */
interface User_Interface {
	/**
	 * Returns the user ID.
	 *
	 * @since 3.8
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Tells if the user is a translator (has a translator capability).
	 * Note: returns `true` if the user has a capability for a language that doesn't exist anymore. This is intentional,
	 * to prevent the user to suddenly have the rights to translate in all languages while it wasn't allowed until then.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_translator(): bool;

	/**
	 * Tells if the user can translate to the given language.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language $language A language object.
	 * @return bool
	 */
	public function can_translate( PLL_Language $language ): bool;

	/**
	 * Tells if the user can translate to all the given languages.
	 *
	 * @since 3.8
	 *
	 * @param array $languages List of language slugs.
	 * @return bool
	 *
	 * @phpstan-param array<non-empty-string> $languages
	 */
	public function can_translate_all( array $languages ): bool;

	/**
	 * Tells if the user has the specified capability.
	 *
	 * @since 3.8
	 *
	 * @param string $capability Capability name.
	 * @param mixed  ...$args    Optional further parameters, typically starting with an object ID.
	 * @return bool
	 */
	public function has_cap( $capability, ...$args ): bool;

	/**
	 * Returns the preferred language of the user.
	 *
	 * @since 3.8
	 *
	 * @return string The preferred language slug, empty string if no preferred language is found.
	 */
	public function get_preferred_language_slug(): string;

	/**
	 * Checks if the current user has the rights to assign a language to an object and dies if not.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language $language The language to assign.
	 * @return void|never Dies if the user does not have the rights, does nothing otherwise.
	 */
	public function can_translate_or_die( PLL_Language $language ): void;
}
