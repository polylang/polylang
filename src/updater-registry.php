<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elects a single "leader" among the add-ons' updaters.
 *
 * Several add-ons can run at once, each carrying its own namespaced copy of the updater, isolated from the others.
 * This registry lives in core as the single point they all share, and only arbitrates which updater is the leader.
 * HTTP and license logic stay in the package.
 *
 * @since 3.9
 */
class PLL_Updater_Registry {

	/**
	 * Registered updaters, keyed by product id. On equal versions, the first registered wins.
	 *
	 * @var PLL_Updater_Interface[]
	 */
	private static $candidates = array();

	/**
	 * The elected updater, once the election has run.
	 *
	 * @var PLL_Updater_Interface|null
	 */
	private static $elected = null;

	/**
	 * Announces an updater to the registry. Called from each add-on's Updater constructor.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Updater_Interface $updater The add-on's updater.
	 * @return void
	 */
	public static function register( PLL_Updater_Interface $updater ): void {
		if ( empty( self::$candidates ) ) {
			add_action( 'pll_init', array( self::class, 'elect' ), 0 );
		}

		self::$candidates[ $updater->get_id() ] = $updater;
	}

	/**
	 * Elects the highest-version updater and runs its setup once. Hooked to `pll_init`.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Base $polylang Polylang object.
	 * @return void
	 */
	public static function elect( PLL_Base $polylang ): void {
		$elected = null;

		foreach ( self::$candidates as $candidate ) {
			if ( null === $elected || version_compare( $candidate::get_version(), $elected::get_version(), '>' ) ) {
				$elected = $candidate;
			}
		}

		self::$elected = $elected;

		if ( null !== $elected ) {
			$elected->boot_leader( $polylang );
		}
	}

	/**
	 * Returns the elected updater, or null before the election has run.
	 *
	 * @since 3.9
	 *
	 * @return PLL_Updater_Interface|null
	 */
	public static function get_elected(): ?PLL_Updater_Interface {
		return self::$elected;
	}
}
