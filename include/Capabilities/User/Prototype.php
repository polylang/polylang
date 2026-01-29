<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\User;

use WP_User;

/**
 * A class for user prototypes.
 * Always returns a `NOOP` instance so capabilities features are disabled by default.
 *
 * @since 3.8
 */
class Prototype implements Prototype_Interface {
	/**
	 * The user instance to be used for capability checks.
	 *
	 * @var NOOP
	 */
	private ?NOOP $instance = null;

	/**
	 * Clones the user.
	 *
	 * @since 3.8
	 *
	 * @param WP_User $user The user to decorate.
	 * @return NOOP New instance of NOOP.
	 */
	public function get( WP_User $user ): NOOP {
		if ( $this->instance && $user->ID === $this->instance->get_id() ) {
			return $this->instance;
		}

		$this->instance = new NOOP( $user );

		return $this->instance;
	}
}
