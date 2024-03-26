<?php
/**
 * @package Polylang
 */

/**
 * Class that manages Polylang's options:
 * - Automatically stores the options into the database on `shutdown` if they have been modified.
 * - Behaves almost like an array, meaning only values can be get/set (implements `ArrayAccess`).
 * - Handles `switch_to_blog()`.
 * - Options are always defined: it is not possible to unset them from the list, they are set to their default value instead.
 * - If an option is not registered but exists in database, its raw value will be kept and remain untouched.
 *
 * @since 3.7
 */
class PLL_Options implements ArrayAccess {
	const OPTION_NAME = 'polylang';

	/**
	 * Polylang's options, by blog ID.
	 * Raw value if option is not registered yet, `PLL_Abstract_Option` instance otherwise.
	 *
	 * @var PLL_Abstract_Option|mixed[][]
	 */
	private $options = array();

	/**
	 * Tells if the options have been modified, by blog ID.
	 *
	 * @var bool[]
	 * @phpstan-var array<int, bool>
	 */
	private $modified = array();

	/**
	 * The original blog ID.
	 *
	 * @var int
	 */
	private $blog_id;

	/**
	 * The current blog ID.
	 *
	 * @var int
	 */
	private $current_blog_id;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 */
	public function __construct() {
		// Keep track of the blog ID.
		$this->blog_id = (int) get_current_blog_id();

		// Handle options.
		$this->init_options_for_blog( $this->blog_id );

		add_action( 'switch_blog', array( $this, 'init_options_for_blog' ), PHP_INT_MIN );
		add_action( 'shutdown', array( $this, 'save_all' ) );
	}

	/**
	 * Registers an option.
	 *
	 * @since 3.7
	 *
	 * @param string $key       Option key.
	 * @param string $classname Classname of `PLL_Abstract_Option` instance.
	 * @param mixed  $default   Default option value.
	 * @return PLL_Abstract_Option The option instance, in case some process is needed afterward.
	 */
	public function register( string $key, string $classname, $default ) {
		foreach( $this->options as $blog_id => $options ) {
			if ( ! isset( $options[ $key ] ) ) {
				// If option doesn't exist in database, use default value.
				$this->options[ $blog_id ][ $key ] = new $classname(
					$key,
					$default,
					$default
				);
				continue;
			}

			$this->options[ $blog_id ][ $key ] = new $classname(
				$key,
				$this->options[ $blog_id ][ $key ],
				$default
			);
		}
	}

	/**
	 * Initializes options for the given blog:
	 * - stores the blog ID,
	 * - stores the options.
	 * Hooked to `switch_blog`.
	 *
	 * @since 3.7
	 *
	 * @param int $blog_id The blog ID.
	 * @return void
	 */
	public function init_options_for_blog( $blog_id ) {
		$this->current_blog_id = (int) $blog_id;

		if ( isset( $this->options[ $blog_id ] ) ) {
			return;
		}

		if ( ! pll_is_plugin_active( POLYLANG_BASENAME ) ) {
			return;
		}

		$options = get_option( self::OPTION_NAME );
		if ( empty( $options ) ) {
			return;
		}

		$this->options[ $blog_id ] = $options;
	}

	/**
	 * Stores the options into the database for all blogs.
	 * Hooked to `shutdown`.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function save_all() {
		// Find blog with modified options.
		$modified = array_filter( $this->modified );

		if ( empty( $modified ) ) {
			return;
		}

		remove_action( 'switch_blog', array( $this, 'init_options_for_blog' ), PHP_INT_MIN );

		// Handle the original blog first, maybe this will prevent the use of `switch_to_blog()`.
		if ( isset( $modified[ $this->blog_id ] ) && $this->current_blog_id === $this->blog_id ) {
			$this->save();
			unset( $modified[ $this->blog_id ] );

			if ( empty( $modified ) ) {
				// All done, no need of `switch_to_blog()`.
				return;
			}
		}

		foreach ( $modified as $blog_id => $_yup ) {
			switch_to_blog( $blog_id );
			$this->save();
			restore_current_blog();
		}

	}

	/**
	 * Stores the options into the database.
	 *
	 * @since 3.7
	 *
	 * @return bool True if the options were updated, false otherwise.
	 */
	public function save(): bool {
		if ( ! $this->modified[ $this->current_blog_id ] ) {
			return false;
		}

		$this->modified[ $this->current_blog_id ] = false;
		return update_option( self::OPTION_NAME, $this->options[ $this->current_blog_id ] );
	}

	/**
	 * Returns all options.
	 *
	 * @since 3.7
	 *
	 * @return mixed[]
	 */
	public function get_all(): array {
		return array_map(
			function ( $value ) {
				if ( $value instanceof PLL_Abstract_Option ) {
					$value = $value->get();
				}

				return $value;
			},
			$this->options[ $this->current_blog_id ]
		);
	}

	/**
	 * Tells if an option exists.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.7
	 *
	 * @param string $offset The name of the option to check for.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ): bool {
		return isset( $this->options[ $this->current_blog_id ][ $offset ] );
	}

	/**
	 * Returns the value of the specified option.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.7
	 *
	 * @param string $offset The name of the option to retrieve.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet( $offset ) {
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] ) ) {
			return null;
		}

		return $this->options[ $this->current_blog_id ][ $offset ]->get();
	}

	/**
	 * Assigns a value to the specified option.
	 * This doesn't allow to set an unknown option.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.7
	 *
	 * @param string $offset The name of the option to assign the value to.
	 * @param mixed  $value  The value to set.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] ) ) {
			return;
		}

		if ( $this->options[ $this->current_blog_id ][ $offset ] instanceof PLL_Abstract_Option
			&& $this->options[ $this->current_blog_id ][ $offset ]->set( $value ) ) {
			$this->modified[ $this->current_blog_id ] = true;
		}
	}

	/**
	 * Resets an option.
	 * Also sets the property `$modified` if the value changes.
	 * This doesn't allow to unset an option, this resets it to its default value instead.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.7
	 *
	 * @param string $offset The name of the option to unset.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] ) ) {
			return;
		}

		$this->modified[ $this->current_blog_id ] = true;
		$this->options[ $this->current_blog_id ][ $offset ]->reset();
	}
}
