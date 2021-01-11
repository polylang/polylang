<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Accept_Languages_Collection
 *
 * Represents a collection of values parsed from an Accept-Language HTTP header.
 */
class PLL_Accept_Languages_Collection {
	/**
	 * @var PLL_Accept_Language[]
	 */
	protected $accept_languages = array();

	/**
	 * PLL_Accept_Languages_Collection constructor.
	 *
	 * @param PLL_Accept_Language[] $accept_languages
	 */
	public function __construct( $accept_languages ) {
		$this->accept_languages = $accept_languages;
	}

	/**
	 * @return PLL_Accept_Language[]
	 */
	public function bubble_sort() {
		$k = $this->accept_languages;
		$v = array_map(
			function ( $accept_lang ) {
				return $accept_lang->get_quality();
			},
			$this->accept_languages
		);

		if ( $n = count( $k ) ) {
			// Set default to 1 for any without q factor
			foreach ( $v as $key => $val ) {
				if ( '' === $val || (float) $val > 1 ) {
					$v[ $key ] = 1;
				}
			}

			// Bubble sort ( need a stable sort for Android, so can't use a PHP sort function )
			if ( $n > 1 ) {
				for ( $i = 2; $i <= $n; $i++ ) {
					for ( $j = 0; $j <= $n - 2; $j++ ) {
						if ( $v[ $j ] < $v[ $j + 1 ] ) {
							// Swap values
							$temp = $v[ $j ];
							$v[ $j ] = $v[ $j + 1 ];
							$v[ $j + 1 ] = $temp;
							// Swap keys
							$temp = $k[ $j ];
							$k[ $j ] = $k[ $j + 1 ];
							$k[ $j + 1 ] = $temp;
						}
					}
				}
			}
			$this->accept_languages = array_filter(
				$k,
				function ( $accept_lang ) {
					return $accept_lang->get_quality() > 0;
				}
			);
		}
		return $this->accept_languages;
	}

	/**
	 * Looks through sorted list and use first one that matches our language list
	 *
	 * @param PLL_Language[] $languages
	 * @return string|false A language slug if there's a match, false otherwise.
	 */
	public function find_best_match( $languages ) {
		foreach ( $this->accept_languages as $accept_lang ) {
			// First loop to match the exact locale
			foreach ( $languages as $language ) {
				if ( 0 === strcasecmp( $accept_lang, $language->get_locale( 'display' ) ) ) {
					return $language->slug;
				}
			}

			// In order of priority
			$subsets = array();
			if ( ! empty( $accept_lang->get_subtag( 'region' ) ) ) {
				$subsets[] = $accept_lang->get_subtag( 'language' ) . '-' . $accept_lang->get_subtag( 'region' );
				$subsets[] = $accept_lang->get_subtag( 'region' );
			}
			if ( ! empty( $accept_lang->get_subtag( 'variant' ) ) ) {
				$subsets[] = $accept_lang->get_subtag( 'language' ) . '-' . $accept_lang->get_subtag( 'variant' );
			}
			$subsets[] = $accept_lang->get_subtag( 'language' );

			// More loops to match the subsets
			foreach ( $languages as $language ) {
				foreach ( $subsets as $subset ) {

					if ( 0 === stripos( $subset, $language->slug ) || 0 === stripos( $language->get_locale( 'display' ), $subset ) ) {
						return $language->slug;
					}
				}
			}
		}
		return false;
	}

}
