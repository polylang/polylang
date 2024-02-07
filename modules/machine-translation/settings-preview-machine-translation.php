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
				'module'      => 'machine-translation',
				'title'       => __( 'Machine Translation', 'polylang' ),
				'description' => $this->get_description(),
			)
		);
	}

	/**
	 * Returns the module description.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	protected function get_description() {
		return __( 'Opt in for a machine translation service for post types.', 'polylang' );
	}

	/**
	 * Tells if the module is active.
	 *
	 * @since 3.6
	 *
	 * @return bool
	 */
	public function is_active() {
		return false;
	}

	/**
	 * Displays an upgrade message.
	 *
	 * @since 3.6
	 *
	 * @return string
	 */
	public function get_upgrade_message() {
		return $this->default_upgrade_message();
	}
}
