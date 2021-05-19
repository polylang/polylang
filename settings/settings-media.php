<?php
/**
 * @package Polylang
 */

/**
 * Settings class for media language and translation management
 *
 * @since 1.8
 */
class PLL_Settings_Media extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 30;

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
				'module'        => 'media',
				'title'         => __( 'Media', 'polylang' ),
				'description'   => __( 'Activate languages and translations for media only if you need to translate the text attached to the media: the title, the alternative text, the caption, the description... Note that the file is not duplicated.', 'polylang' ),
				'active_option' => 'media_support',
			)
		);
	}
}
