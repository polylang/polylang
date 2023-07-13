<?php
/**
 * @package Polylang
 */

/**
 * Class that manages Polylang's options:
 * - Automatically stores the options into the database on `shutdown` if they have been modified.
 * - Behaves almost like an array (implements `ArrayAccess`, `Countable`, `Iterator`, and `JsonSerializable`).
 * - Handles `switch_to_blog()`.
 * - Whitelists options: it is not possible to add unknown options (not listed in PLL_Options::DEFAULTS).
 * - Options are always defined: it is not possible to unset them from the list, they are set to their default value instead.
 * - Automatic cast + limited sanitization/validation (invalid values are not set).
 *
 * @since 3.5
 *
 * @implements ArrayAccess<non-falsy-string, mixed>
 * @implements Iterator<non-falsy-string, mixed>
 *
 * @phpstan-type BoolDataKeys 'browser'|'hide_default'|'media_support'|'redirect_lang'|'rewrite'|'uninstall'
 * @phpstan-type ListDataKeys 'language_taxonomies'|'post_types'|'sync'|'taxonomies'
 * @phpstan-type StringDataKeys 'default_lang'|'previous_version'|'version'
 * @phpstan-type NavMenuType array<
 *     non-falsy-string,
 *     array<
 *         non-falsy-string,
 *         array<
 *             non-falsy-string,
 *             int<0, max>
 *         >
 *     >
 * >
 * @phpstan-type OptionsData array{
 *     browser: bool,
 *     default_lang: string,
 *     domains: array<non-falsy-string, non-falsy-string>,
 *     first_activation: int<0, max>,
 *     force_lang: int-mask<0, 1, 2, 3>,
 *     hide_default: bool,
 *     language_taxonomies: list<non-falsy-string>,
 *     media: array<non-falsy-string, 1>,
 *     media_support: bool,
 *     nav_menus: NavMenuType,
 *     post_types: list<non-falsy-string>,
 *     previous_version: string,
 *     redirect_lang: bool,
 *     rewrite: bool,
 *     sync: list<non-falsy-string>,
 *     taxonomies: list<non-falsy-string>,
 *     uninstall: bool,
 *     version: string
 * }
 * @phpstan-type DefaultOptionsData array{
 *     browser: false,
 *     default_lang: '',
 *     domains: array<non-falsy-string, non-falsy-string>,
 *     first_activation: 0,
 *     force_lang: 0,
 *     hide_default: false,
 *     language_taxonomies: list<non-falsy-string>,
 *     media: array<non-falsy-string, 1>,
 *     media_support: false,
 *     nav_menus: NavMenuType,
 *     post_types: list<non-falsy-string>,
 *     previous_version: '',
 *     redirect_lang: false,
 *     rewrite: false,
 *     sync: list<non-falsy-string>,
 *     taxonomies: list<non-falsy-string>,
 *     uninstall: false,
 *     version: ''
 * }
 * @phpstan-type ResetOptionsData array{
 *     browser: false,
 *     default_lang: '',
 *     domains: array<non-falsy-string, non-falsy-string>,
 *     first_activation: positive-int,
 *     force_lang: 1,
 *     hide_default: true,
 *     media: array<non-falsy-string, 1>,
 *     media_support: false,
 *     nav_menus: NavMenuType,
 *     post_types: list<non-falsy-string>,
 *     previous_version: '',
 *     redirect_lang: false,
 *     rewrite: true,
 *     sync: list<non-falsy-string>,
 *     taxonomies: list<non-falsy-string>,
 *     uninstall: false,
 *     version: non-falsy-string
 * }
 */
class PLL_Options implements ArrayAccess, Countable, Iterator, JsonSerializable {

	const OPTION_NAME = 'polylang';

	/**
	 * @var mixed[]
	 * @phpstan-var DefaultOptionsData
	 */
	const DEFAULTS = array(
		'browser'             => false,
		'default_lang'        => '',
		'domains'             => array(),
		'first_activation'    => 0,
		'force_lang'          => 0,
		'hide_default'        => false,
		'language_taxonomies' => array(),
		'media'               => array(),
		'media_support'       => false,
		'nav_menus'           => array(),
		'post_types'          => array(),
		'previous_version'    => '',
		'redirect_lang'       => false,
		'rewrite'             => false,
		'sync'                => array(),
		'taxonomies'          => array(),
		'uninstall'           => false,
		'version'             => '',
	);

	/**
	 * Polylang's options, by blog ID.
	 *
	 * @var mixed[][]
	 * @phpstan-var array<int, OptionsData>
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
	 * Option keys.
	 * Required to implement Iterator.
	 *
	 * @var string[]
	 * @phpstan-var list<key-of<self::DEFAULTS>>
	 */
	private $option_keys;

	/**
	 * Option position.
	 * Required to implement Iterator.
	 *
	 * @var int
	 * @phpstan-var int<0, max>
	 */
	private $position = 0;

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
	 * @since 3.5
	 */
	public function __construct() {
		// Keep track of the blog ID.
		$this->blog_id = (int) get_current_blog_id();

		// Handle options.
		$this->option_keys = array_keys( self::DEFAULTS );
		$this->init_options_for_blog( $this->blog_id );

		$min = defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -PHP_INT_MAX;
		add_action( 'switch_blog', array( $this, 'init_options_for_blog' ), $min );
		add_action( 'shutdown', array( $this, 'save_all' ) );
	}

	/**
	 * Initializes options for the given blog:
	 * - stores the blog ID,
	 * - stores the options.
	 * Hooked to `switch_blog`.
	 *
	 * @since 3.5
	 *
	 * @param int $blog_id The blog ID.
	 * @return void
	 */
	public function init_options_for_blog( $blog_id ) {
		$this->current_blog_id = (int) $blog_id;

		if ( isset( $this->options[ $blog_id ] ) ) {
			return;
		}

		// Store the new options.
		$this->options[ $blog_id ] = self::DEFAULTS;
		$options                   = get_option( self::OPTION_NAME );

		if ( ! is_array( $options ) || empty( $options ) ) {
			$this->modified[ $blog_id ] = true;
			return;
		}

		$this->merge( $options );
	}

	/**
	 * Stores the options into the database for all blogs.
	 * Hooked to `shutdown`.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function save_all() {
		// Find options that have been modified.
		$modified = array_filter( $this->modified );

		if ( empty( $modified ) ) {
			return;
		}

		$min = defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -PHP_INT_MAX;
		remove_action( 'switch_blog', array( $this, 'init_options_for_blog' ), $min );

		// Handle the original blog first, maybe this will prevent the use of `switch_to_blog()`.
		if ( isset( $modified[ $this->blog_id ] ) && $this->current_blog_id === $this->blog_id ) {
			$this->save();
			unset( $modified[ $this->blog_id ] );

			if ( empty( $modified ) ) {
				// All done, no need of `switch_to_blog()`.
				return;
			}
		}

		foreach ( $modified as $blog_id => $yup ) {
			switch_to_blog( $blog_id );
			$this->save();
		}

		restore_current_blog();
	}

	/**
	 * Merge new options into the current ones.
	 *
	 * @since 3.5
	 *
	 * @param array ...$arrays Lists of new options.
	 * @return self
	 */
	public function merge( array ...$arrays ) {
		$options = array_merge( ...$arrays );

		// Whitelist options.
		$options = array_intersect_key( $options, self::DEFAULTS );

		// Cast/sanitize options.
		foreach ( $options as $offset => $value ) {
			$this->sanitize_and_set( $offset, $value );
		}

		return $this;
	}

	/**
	 * Stores the options into the database.
	 *
	 * @since 3.5
	 *
	 * @return bool True if the options were updated, false otherwise.
	 */
	public function save() {
		if ( ! $this->modified[ $this->current_blog_id ] ) {
			return false;
		}

		$this->modified[ $this->current_blog_id ] = false;
		return update_option( self::OPTION_NAME, $this->options[ $this->current_blog_id ] );
	}

	/**
	 * Returns all options.
	 *
	 * @since 3.5
	 *
	 * @return mixed[]
	 *
	 * @phpstan-return OptionsData
	 */
	public function get_all() {
		return $this->options[ $this->current_blog_id ];
	}

	/**
	 * Returns reset options.
	 *
	 * @since 3.5
	 *
	 * @return array
	 *
	 * @phpstan-return ResetOptionsData
	 */
	public static function get_reset_options() {
		/** @var ResetOptionsData */
		return array_merge(
			self::DEFAULTS,
			/**
			 * Other entries that are kept in their default value:
			 * - browser:       false   The default language for the front page is not set by browser preference (was the opposite before 3.1).
			 * - media_support: false   Do not support languages and translation for media by default (was the opposite before 3.1).
			 * - redirect_lang: false   Do not redirect the language page to the homepage.
			 * - sync:          array() Synchronisation is disabled by default (was the opposite before 1.2).
			 * - uninstall:     false,  Do not remove data when uninstalling Polylang.
			 */
			array(
				'first_activation' => time(),
				'force_lang'       => 1,    // Add URL language information (was 0 before 1.7).
				'hide_default'     => true, // Remove URL language information for default language (was the opposite before 2.1.5).
				'rewrite'          => true, // Remove /language/ in permalinks (was the opposite before 0.7.2).
				'version'          => POLYLANG_VERSION,
			)
		);
	}

	/**
	 * Tells if an option exists.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.5
	 *
	 * @param string $offset The name of the option to check for.
	 * @return bool
	 *
	 * @phpstan-param key-of<self::DEFAULTS> $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->options[ $this->current_blog_id ][ $offset ] );
	}

	/**
	 * Returns the value of the specified option.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.5
	 *
	 * @param string $offset The name of the option to retrieve.
	 * @return mixed
	 *
	 * @phpstan-param key-of<self::DEFAULTS> $offset
	 * @phpstan-return (
	 *     $offset is BoolDataKeys ? bool : (
	 *         $offset is ListDataKeys ? list<non-falsy-string> : (
	 *             $offset is StringDataKeys ? string : (
	 *                 $offset is 'force_lang' ? int-mask<0, 1, 2, 3> : (
	 *                     $offset is 'first_activation' ? int<0, max> : (
	 *                         $offset is 'media' ? array<non-falsy-string, 1> : (
	 *                             $offset is 'domains' ? array<non-falsy-string, non-falsy-string> : (
	 *                                 $offset is 'nav_menus' ? NavMenuType : null
	 *                             )
	 *                         )
	 *                     )
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet( $offset ) {
		if ( ! isset( $this->options[ $this->current_blog_id ][ $offset ] ) ) {
			return null;
		}

		return $this->options[ $this->current_blog_id ][ $offset ];
	}

	/**
	 * Assigns a value to the specified option.
	 * This doesn't allow to set an unknown option.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.5
	 *
	 * @param string $offset The name of the option to assign the value to.
	 * @param mixed  $value  The value to set.
	 * @return void
	 *
	 * @phpstan-param key-of<self::DEFAULTS> $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( ! array_key_exists( $offset, self::DEFAULTS ) ) {
			return;
		}

		$this->sanitize_and_set( $offset, $value );
	}

	/**
	 * Resets an option.
	 * Also sets the property `$modified` if the value changes.
	 * This doesn't allow to unset an option, this resets it to its default value instead.
	 * Required by interface `ArrayAccess`.
	 *
	 * @since 3.5
	 *
	 * @param string $offset The name of the option to unset.
	 * @return void
	 *
	 * @phpstan-param key-of<self::DEFAULTS> $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		if ( ! array_key_exists( $offset, self::DEFAULTS ) || self::DEFAULTS[ $offset ] === $this->options[ $this->current_blog_id ][ $offset ] ) {
			return;
		}

		$this->options[ $this->current_blog_id ][ $offset ] = self::DEFAULTS[ $offset ];
		$this->modified[ $this->current_blog_id ] = true;
	}

	/**
	 * Returns the number of options.
	 * Required by interface `Countable`.
	 *
	 * @since 3.5
	 *
	 * @return int
	 */
	#[\ReturnTypeWillChange]
	public function count() {
		return count( $this->options[ $this->current_blog_id ] );
	}

	/**
	 * Returns the value of the current option.
	 * Required by interface `Iterator`.
	 *
	 * @since 3.5
	 *
	 * @return mixed
	 *
	 * @phpstan-return bool|list<non-falsy-string>|string|int-mask<0, 1, 2, 3>|int<0, max>|array<non-falsy-string, 1>|array<non-falsy-string, non-falsy-string>|NavMenuType
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return $this->options[ $this->current_blog_id ][ $this->option_keys[ $this->position ] ];
	}

	/**
	 * Returns the key of the current option.
	 * Required by interface `Iterator`.
	 *
	 * @since 3.5
	 *
	 * @return string
	 *
	 * @phpstan-return key-of<self::DEFAULTS>
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return $this->option_keys[ $this->position ];
	}

	/**
	 * Moves forward to next option.
	 * Required by interface `Iterator`.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function next() {
		++$this->position;
	}

	/**
	 * Rewinds the Iterator to the first option.
	 * Required by interface `Iterator`.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Checks if current position is valid.
	 * Required by interface `Iterator`.
	 *
	 * @since 3.5
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function valid() {
		return isset( $this->option_keys[ $this->position ] );
	}

	/**
	 * Returns the data which can be serialized by `json_encode()`.
	 *
	 * @since 3.5
	 *
	 * @return mixed[]
	 *
	 * @phpstan-return OptionsData
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->options[ $this->current_blog_id ];
	}

	/**
	 * Type-casts/Sanitizes the given option value and sets it in the property.
	 * Also sets the property `$modified` if the value changes.
	 * This doesn't do a full sanitization, only basic type-cast.
	 *
	 * @since 3.5
	 *
	 * @param string $offset The name of the option to sanitize.
	 * @param mixed  $value  The value to sanitize.
	 * @return void
	 *
	 * @phpstan-param key-of<self::DEFAULTS> $offset
	 */
	private function sanitize_and_set( $offset, $value ) {
		$prev = $this->options[ $this->current_blog_id ][ $offset ];

		switch ( $offset ) {
			// String.
			case 'default_lang':
			case 'previous_version':
			case 'version':
				if ( ! is_string( $value ) ) {
					// Invalid.
					return;
				}

				$this->options[ $this->current_blog_id ][ $offset ] = $value;
				break;

			// Bool.
			case 'browser':
			case 'hide_default':
			case 'media_support':
			case 'redirect_lang':
			case 'rewrite':
			case 'uninstall':
				$this->options[ $this->current_blog_id ][ $offset ] = ! empty( $value );
				break;

			// Int-mask<0, 1, 2, 3>.
			case 'force_lang':
				if ( ! is_numeric( $value ) || $value < 0 || $value > 3 ) {
					// Invalid.
					return;
				}

				/** @var int-mask<0, 1, 2, 3> $value */
				$this->options[ $this->current_blog_id ][ $offset ] = (int) $value;
				break;

			// Int<0, max>.
			case 'first_activation':
				if ( ! is_numeric( $value ) || $value < 0 ) {
					// Invalid.
					return;
				}

				/** @var int<0, max> $value */
				$this->options[ $this->current_blog_id ][ $offset ] = (int) $value;
				break;

			// Array with non falsy strings as array keys and values.
			case 'domains':
				if ( ! is_array( $value ) ) {
					// Invalid.
					return;
				}

				$this->options[ $this->current_blog_id ][ $offset ] = array_filter(
					$value,
					function( $v, $k ) {
						return is_string( $v ) && ! empty( $v ) && is_string( $k ) && ! empty( $k );
					},
					ARRAY_FILTER_USE_BOTH
				);
				break;

			// List with non falsy strings as array values.
			case 'language_taxonomies':
			case 'post_types':
			case 'sync':
			case 'taxonomies':
				if ( ! is_array( $value ) ) {
					// Invalid.
					return;
				}

				$this->options[ $this->current_blog_id ][ $offset ] = array_values(
					array_filter(
						$value,
						function( $v ) {
							return is_string( $v ) && ! empty( $v );
						}
					)
				);
				break;

			// Array with non falsy strings as array keys.
			case 'media':
			case 'nav_menus':
				if ( ! is_array( $value ) ) {
					// Invalid.
					return;
				}

				$this->options[ $this->current_blog_id ][ $offset ] = array_filter(
					$value,
					function ( $v, $k ) {
						return is_string( $k ) && ! empty( $k );
					},
					ARRAY_FILTER_USE_BOTH
				);
				break;
		}

		if ( $prev !== $this->options[ $this->current_blog_id ][ $offset ] ) {
			$this->modified[ $this->current_blog_id ] = true;
		}
	}
}
