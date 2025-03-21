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
	 * @param PLL_Settings $polylang Polylang object.
	 * @param array        $args     Optional. Addition arguments.
	 *
	 * @phpstan-param array{
	 *   module?: non-falsy-string,
	 *   title?: string,
	 *   description?: string,
	 *   active_option?: non-falsy-string
	 * } $args
	 */
	public function __construct( &$polylang, array $args = array() ) {
		$default = array(
			'module'        => 'translate-slugs',
			'title'         => __( 'Translate slugs', 'polylang' ),
			'description'   => $this->get_description(),
			'active_option' => 'preview',
		);

		parent::__construct( $polylang, array_merge( $default, $args ) );
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
}
