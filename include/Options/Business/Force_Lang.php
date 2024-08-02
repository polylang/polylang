<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options\Business;

use WP_Syntex\Polylang\Options\Abstract_Option;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the "Determine how the current language is defined" option.
 * /!\ Constructor depends on `hide_language_from_content_option`: this option must be set AFTER `hide_language_from_content_option`.
 *
 * @since 3.7
 */
class Force_Lang extends Abstract_Option {
	/**
	 * @var array
	 */
	private $enum = array( 0, 1, 2, 3 );

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param mixed   $value   Option value. Use `null` to set the default value.
	 * @param Options $options All options.
	 */
	public function __construct( $value, Options $options ) {
		if ( $options->get( 'hide_language_from_content_option' ) ) {
			$this->enum = array( 1, 2, 3 );
		}

		parent::__construct( $value );
	}

	/**
	 * Returns option key.
	 *
	 * @since 3.7
	 *
	 * @return string
	 *
	 * @phpstan-return 'force_lang'
	 */
	public static function key(): string {
		return 'force_lang';
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.7
	 *
	 * @return int
	 */
	protected function get_default() {
		return 1;
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 * @since 3.7
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'integer', enum: list<0|1|2|3>}
	 */
	protected function get_data_structure(): array {
		return array(
			'type' => 'integer',
			'enum' => $this->enum,
		);
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 * @since 3.7
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Determine how the current language is defined.', 'polylang' );
	}
}
