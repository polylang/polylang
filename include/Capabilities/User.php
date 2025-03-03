<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

use PLL_Language;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * A class allowing to determine if a user can translate content.
 *
 * @since 3.8
 */
class User {
	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param int $user_id Optional. User ID. Default is the current user ID.
	 */
	public function __construct( int $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$this->user = new WP_User( $user_id );
	}

	/**
	 * Returns the user object.
	 *
	 * @since 3.8
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * Tells if the user is a translator (has a translator capability).
	 * Note: returns `true` if the user has a capability for a language that doesn't exist anymore.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_translator(): bool {
		foreach ( $this->user->allcaps as $cap => $one ) {
			if ( Tools::is_translator_capability( $cap ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells if the user can translate to the given language.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language|string $language A language object or language slug.
	 * @return bool
	 */
	public function can_translate( $language ): bool {
		if ( ! $this->is_translator() ) {
			return true;
		}

		return $this->user->has_cap( Tools::get_translator_capability( $language ) );
	}

	/**
	 * Filters a list of languages according to the user's capabilities.
	 *
	 * @since 3.8
	 *
	 * @param (PLL_Language|string)[] $languages An array of language objects or language slugs.
	 * @return (PLL_Language|string)[]
	 *
	 * @template TKey of array-key
	 * @template TValue of PLL_Language|string
	 * @phpstan-param array<TKey, TValue> $languages
	 * @phpstan-return array<TKey, TValue>
	 */
	public function filter_languages( array $languages ): array {
		if ( $this->is_translator() ) {
			return array_filter( $languages, array( $this, 'can_translate' ) );
		}

		return $languages;
	}
}
