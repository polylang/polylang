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
	public $priority = 90;

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
				'title'         => sprintf(
					/* translators: %s is a service name. */
					__( 'Machine Translation by %s', 'polylang' ),
					'DeepL'
				),
				'description'   => __( 'Allows linkage to an external translation solution.', 'polylang' ),
				'active_option' => '',
			)
		);
	}
}
