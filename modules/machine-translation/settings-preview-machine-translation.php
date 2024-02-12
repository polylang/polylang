<?php
/**
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to advertize the Machine Translation module.
 *
 * @since 3.6
 */
class PLL_Settings_Preview_Machine_Translation extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 85;

	/**
	 * Constructor.
	 *
	 * @since 3.6
	 *
	 * @param PLL_Settings $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'        => 'machine_translation',
				'title'         => __( 'Machine Translation', 'polylang' ),
				'description'   => __( 'Opt in for a machine translation service for post types.', 'polylang' ),
				'active_option' => '',
			)
		);
	}
}
