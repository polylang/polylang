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
			'module'        => 'machine_translation',
			'title'         => sprintf(
				/* translators: %s is a service name. */
				__( 'Machine Translation by %s', 'polylang' ),
				'DeepL'
			),
			'description'   => __( 'Allows linkage to an external translation solution.', 'polylang' ),
			'active_option' => 'preview',
		);

		parent::__construct( $polylang, array_merge( $default, $args ) );
	}
}
