<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\User;

use WP_User;
use PLL_Language;

/**
 * A NOOP user class that decorates `WP_User` and deactivates language-related methods.
 * This class allows all translations but doesn't consider the user as a translator.
 *
 * @since 3.8
 */
class NOOP_User implements User_Interface {
	/**
	 * User instance to decorate.
	 *
	 * @var WP_User
	 */
	private WP_User $user;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param WP_User $user An instance of `WP_User`.
	 */
	public function __construct( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * Clones the user.
	 *
	 * @since 3.8
	 *
	 * @param WP_User $user The user to decorate.
	 * @return NOOP_User New instance of NOOP_User.
	 */
	public function clone( WP_User $user ): NOOP_User {
		if ( $user->ID === $this->get_id() ) {
			return $this;
		}

		return new self( $user );
	}

	/**
	 * Returns the user ID.
	 *
	 * @since 3.8
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->user->ID;
	}

	/**
	 * Tells if the user is a translator (has a translator capability).
	 * Always returns false for NOOP user.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_translator(): bool {
		return false;
	}

	/**
	 * Tells if the user can translate to the given language.
	 * Always returns true for NOOP user.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language $language A language object.
	 * @return bool
	 */
	public function can_translate( PLL_Language $language ): bool { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return true;
	}

	/**
	 * Tells if the user can translate to all the given languages.
	 * Always returns true for NOOP user.
	 *
	 * @since 3.8
	 *
	 * @param array $languages List of language slugs.
	 * @return bool
	 *
	 * @phpstan-param array<non-empty-string> $languages
	 */
	public function can_translate_all( array $languages ): bool { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return true;
	}

	/**
	 * Tells if the user has the specified capability.
	 * Delegates to WP_User.
	 *
	 * @since 3.8
	 *
	 * @param string $capability Capability name.
	 * @param mixed  ...$args    Optional further parameters, typically starting with an object ID.
	 * @return bool
	 */
	public function has_cap( $capability, ...$args ): bool {
		return $this->user->has_cap( $capability, ...$args );
	}

	/**
	 * Returns the preferred language of the user.
	 * Always returns an empty string for NOOP user.
	 *
	 * @since 3.8
	 *
	 * @return string The preferred language slug, empty string if no preferred language is found.
	 */
	public function get_preferred_language_slug(): string {
		return '';
	}

	/**
	 * Checks if the current user has the rights to assign a language to an object and dies if not.
	 * Does nothing for NOOP user (always allows).
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language $language The language to assign.
	 * @return void|never Dies if the user does not have the rights, does nothing otherwise.
	 */
	public function can_translate_or_die( PLL_Language $language ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Never say die!
	}
}
