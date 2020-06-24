<?php
/**
 * @package Polylang
 */

/**
 * Settings class to advertize the Translate slugs module
 *
 * @since 1.9
 */
class PLL_Settings_Translate_Slugs extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 80;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang polylang object
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'translate-slugs',
				'title'       => __( 'Translate slugs', 'polylang' ),
				'description' => __( 'Allows to translate custom post types and taxonomies slugs in urls.', 'polylang' ),
			)
		);
	}

	/**
	 * Tells if the module is active
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'PLL_Translate_Slugs_Model', true ) && get_option( 'permalink_structure' );
	}

	/**
	 * Displays upgrade message
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function get_upgrade_message() {
		return class_exists( 'PLL_Translate_Slugs_Model', true ) ? '' : $this->default_upgrade_message();
	}
}
