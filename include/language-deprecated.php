<?php
/**
 * @package Polylang
 */

/**
 * Holds everything related to deprecated properties of `PLL_Language`.
 *
 * @since 3.4
 */
abstract class PLL_Language_Deprecated {

	/**
	 * List of deprecated term properties and related arguments to use with `get_tax_prop()`.
	 *
	 * @private
	 *
	 * @var string[][]
	 */
	const DEPRECATED_TERM_PROPERTIES = array(
		'term_taxonomy_id'    => array( 'language', 'term_taxonomy_id' ),
		'count'               => array( 'language', 'count' ),
		'tl_term_id'          => array( 'term_language', 'term_id' ),
		'tl_term_taxonomy_id' => array( 'term_language', 'term_taxonomy_id' ),
		'tl_count'            => array( 'term_language', 'count' ),
	);

	/**
	 * List of deprecated URL properties and related getter to use.
	 *
	 * @private
	 *
	 * @var string[]
	 */
	const DEPRECATED_URL_PROPERTIES = array(
		'home_url'   => 'get_home_url',
		'search_url' => 'get_search_url',
	);

	/**
	 * Returns a language term property value (term ID, term taxonomy ID, or count).
	 *
	 * @since 3.4
	 *
	 * @param string $taxonomy_name Name of the taxonomy.
	 * @param string $prop_name     Name of the property: 'term_taxonomy_id', 'term_id', 'count'.
	 * @return int
	 *
	 * @phpstan-param non-empty-string $taxonomy_name
	 * @phpstan-param 'term_taxonomy_id'|'term_id'|'count' $prop_name
	 * @phpstan-return int<0, max>
	 */
	abstract public function get_tax_prop( $taxonomy_name, $prop_name );

	/**
	 * Returns language's home URL. Takes care to render it dynamically if no cache is allowed.
	 *
	 * @since 3.4
	 *
	 * @return string Language home URL.
	 *
	 * @phpstan-return non-empty-string
	 */
	abstract public function get_home_url();

	/**
	 * Returns language's search URL. Takes care to render it dynamically if no cache is allowed.
	 *
	 * @since 3.4
	 *
	 * @return string Language search URL.
	 *
	 * @phpstan-return non-empty-string
	 */
	abstract public function get_search_url();

	/**
	 * Throws a depreciation notice if someone tries to get one of the following properties:
	 * `term_taxonomy_id`, `count`, `tl_term_id`, `tl_term_taxonomy_id` or `tl_count`.
	 *
	 * Backward compatibility with Polylang < 3.4.
	 *
	 * @since 3.4
	 *
	 * @param string $property Property to get.
	 * @return mixed Required property value.
	 */
	public function __get( $property ) {
		// Deprecated property.
		if ( $this->is_deprecated_term_property( $property ) ) {
			$this->deprecated_property(
				$property,
				sprintf(
					"get_tax_prop( '%s', '%s' )",
					self::DEPRECATED_TERM_PROPERTIES[ $property ][0],
					self::DEPRECATED_TERM_PROPERTIES[ $property ][1]
				)
			);

			return $this->get_deprecated_term_property( $property );
		}

		if ( $this->is_deprecated_url_property( $property ) ) {
			$this->deprecated_property( $property, "get_{$property}()" );

			return $this->get_deprecated_url_property( $property );
		}

		// Undefined property.
		if ( ! property_exists( $this, $property ) ) {
			return null;
		}

		// The property is defined.
		$ref = new ReflectionProperty( $this, $property );

		// Public property.
		if ( $ref->isPublic() ) {
			return $this->{$property};
		}

		// Protected or private property.
		$visibility = $ref->isPrivate() ? 'private' : 'protected';
		$trace      = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$file       = isset( $trace[0]['file'] ) ? $trace[0]['file'] : '';
		$line       = isset( $trace[0]['line'] ) ? $trace[0]['line'] : 0;
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			esc_html(
				sprintf(
					"Cannot access %s property %s::$%s in %s on line %d.\nError handler",
					$visibility,
					get_class( $this ),
					$property,
					$file,
					$line
				)
			),
			E_USER_ERROR
		);
	}

	/**
	 * Checks for a deprecated property.
	 * Is triggered by calling `isset()` or `empty()` on inaccessible (protected or private) or non-existing properties.
	 *
	 * Backward compatibility with Polylang < 3.4.
	 *
	 * @since 3.4
	 *
	 * @param string $property A property name.
	 * @return bool
	 */
	public function __isset( $property ) {
		return $this->is_deprecated_term_property( $property ) || $this->is_deprecated_url_property( $property );
	}

	/**
	 * Tells if the given term property is deprecated.
	 *
	 * @since 3.4
	 * @see PLL_Language::DEPRECATED_TERM_PROPERTIES for the list of deprecated properties.
	 *
	 * @param string $property A property name.
	 * @return bool
	 *
	 * @phpstan-assert-if-true key-of<PLL_Language::DEPRECATED_TERM_PROPERTIES> $property
	 */
	protected function is_deprecated_term_property( $property ) {
		return array_key_exists( $property, self::DEPRECATED_TERM_PROPERTIES );
	}

	/**
	 * Returns a deprecated term property's value.
	 *
	 * @since 3.4
	 * @see PLL_Language::DEPRECATED_TERM_PROPERTIES for the list of deprecated properties.
	 *
	 * @param string $property A property name.
	 * @return int
	 *
	 * @phpstan-param key-of<PLL_Language::DEPRECATED_TERM_PROPERTIES> $property
	 * @phpstan-return int<0, max>
	 */
	protected function get_deprecated_term_property( $property ) {
		return $this->get_tax_prop(
			self::DEPRECATED_TERM_PROPERTIES[ $property ][0],
			self::DEPRECATED_TERM_PROPERTIES[ $property ][1]
		);
	}

	/**
	 * Tells if the given URL property is deprecated.
	 *
	 * @since 3.4
	 * @see PLL_Language::DEPRECATED_URL_PROPERTIES for the list of deprecated properties.
	 *
	 * @param string $property A property name.
	 * @return bool
	 *
	 * @phpstan-assert-if-true key-of<PLL_Language::DEPRECATED_URL_PROPERTIES> $property
	 */
	protected function is_deprecated_url_property( $property ) {
		return array_key_exists( $property, self::DEPRECATED_URL_PROPERTIES );
	}

	/**
	 * Returns a deprecated URL property's value.
	 *
	 * @since 3.4
	 * @see PLL_Language::DEPRECATED_URL_PROPERTIES for the list of deprecated properties.
	 *
	 * @param string $property A property name.
	 * @return string
	 *
	 * @phpstan-param key-of<PLL_Language::DEPRECATED_URL_PROPERTIES> $property
	 * @phpstan-return non-empty-string
	 */
	protected function get_deprecated_url_property( $property ) {
		return $this->{self::DEPRECATED_URL_PROPERTIES[ $property ]}();
	}

	/**
	 * Triggers a deprecated an error for a deprecated property.
	 *
	 * @since 3.4
	 *
	 * @param string $property    Deprecated property name.
	 * @param string $replacement Method or property name to use instead.
	 * @return void
	 */
	private function deprecated_property( $property, $replacement ) {
		/**
		 * Filters whether to trigger an error for deprecated properties.
		 *
		 * The filter name is intentionally not prefixed to use the same as WordPress
		 * in case it is added in the future.
		 *
		 * @since 3.4
		 *
		 * @param bool $trigger Whether to trigger the error for deprecated properties. Default true.
		 */
		if ( ! WP_DEBUG || ! apply_filters( 'deprecated_property_trigger_error', true ) ) {
			return;
		}

		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			sprintf(
				"Class property %1\$s::\$%2\$s is deprecated, use %1\$s::%3\$s instead.\nError handler",
				esc_html( get_class( $this ) ),
				esc_html( $property ),
				esc_html( $replacement )
			),
			E_USER_DEPRECATED
		);
	}
}
