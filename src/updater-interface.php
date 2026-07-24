<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contract an add-on's updater must fulfil to take part in the leader election.
 *
 * Lives in core because each add-on ships the updater under its own namespace: this interface is the only type they
 * all share, and therefore the only one the registry can type hint against.
 *
 * @since 3.9
 */
interface PLL_Updater_Interface {

	/**
	 * Returns the updater version, used to elect the leader.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public static function get_version(): string;

	/**
	 * Returns the product id, also the storage key in the `polylang_licenses` option.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Sets up what must not be duplicated. Called once, on the elected updater.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Base $polylang Polylang object.
	 * @return void
	 */
	public function boot_leader( PLL_Base $polylang ): void;
}
