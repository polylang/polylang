<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

use WP_User;
use PLL_Model;
use PLL_Language;
use WP_Syntex\Polylang\Model\Languages;

defined( 'ABSPATH' ) || exit;

/**
 * A class wrapping `WP_User` with translation management feature.
 *
 * @since 3.8
 */
class User {
	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var bool|null
	 */
	private $is_translator;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param WP_User|null $user Optional. An instance of `WP_User`.
	 */
	public function __construct( ?WP_User $user = null ) {
		if ( empty( $user ) ) {
			$user = wp_get_current_user();
		}
		$this->user = $user;
	}

	/**
	 * Tells if the user is a translator (has a translator capability).
	 * Note: returns `true` if the user has a capability for a language that doesn't exist anymore. This is intentional,
	 * to prevent the user to suddenly have the rights to translate in all languages while it wasn't allowed until then.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_translator(): bool {
		if ( isset( $this->is_translator ) ) {
			return $this->is_translator;
		}

		$pattern             = Languages::INNER_SLUG_PATTERN;
		$this->is_translator = ! empty( preg_grep( "/^translate_{$pattern}$/", array_keys( $this->user->allcaps ) ) );

		return $this->is_translator;
	}

	/**
	 * Tells if the user can translate to the given language.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Language $language A language object.
	 * @return bool
	 */
	public function can_translate( PLL_Language $language ): bool {
		if ( ! $this->is_translator() ) {
			return true;
		}

		return $this->user->has_cap( "translate_{$language->slug}" );
	}

	/**
	 * Tells if the user has the specified capability.
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
	 *
	 * @since 3.8
	 *
	 * @param PLL_Model $model The model instance.
	 * @return PLL_Language|null
	 */
	public function get_preferred_language( PLL_Model $model ): ?PLL_Language {
		$pattern       = Languages::INNER_SLUG_PATTERN;
		$caps_array    = array_combine( array_keys( $this->user->allcaps ), array_keys( $this->user->allcaps ) );
		$language_caps = preg_grep( "/^translate_{$pattern}$/", $caps_array );

		if ( empty( $language_caps ) ) {
			return null;
		}

		// Arbitrarily use the first language cap.
		$language_cap = reset( $language_caps );
		$language     = $model->get_language(
			str_replace( 'translate_', '', $language_cap )
		);

		return $language ?: null;
	}
}
