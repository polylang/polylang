<?php

/**
 * Trait to handle PLL_MO objects.
 */
trait PLL_MO_Trait {
	/**
	 * Flushes the PLL_MO cache for the given languages.
	 *
	 * @param PLL_Language[] $languages The languages to flush the cache for.
	 */
	public function flush_pll_mo_cache( array $languages ): void {
		if ( empty( $languages ) ) {
			return;
		}

		$mo = new PLL_MO();
		foreach ( $languages as $lang ) {
			// Flush the cache.
			$mo->export_to_db( $lang );
		}
	}
}
