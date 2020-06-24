<?php
/**
 * @package Polylang
 */

/**
 * A class to inform about the WPML compatibility module in Polylang settings
 *
 * @since 1.8
 */
class PLL_Settings_WPML extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 60;

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $polylang polylang object
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'wpml',
				'title'       => __( 'WPML compatibility', 'polylang' ),
				'description' => __( 'Polylang\'s WPML compatibility mode', 'polylang' ),
			)
		);
	}

	/**
	 * Tells if the module is active
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! defined( 'PLL_WPML_COMPAT' ) || PLL_WPML_COMPAT;
	}
}
