<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options;

use WP_Error;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining a decorator for options when Polylang is not active on the current site.
 *
 * @since 3.8
 */
class Inactive_Option extends Abstract_Option {
	public const ERROR_CODE = 'pll_not_active';

	/**
	 * The option to decorate.
	 *
	 * @var Abstract_Option
	 */
	private $option;

	/**
	 * The key of the option to decorate.
	 *
	 * @var string
	 *
	 * @phpstan-var non-falsy-string
	 */
	private static $key = 'not-an-option';

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Abstract_Option $option The option to wrap.
	 */
	public function __construct( Abstract_Option $option ) {
		$this->option = $option;
		self::$key    = $option::key();
		$this->errors = new WP_Error();
	}

	/**
	 * Returns option key.
	 *
	 * @since 3.8
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	public static function key(): string {
		return self::$key;
	}

	/**
	 * Does nothing except adding an error.
	 *
	 * @since 3.8
	 *
	 * @param mixed   $value   Value to set.
	 * @param Options $options All options.
	 * @return bool True if the value has been assigned. False in case of errors.
	 */
	public function set( $value, Options $options ): bool { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! in_array( self::ERROR_CODE, $this->errors->get_error_codes(), true ) ) {
			$this->errors->add(
				self::ERROR_CODE,
				/* translators: %s is a blog ID. */
				sprintf( __( 'Polylang is not active on site %s.', 'polylang' ), (int) get_current_blog_id() )
			);
		}
		return false;
	}

	/**
	 * Returns the value of the option, usually the default value for inactive options.
	 *
	 * @since 3.8
	 *
	 * @return mixed
	 */
	public function get() {
		return $this->option->get();
	}

	/**
	 * Returns an empty schema.
	 *
	 * @since 3.8
	 *
	 * @return array The schema.
	 */
	public function get_schema(): array {
		return array();
	}

	/**
	 * Returns the default value.
	 *
	 * @since 3.8
	 *
	 * @return mixed
	 */
	public function get_default() {
		return $this->option->get_default();
	}

	/**
	 * Not used but required by `Abstract_Option`.
	 *
	 * @since 3.8
	 *
	 * @return array Partial schema.
	 */
	protected function get_data_structure(): array {
		return array();
	}

	/**
	 * Not used but required by `Abstract_Option`.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return '';
	}
}
