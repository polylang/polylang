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
	 * @var array<string, array{version: string, boot: callable}>
	 */
	private static $candidates = array();

	/**
	 * Announces an updater to the registry. Called from each add-on's Updater constructor.
	 *
	 * @since 3.9
	 *
	 * @param string   $id      Product id, also the storage key in the `polylang_licenses` option.
	 * @param string   $version Updater package version, from Updater::get_version().
	 * @param callable $boot    Leader-only setup: registers the shared licenses tab, wizard, AJAX and cron. Run once.
	 * @return void
	 */
	public static function register( string $id, string $version, callable $boot ): void {
		if ( empty( self::$candidates ) ) {
			add_action( 'pll_init', array( self::class, 'elect' ), 0 );
		}

		self::$candidates[ $id ] = array(
			'version' => $version,
			'boot'    => $boot,
		);
	}

	/**
	 * Elects the highest-version updater and runs its setup once. Hooked to `pll_init`.
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function elect(): void {
		if ( empty( self::$candidates ) ) {
			return;
		}

		$leader = null;
		foreach ( self::$candidates as $candidate ) {
			if ( null === $leader || version_compare( $candidate['version'], $leader['version'], '>' ) ) {
				$leader = $candidate;
			}
		}

		call_user_func( $leader['boot'] );
	}
}
