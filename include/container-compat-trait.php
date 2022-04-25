<?php
/**
 * @package Polylang
 */

/**
 * Trait that can be used for backward compatibility with the container.
 *
 * @since 3.3
 */
trait PLL_Container_Compat_Trait {

	/**
	 * List of class properties that are not available anymore, but are in the container instead.
	 *
	 * @since 3.3
	 *
	 * @var array<string> Property names as array keys, container identifiers as array values.
	 */
	protected $container_identifiers = array();

	/**
	 * Checks for an existing identifier in the container.
	 * Is triggered by calling `isset()` or `empty()` on inaccessible (protected or private) or non-existing properties.
	 *
	 * @since 3.3
	 *
	 * @param string $id A property name.
	 * @return bool
	 */
	public function __isset( $id ) {
		if ( empty( $this->container_identifiers[ $id ] ) ) {
			return false;
		}

		return PLL()->has( $this->container_identifiers[ $id ] ) && null !== PLL()->get( $this->container_identifiers[ $id ] );
	}

	/**
	 * Returns an existing identifier from the container.
	 * Is utilized for reading data from inaccessible (protected or private) or non-existing properties.
	 *
	 * @since 3.3
	 *
	 * @param  string $id A property name.
	 * @return mixed
	 */
	public function &__get( $id ) {
		if ( ! empty( $this->container_identifiers[ $id ] ) ) {
			// In the container.
			$trigger = ! defined( 'PLL_TRIGGER_DEPRECATED_ERROR' ) || PLL_TRIGGER_DEPRECATED_ERROR;

			/**
			 * Filters whether to trigger an error for deprecated class properties.
			 *
			 * @since 3.3
			 *
			 * @param bool   $trigger      Whether to trigger the error for deprecated class properties. Default true.
			 * @param string $class_name   Name of the class.
			 * @param string $id           Name of the property.
			 * @param string $container_id Corresponding identifier used in the container.
			 */
			if ( WP_DEBUG && apply_filters( 'pll_deprecated_property_trigger_error', $trigger, get_class( $this ), $id, $this->container_identifiers[ $id ] ) ) {
				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					sprintf(
						"Class property %s::$%s is deprecated, PLL()->get( \'%s\' ) must be used instead.\nError handler",
						esc_html( get_class( $this ) ),
						esc_html( $id ),
						esc_html( $this->container_identifiers[ $id ] )
					),
					E_USER_DEPRECATED
				);
			}

			if ( ! PLL()->has( $this->container_identifiers[ $id ] ) ) {
				PLL()->add_shared( $this->container_identifiers[ $id ], null );
			}

			return PLL()->get( $this->container_identifiers[ $id ] );
		}

		// Not in the container.
		$trace = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		if ( ! property_exists( $this, $id ) ) {
			// Undefined property.
			// Always return something, to prevent a "Only variable references should be returned by reference" notice.
			$this->{$id} = null;
			return $this->{$id};
		}

		// The property is defined.
		$ref = new ReflectionProperty( $this, $id );

		if ( $ref->isPublic() ) {
			// Why tf are we entering `__get()` if the property exists and is public?!
			return $this->{$id};
		}

		// Protected or private property.
		$visibility = $ref->isPrivate() ? 'private' : 'protected';
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			esc_html(
				sprintf(
					"Cannot access %s property %s::$%s in %s on line %d\nError handler",
					$visibility,
					get_class( $this ),
					$id,
					$trace[0]['file'],
					$trace[0]['line']
				)
			),
			E_USER_ERROR
		);
	}

	/**
	 * Adds an item to the container, or as an undeclared class property.
	 * Is run when writing data to inaccessible (protected or private) or non-existing properties.
	 *
	 * @since 3.3
	 *
	 * @param  string $id    A property name.
	 * @param  mixed  $value The value to add.
	 * @return void
	 */
	public function __set( $id, $value ) {
		if ( ! empty( $this->container_identifiers[ $id ] ) ) {
			// Back-compat: add to the container.
			PLL()->add_shared( $this->container_identifiers[ $id ], $value );
			return;
		}

		if ( ! property_exists( $this, $id ) ) {
			// Undefined property.
			$this->{$id} = $value;
			return;
		}

		// Protected or private property.
		$trace = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			esc_html(
				sprintf(
					"Cannot access non-public member %s::$%s in %s on line %d\nError handler",
					get_class( $this ),
					$id,
					$trace[0]['file'],
					$trace[0]['line']
				)
			),
			E_USER_ERROR
		);
	}

	/**
	 * Unsets an item in the container (sets its value to `null`).
	 * Is invoked when `unset()` is used on inaccessible (protected or private) or non-existing properties.
	 *
	 * @since 3.3
	 *
	 * @param  string $id A property name.
	 * @return void
	 */
	public function __unset( $id ) {
		if ( ! empty( $this->container_identifiers[ $id ] ) ) {
			PLL()->add_shared( $this->container_identifiers[ $id ], null );
			return;
		}

		if ( ! property_exists( $this, $id ) ) {
			// Undefined property.
			return;
		}

		// Protected or private property.
		$trace = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			esc_html(
				sprintf(
					"Cannot access non-public member %s::$%s in %s on line %d\nError handler",
					get_class( $this ),
					$id,
					$trace[0]['file'],
					$trace[0]['line']
				)
			),
			E_USER_ERROR
		);
	}
}
