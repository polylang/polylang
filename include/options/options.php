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
 *
 * @implements ArrayAccess<non-falsy-string, mixed>
 */
class PLL_Options implements ArrayAccess {
	const OPTION_NAME = 'polylang';

	/**
	 * Polylang's options, by blog ID.
	 * Raw value if option is not registered yet, `PLL_Abstract_Option` instance otherwise.
	 *
	 * @var PLL_Abstract_Option[][]|mixed[][]
	 * @phpstan-var array<int, array<non-falsy-string, mixed>>
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
	 * Cached options JSON schema by blog ID.
	 *
	 * @var array[]|null
	 */
	private $schema;

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
	 * @param string $class_name  Option class to register.
	 * @param string $key         Option key.
	 * @param mixed  $default     Option default value.
	 * @param mixed  ...$args     Additional arguments to pass to the constructor, except `$value` and `$key`.
	 * @return void
	 *
	 * @phpstan-param class-string<PLL_Abstract_Option> $class_name
	 */
	public function register( string $class_name, string $key, $default, ...$args ) {
		foreach ( $this->options as &$options ) {
			if ( ! array_key_exists( $key, $options ) ) {
				// Option raw value doesn't exist in database, use default instead.
				$options[ $key ] = new $class_name(
					$key,
					$default,
					$default,
					...$args
				);
				continue;
			}

			// If option exists in database, use this value.
			if ( $options[ $key ] instanceof PLL_Abstract_Option ) {
				// Already registered, do nothing.
				continue;
			}

			// Option raw value exists in database, use it.
			$options[ $key ] = new $class_name(
				$key,
				$options[ $key ],
				$default,
				...$args
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
		if ( ! is_array( $options ) || empty( $options ) ) {
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
		return update_option(
			self::OPTION_NAME,
			$this->get_all()
		);
	}

	/**
	 * Returns all options.
	 *
	 * @since 3.7
	 *
	 * @return mixed[] All options values.
	 */
	public function get_all(): array {
		return array_map(
			function ( $value ) {
				return $value->get();
			},
			array_filter(
				$this->options[ $this->current_blog_id ],
				function ( $value ) {
					return $value instanceof PLL_Abstract_Option;
				}
			)
		);
	}

	/**
	 * Merges a subset of options into the current blog ones.
	 *
	 * @since 3.7
	 *
	 * @param array $options Array of raw options.
	 * @return void
	 */
	public function merge( array $options ) {
		foreach ( $options as $key => $value ) {
			if ( isset( $this->options[ $this->current_blog_id ][ $key ] )
				&& $this->options[ $this->current_blog_id ][ $key ] instanceof PLL_Abstract_Option ) {
				$this->options[ $this->current_blog_id ][ $key ]->set( $value );
				$this->modified[ $this->current_blog_id ] = true;
			}
		}
	}

	/**
	 * Returns JSON schema for all options of the current blog.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 */
	public function get_schema(): array {
		if ( isset( $this->schema[ $this->current_blog_id ] ) ) {
			return $this->schema[ $this->current_blog_id ];
		}

		$properties = array();
		foreach ( $this->options[ $this->current_blog_id ] as $option ) {
			if ( ! $option instanceof PLL_Abstract_Option ) {
				continue;
			}

			$sub_schema = $option->get_schema();

			// Cleanup.
			unset( $sub_schema['title'], $sub_schema['$schema'] );

			$properties[ $option->key() ] = $sub_schema;
		}

		$this->schema[ $this->current_blog_id ] = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => static::OPTION_NAME,
			'description'          => __( 'Polylang options', 'polylang' ),
			'type'                 => 'object',
			'context'              => array( 'edit' ),
			'properties'           => $properties,
			'additionalProperties' => false,
		);

		return $this->schema[ $this->current_blog_id ];
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
		return isset( $this->options[ $this->current_blog_id ][ $offset ] ) && $this->options[ $this->current_blog_id ][ $offset ] instanceof PLL_Abstract_Option;
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
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] )
			|| ! $this->options[ $this->current_blog_id ][ $offset ] instanceof PLL_Abstract_Option ) {
			return null;
		}
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
		if ( ! array_key_exists( $offset, $this->options[ $this->current_blog_id ] )
			|| ! $this->options[ $this->current_blog_id ][ $offset ] instanceof PLL_Abstract_Option ) {
			return;
		}

		if ( $this->options[ $this->current_blog_id ][ $offset ]->set( $value ) ) {
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
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] )
			|| ! $this->options[ $this->current_blog_id ][ $offset ] instanceof PLL_Abstract_Option ) {
			return;
		}

		$this->modified[ $this->current_blog_id ] = true;
		$this->options[ $this->current_blog_id ][ $offset ]->reset();
	}
}
