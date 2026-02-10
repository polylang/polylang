<?php

namespace WP_Syntex\Polylang\Tests\Includes\Mockery;

use WP_User;
use PLL_Language;
use WP_Syntex\Polylang\Model\Languages;
use WP_Syntex\Polylang\Capabilities\User\User_Interface;
use WP_Syntex\Polylang\Capabilities\User\Creator_Interface;

class Mock_Translator implements User_Interface, Creator_Interface {
	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var string[]|null
	 */
	private $language_caps;

	/**
	 * @var bool[]
	 */
	private $can_translate = array();

	/**
	 * @param WP_User $user An instance of `WP_User`.
	 */
	public function __construct( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * @param WP_User $user The user.
	 * @return User_Interface The decorated user.
	 */
	public function get( WP_User $user ): User_Interface {
		return new self( $user );
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->user->ID;
	}

	/**
	 * @return bool
	 */
	public function is_translator(): bool {
		return ! empty( $this->get_language_caps() );
	}

	/**
	 * @param PLL_Language $language A language object.
	 * @return bool
	 */
	public function can_translate( PLL_Language $language ): bool {
		if ( isset( $this->can_translate[ $language->slug ] ) ) {
			return $this->can_translate[ $language->slug ];
		}

		if ( ! $this->is_translator() ) {
			$this->can_translate[ $language->slug ] = true;
		} else {
			$this->can_translate[ $language->slug ] = $this->user->has_cap( "translate_{$language->slug}" );
		}

		return $this->can_translate[ $language->slug ];
	}

	/**
	 * @param array $languages List of language slugs.
	 * @return bool
	 */
	public function can_translate_all( array $languages ): bool {
		if ( ! $this->is_translator() ) {
			return true;
		}

		foreach ( $languages as $language_slug ) {
			if ( ! isset( $this->can_translate[ $language_slug ] ) ) {
				$this->can_translate[ $language_slug ] = $this->user->has_cap( "translate_{$language_slug}" );
			}
			if ( ! $this->can_translate[ $language_slug ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $capability Capability name.
	 * @param mixed  ...$args    Optional further parameters, typically starting with an object ID.
	 * @return bool
	 */
	public function has_cap( $capability, ...$args ): bool {
		return $this->user->has_cap( $capability, ...$args );
	}

	/**
	 * @return string The preferred language slug, empty string if no preferred language is found.
	 */
	public function get_preferred_language_slug(): string {
		$language_caps = $this->get_language_caps();

		if ( empty( $language_caps ) ) {
			return '';
		}

		// Arbitrarily use the first language cap.
		$language_cap = reset( $language_caps );

		return str_replace( 'translate_', '', $language_cap );
	}

	/**
	 * @return array
	 */
	private function get_language_caps(): array {
		if ( isset( $this->language_caps ) ) {
			return $this->language_caps;
		}

		$this->language_caps = (array) preg_grep( '/^translate_' . Languages::INNER_SLUG_PATTERN . '$/', array_keys( $this->user->allcaps ) );

		return $this->language_caps;
	}

	/**
	 * @param PLL_Language $language The language to assign.
	 * @return void|never Dies if the user does not have the rights, does nothing otherwise.
	 */
	public function can_translate_or_die( PLL_Language $language ): void {
		if ( ! $this->can_translate( $language ) ) {
			wp_die( esc_html( sprintf( 'Sorry, you are not allowed to edit content in %s.', $language->name ) ) );
		}
	}
}
