<?php
/**
 * @package Polylang
 */

/**
 * Class to advertize the Translate slugs module.
 *
 * @since 1.9
 * @since 3.1 Renamed from PLL_Settings_Translate_Slugs.
 */
class PLL_Settings_Preview_Translate_Slugs extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 80;

	/**
	 * Constructor.
	 *
	 * @since 1.9
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'translate-slugs',
				'title'       => __( 'Translate slugs', 'polylang' ),
				'description' => $this->get_description(),
			)
		);
	}

	/**
	 * Returns the module description.
	 *
	 * @since 3.1
	 *
	 * @return string
	 */
	protected function get_description() {
		return __( 'Allows to translate custom post types and taxonomies slugs in URLs.', 'polylang' );
	}

	/**
	 * Tells if the module is active.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_active() {
		return false;
	}

	/**
	 * Displays an upgrade message.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function get_upgrade_message() {
		return $this->default_upgrade_message();
	}
}
