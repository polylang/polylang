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
	 * Checks for an existing identifier in the container.
	 *
	 * @since 3.3
	 *
	 * @param string $id A property name or a container identifier.
	 * @return bool
	 */
	public function __isset( $id ) {
		return ( empty( $this->container_identifiers ) || in_array( $id, $this->container_identifiers ) ) && $this->container->has( $id );
	}

	/**
	 * Returns an existing identifier from the container.
	 *
	 * @since 3.3
	 *
	 * @param  string $id A property name or a container identifier.
	 * @return mixed
	 */
	public function &__get( $id ) {
		if ( ( empty( $this->container_identifiers ) || in_array( $id, $this->container_identifiers ) ) && $this->container->has( $id ) ) {
			/**
			 * Filters whether to trigger an error for deprecated class property.
			 *
			 * @since 3.3
			 *
			 * @param bool   $trigger    Whether to trigger the error for deprecated class properties. Default true.
			 * @param string $class_name Name of the class.
			 * @param string $id         Name of the property.
			 */
			if ( WP_DEBUG && apply_filters( 'pll_deprecated_property_trigger_error', true, get_class( $this ), $id ) ) {
				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					sprintf(
						'Class property %1$s->%2$s is <strong>deprecated</strong>, %1$s->get( \'%2$s\' ) must be used instead.',
						esc_html( get_class( $this ) ),
						esc_html( $id )
					),
					E_USER_DEPRECATED
				);
			}

			$value = $this->container->get( $id );
			return $value;
		}

		$trace = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			sprintf(
				'Undefined property: %s::$%s in %s on line %d',
				esc_html( get_class( $this ) ),
				esc_html( $id ),
				esc_html( $trace[0]['file'] ),
				esc_html( $trace[0]['line'] )
			),
			E_USER_NOTICE
		);
	}

	/**
	 * Adds an item to the container.
	 *
	 * @since 3.3
	 *
	 * @param  string $id    A property name or a container identifier.
	 * @param  mixed  $value The value to add.
	 * @return void
	 */
	public function __set( $id, $value ) {
		$this->container->addShared( $id, $value );
	}
}

