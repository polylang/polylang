<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options;

use WP_Error;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use WP_Syntex\Polylang\Options\Abstract_Option;

defined( 'ABSPATH' ) || exit;

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
 * @implements IteratorAggregate<non-empty-string, mixed>
 *
 * @phpstan-import-type Schema from Abstract_Option as OptionSchema
 * @phpstan-type Schema array{
 *     '$schema': non-falsy-string,
 *     title: non-falsy-string,
 *     description: string,
 *     type: 'object',
 *     properties: array<non-falsy-string, OptionSchema>,
 *     additionalProperties: false
 * }
 */
class Options implements ArrayAccess, IteratorAggregate {
	public const OPTION_NAME = 'polylang';

	/**
	 * Polylang's options, by blog ID.
	 * Raw value if option is not registered yet, `Abstract_Option` instance otherwise.
	 *
	 * @var Abstract_Option[][]|mixed[][]
	 * @phpstan-var array<int, array<non-falsy-string, mixed>>
	 */
	private $options = array();

	/**
	 * Tells if the options have been modified, by blog ID.
	 *
	 * @var bool[]
	 * @phpstan-var array<int, true>
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
	 * @phpstan-var array<int, Schema>|null
	 */
	private $schema;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 */
	public function __construct() {
		// Keep track of the blog ID.
		$this->blog_id         = (int) get_current_blog_id();
		$this->current_blog_id = $this->blog_id;

		// Handle options.
		$this->init_options_for_current_blog();

		add_filter( 'pre_update_option_polylang', array( $this, 'protect_wp_option_storage' ), 1 );
		add_action( 'switch_blog', array( $this, 'on_blog_switch' ), -1000 ); // Options must be ready early.
		add_action( 'shutdown', array( $this, 'save_all' ), 1000 ); // Make sure to save options after everything.
	}

	/**
	 * Registers an option.
	 * Options must be registered in the right order: some options depend on other options' value.
	 *
	 * @since 3.7
	 *
	 * @param string $class_name  Option class to register.
	 * @return self
	 *
	 * @phpstan-param class-string<Abstract_Option> $class_name
	 */
	public function register( string $class_name ): self {
		foreach ( $this->options as &$options ) {
			$key = $class_name::key();

			if ( ! array_key_exists( $key, $options ) ) {
				// Option raw value doesn't exist in database, use default instead.
				$options[ $key ] = new $class_name();
				continue;
			}

			// If option exists in database, use this value.
			if ( $options[ $key ] instanceof Abstract_Option ) {
				// Already registered, do nothing.
				continue;
			}

			// Option raw value exists in database, use it.
			$options[ $key ] = new $class_name( $options[ $key ] );
		}

		return $this;
	}

	/**
	 * Prevents storing an instance of `Options` into the database.
	 *
	 * @since 3.7
	 *
	 * @param array|Options $value The options to store.
	 * @return array
	 */
	public function protect_wp_option_storage( $value ) {
		if ( $value instanceof self ) {
			return $value->get_all();
		}
		return $value;
	}

	/**
	 * Initializes options for the newly switched blog if applicable.
	 *
	 * @since 3.7
	 *
	 * @param int $blog_id The blog ID.
	 * @return void
	 */
	public function on_blog_switch( $blog_id ): void {
		$this->current_blog_id = (int) $blog_id;

		if ( isset( $this->options[ $blog_id ] ) ) {
			return;
		}

		if ( ! pll_is_plugin_active( POLYLANG_BASENAME ) && ! doing_action( 'activate_' . POLYLANG_BASENAME ) ) {
			return;
		}

		$this->init_options_for_current_blog();
	}

	/**
	 * Stores the options into the database for all blogs.
	 * Hooked to `shutdown`.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function save_all(): void {
		// Find blog with modified options.
		$modified = $this->get_modified();

		if ( empty( $modified ) ) {
			// Not modified.
			return;
		}

		remove_action( 'switch_blog', array( $this, 'on_blog_switch' ), -1000 );

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
		if ( empty( $this->modified[ $this->current_blog_id ] ) ) {
			return false;
		}

		unset( $this->modified[ $this->current_blog_id ] );

		if ( is_multisite() && ! get_site( $this->current_blog_id ) ) { // Cached by `$this->get_modified()` if called from `$this->save_all()`.
			// Deleted. Should not happen if called from `$this->save_all()`.
			return false;
		}

		$options = get_option( self::OPTION_NAME, array() );

		if ( is_array( $options ) ) {
			// Preserve options that are not from Polylang.
			$options = array_merge( $options, $this->get_all() );
		} else {
			$options = $this->get_all();
		}

		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Returns all options.
	 *
	 * @since 3.7
	 *
	 * @return mixed[] All options values.
	 */
	public function get_all(): array {
		if ( empty( $this->options[ $this->current_blog_id ] ) ) {
			// No options.
			return array();
		}

		return array_map(
			function ( $value ) {
				return $value->get();
			},
			array_filter(
				$this->options[ $this->current_blog_id ],
				function ( $value ) {
					return $value instanceof Abstract_Option;
				}
			)
		);
	}

	/**
	 * Merges a subset of options into the current blog ones.
	 *
	 * @since 3.7
	 *
	 * @param array $values Array of raw options.
	 * @return WP_Error
	 */
	public function merge( array $values ): WP_Error {
		$errors = new WP_Error();

		foreach ( $this->options[ $this->current_blog_id ] as $key => $option ) {
			if ( ! isset( $values[ $key ] ) || ! $this->has( $key ) ) {
				continue;
			}

			$option_errors = $this->set( $key, $values[ $key ] );

			if ( $option_errors->has_errors() ) {
				// Blocking and non-blocking errors.
				$errors->merge_from( $option_errors );
			}

			unset( $values[ $key ] );
		}

		if ( empty( $values ) ) {
			return $errors;
		}

		// Merge all "unknown option" errors into a single error message.
		if ( 1 === count( $values ) ) {
			/* translators: %s is an option name. */
			$message = __( 'Unknown option key %s.', 'polylang' );
		} else {
			/* translators: %s is a list of option names. */
			$message = __( 'Unknown option keys %s.', 'polylang' );
		}

		$errors->add(
			'pll_unknown_option_keys',
			sprintf(
				$message,
				wp_sprintf_l(
					'%l',
					array_map(
						function ( $value ) {
							return "'$value'";
						},
						array_keys( $values )
					)
				)
			)
		);

		return $errors;
	}

	/**
	 * Returns JSON schema for all options of the current blog.
	 *
	 * @since 3.7
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	public function get_schema(): array {
		if ( isset( $this->schema[ $this->current_blog_id ] ) ) {
			return $this->schema[ $this->current_blog_id ];
		}

		$properties = array();

		if ( ! empty( $this->options[ $this->current_blog_id ] ) ) {
			foreach ( $this->options[ $this->current_blog_id ] as $option ) {
				if ( ! $option instanceof Abstract_Option ) {
					continue;
				}

				$properties[ $option->key() ] = $option->get_schema();
			}
		}

		$this->schema[ $this->current_blog_id ] = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => static::OPTION_NAME,
			'description'          => __( 'Polylang options', 'polylang' ),
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);

		return $this->schema[ $this->current_blog_id ];
	}

	/**
	 * Tells if an option exists.
	 *
	 * @since 3.7
	 *
	 * @param string $key The name of the option to check for.
	 * @return bool
	 */
	public function has( string $key ): bool {
		return isset( $this->options[ $this->current_blog_id ][ $key ] ) && $this->options[ $this->current_blog_id ][ $key ] instanceof Abstract_Option;
	}

	/**
	 * Returns the value of the specified option.
	 *
	 * @since 3.7
	 *
	 * @param string $key The name of the option to retrieve.
	 * @return mixed
	 */
	public function get( string $key ) {
		if ( ! $this->has( $key ) ) {
			$v = null;
			return $v;
		}

		/** @var Abstract_Option */
		$option = $this->options[ $this->current_blog_id ][ $key ];
		return $option->get();
	}

	/**
	 * Assigns a value to the specified option.
	 *
	 * This doesn't allow to set an unknown option.
	 * When doing multiple `set()`, options must be set in the right order: some options depend on other options' value.
	 *
	 * @since 3.7
	 *
	 * @param string $key   The name of the option to assign the value to.
	 * @param mixed  $value The value to set.
	 * @return WP_Error
	 */
	public function set( string $key, $value ): WP_Error {
		if ( ! $this->has( $key ) ) {
			/* translators: %s is the name of an option. */
			return new WP_Error( 'pll_unknown_option_key', sprintf( __( 'Unknown option key %s.', 'polylang' ), "'$key'" ) );
		}

		/** @var Abstract_Option */
		$option    = $this->options[ $this->current_blog_id ][ $key ];
		$old_value = $option->get();

		if ( $option->set( $value, $this ) && $option->get() !== $old_value ) {
			// No blocking errors: the value can be stored.
			$this->modified[ $this->current_blog_id ] = true;
		}

		// Return errors.
		return $option->get_errors();
	}

	/**
	 * Resets an option to its default value.
	 *
	 * @since 3.7
	 *
	 * @param string $key The name of the option to reset.
	 * @return mixed The new value.
	 */
	public function reset( string $key ) {
		if ( ! $this->has( $key ) ) {
			return null;
		}

		/** @var Abstract_Option */
		$option = $this->options[ $this->current_blog_id ][ $key ];

		if ( $option->get() !== $option->reset() ) {
			$this->modified[ $this->current_blog_id ] = true;
		}

		return $option->get();
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
	public function offsetExists( $offset ): bool {
		return $this->has( (string) $offset );
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
	public function offsetGet( $offset ) {
		return $this->get( (string) $offset );
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
	public function offsetSet( $offset, $value ): void {
		$this->set( (string) $offset, $value );
	}

	/**
	 * Resets an option.
	 * This doesn't allow to unset an option, this resets it to its default value instead.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.7
	 *
	 * @param string $offset The name of the option to unset.
	 * @return void
	 */
	public function offsetUnset( $offset ): void {
		$this->reset( (string) $offset );
	}

	/**
	 * Returns all current site's option values.
	 * Required by interface `IteratorAggregate`.
	 *
	 * @since 3.7
	 *
	 * @return ArrayIterator
	 *
	 * @phpstan-return ArrayIterator<non-empty-string, mixed>
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->get_all() );
	}

	/**
	 * Retrieves site health information based on the current blog's options.
	 *
	 * Iterates through the options for the current blog, processes each one that is
	 * an instance of Abstract_Option, and adds relevant data to the site health information.
	 *
	 * @since 3.8
	 *
	 * @return array The site health information array.
	 */
	public function get_site_health_info() {
		$info = array();
		foreach ( $this->options[ $this->current_blog_id ] as $option ) {
			if ( ! $option instanceof Abstract_Option ) {
				continue;
			}
			$info = $option->add_to_site_health_info( $info, $this );
		}

		return $info;
	}

	/**
	 * Returns the list of modified sites.
	 * On multisite, sites are cached.
	 * /!\ At this point, some sites may have been deleted. They are removed from `$this->modified` here.
	 *
	 * @since 3.7
	 *
	 * @return bool[]
	 * @phpstan-return array<int, true>
	 */
	private function get_modified(): array {
		if ( empty( $this->modified ) ) {
			// Not modified.
			return $this->modified;
		}

		// Cleanup deleted sites and cache existing ones.
		if ( ! is_multisite() ) {
			// Not multisite: no need to cache or verify existence.
			return $this->modified;
		}

		// Fetch all the data instead of only the IDs, so it is cached.
		$sites = get_sites(
			array(
				'site__in' => array_keys( $this->modified ),
				'number'   => count( $this->modified ),
			)
		);

		// Keep only existing blogs.
		$this->modified = array();
		foreach ( $sites as $site ) {
			$this->modified[ $site->id ] = true;
		}

		return $this->modified;
	}

	/**
	 * Initializes options for the current blog.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	private function init_options_for_current_blog(): void {
		$options = get_option( self::OPTION_NAME );

		if ( empty( $options ) || ! is_array( $options ) ) {
			$this->options[ $this->current_blog_id ]  = array();
			$this->modified[ $this->current_blog_id ] = true;
		} else {
			$this->options[ $this->current_blog_id ] = $options;
		}

		/**
		 * Fires after the options have been init for the current blog.
		 * This is the best place to register options.
		 *
		 * @since 3.7
		 *
		 * @param Options $options         Instance of the options.
		 * @param int     $current_blog_id Current blog ID.
		 */
		do_action( 'pll_init_options_for_blog', $this, $this->current_blog_id );
	}
}
