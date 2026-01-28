<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\User;

use WP_User;

/**
 * An interface for user prototypes.
 *
 * @since 3.8
 */
interface Prototype_Interface {
	/**
	 * Returns the user instance to be used for capability checks using prototype pattern.
	 *
	 * @since 3.8
	 *
	 * @param WP_User $user The user to decorate. If null, the current user is used.
	 * @return User_Interface The user instance.
	 */
	public function get( WP_User $user ): User_Interface;
}
