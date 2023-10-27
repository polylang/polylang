<?php
/**
 * Polyfills `did_filter()` for backward compatibility with WP < 6.1.
 */
if ( ! function_exists( 'did_filter' ) ) {
	// Mimic apply_filters()'s counter.
	global $wp_filters;

	if ( ! isset( $wp_filters ) || ! is_array( $wp_filters ) ) {
		$wp_filters = array();
	}

	add_filter(
		'all',
		function ( $hook_name ) {
			global $wp_filters;

			if ( ! isset( $wp_filters[ $hook_name ] ) ) {
				$wp_filters[ $hook_name ] = 1;
			} else {
				++$wp_filters[ $hook_name ];
			}

			return $hook_name;
		}
	);

	/**
	 * Retrieves the number of times a filter has been applied during the current request.
	 *
	 * @since 3.4
	 *
	 * @global int[] $wp_filters Stores the number of times each filter was triggered.
	 *
	 * @param string $hook_name The name of the filter hook.
	 * @return int The number of times the filter hook has been applied.
	 */
	function did_filter( $hook_name ) {
		global $wp_filters;

		if ( ! isset( $wp_filters[ $hook_name ] ) ) {
			return 0;
		}

		return $wp_filters[ $hook_name ];
	}
}
