<?php
/**
 * @package Polylang
 */

/**
 * Class to manage REST routes filterable by language.
 *
 * @since 3.5
 */
class PLL_Filter_REST_Routes {
	/**
	 * REST routes filterable by language ordered by entity type.
	 *
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private $filtered_entities = array();

	/**
	 * Other REST routes filterable by language.
	 *
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private $filtered_routes = array();

	/**
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @param PLL_Model $model Shared instance of the current PLL_Model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->model = $model;

		// Adds search REST endpoint.
		$this->filtered_routes['search'] = 'wp/v2/search';
	}

	/**
	 * Adds query parameters to preload paths.
	 *
	 * @since 3.5
	 *
	 * @param (string|string[])[] $preload_paths Array of paths to preload.
	 * @param array               $args Array of query strings to add paired by key/value.
	 * @return (string|string[])[]
	 */
	public function add_query_parameters( array $preload_paths, array $args ): array {
		foreach ( $preload_paths as $k => $path ) {
			if ( empty( $path ) ) {
				continue;
			}

			$query_params = array();
			// If the method request is OPTIONS, $path is an array and the first element is the path
			if ( is_array( $path ) ) {
				$temp_path = $path[0];
			} else {
				$temp_path = $path;
			}

			$path_parts = wp_parse_url( $temp_path );

			if ( ! isset( $path_parts['path'] ) || ! $this->is_filtered( $path_parts['path'] ) ) {
				continue;
			}

			if ( ! empty( $path_parts['query'] ) ) {
				parse_str( $path_parts['query'], $query_params );
			}

			// Add params in query params
			foreach ( $args as $key => $value ) {
				$query_params[ $key ] = $value;
			}

			// Sort query params to put it in the same order as the preloading middleware does
			ksort( $query_params );

			// Replace the key by the correct path with query params reordered
			$sorted_path = add_query_arg( urlencode_deep( $query_params ), $path_parts['path'] );

			if ( is_array( $path ) ) {
				$preload_paths[ $k ][0] = $sorted_path;
			} else {
				$preload_paths[ $k ] = $sorted_path;
			}
		}

		return $preload_paths;
	}

	/**
	 * Adds inline script to declare filtered REST route on client side.
	 *
	 * @since 3.5
	 *
	 * @param string $script_handle Name of the script to add the inline script to.
	 * @return void
	 */
	public function add_inline_script( string $script_handle ) {
		$script_var = 'let pllFilteredRoutes = ' . (string) wp_json_encode( $this->get() );

		wp_add_inline_script( $script_handle, $script_var, 'before' );
	}

	/**
	 * Returns filtered REST routes by entity type (e.g. post type or taxonomy).
	 *
	 * @since 3.5
	 *
	 * @return string[] REST routes.
	 * @phpstan-return array<string, string>
	 */
	private function get(): array {
		if ( ! empty( $this->filtered_entities ) ) {
			return array_merge( $this->filtered_entities, $this->filtered_routes );
		}

		$translatable_post_types  = $this->model->get_translated_post_types();
		$translatable_taxonomies  = $this->model->get_translated_taxonomies();

		$post_types = get_post_types( array( 'show_in_rest' => true ), 'objects' );
		$taxonomies = get_taxonomies( array( 'show_in_rest' => true ), 'objects' );

		$this->extract_filtered_rest_entities(
			array_merge( $post_types, $taxonomies ),
			array_merge( $translatable_post_types, $translatable_taxonomies )
		);

		return array_merge( $this->filtered_entities, $this->filtered_routes );
	}

	/**
	 * Tells if a given route is fileterable by language.
	 *
	 * @since 3.5
	 *
	 * @param string $rest_route Route to test.
	 * @return bool Whether the route is filterable or not.
	 */
	private function is_filtered( string $rest_route ): bool {
		$rest_route = trim( $rest_route );

		return ! preg_match( '/\d+$/', $rest_route ) && in_array( trim( $rest_route, '/' ), $this->get(), true );
	}

	/**
	 * Extracts filterable REST route from an array of entity objects
	 * from a list of translatable entities (e.g. post types or taxonomies).
	 *
	 * @since 3.5
	 *
	 * @param object[] $rest_entities         Array of post type or taxonomy objects.
	 * @param string[] $translatable_entities Array of translatable entity names.
	 * @return void
	 * @phpstan-param array<WP_Post_Type|WP_Taxonomy> $rest_entities
	 */
	private function extract_filtered_rest_entities( array $rest_entities, array $translatable_entities ) {
		$this->filtered_entities = array();
		foreach ( $rest_entities as $rest_entity ) {
			if ( in_array( $rest_entity->name, $translatable_entities, true ) ) {
				$rest_base      = empty( $rest_entity->rest_base ) ? $rest_entity->name : $rest_entity->rest_base;
				$rest_namespace = empty( $rest_entity->rest_namespace ) ? 'wp/v2' : $rest_entity->rest_namespace;

				$this->filtered_entities[ $rest_entity->name ] = "{$rest_namespace}/{$rest_base}";
			}
		}
	}
}
