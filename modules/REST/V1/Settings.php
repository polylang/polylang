<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST\V1;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Syntex\Polylang\Options\Options as Options_Registry;
use WP_Syntex\Polylang\REST\Abstract_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Settings REST controller.
 *
 * @since 3.7
 */
class Settings extends Abstract_Controller {
	/**
	 * @var Options_Registry
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param Options_Registry $options Options registry.
	 */
	public function __construct( Options_Registry $options ) {
		$this->namespace = 'pll/v1';
		$this->rest_base = 'settings';
		$this->options   = $options;
	}

	/**
	 * Registers the routes for options.
	 *
	 * @since 3.7
	 *
	 * @return self
	 */
	public function register_routes(): self {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			array(
				'args'        => array(
					'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema'      => array( $this, 'get_public_item_schema' ),
				'allow_batch' => array( 'v1' => true ),
			)
		);

		return $this;
	}

	/**
	 * Retrieves all options.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item( $request ) {
		return $this->prepare_item_for_response( $this->options->get_all(), $request );
	}

	/**
	 * Updates option(s).
	 * This allows to update one or several options.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item( $request ) {
		$response = array();
		$errors   = new WP_Error();
		$schema   = $this->options->get_schema();
		$options  = array_intersect_key(
			$request->get_params(),
			$this->options->get_all(), // Remove `context`.
			rest_get_endpoint_args_for_schema( $schema, WP_REST_Server::EDITABLE ) // Remove `readonly` fields.
		);

		foreach ( $options as $option_name => $new_value ) {
			$result = $this->options->set( $option_name, $new_value );

			if ( $result->has_errors() ) {
				$errors->merge_from( $result );
			} else {
				$response[ $option_name ] = $this->options->get( $option_name );
			}
		}

		if ( $errors->has_errors() ) {
			return $this->add_status_to_error( $errors );
		}

		return $this->prepare_item_for_response( $response, $request );
	}

	/**
	 * Checks if a given request has access to get the options.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit options.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Checks if a given request has access to update the options.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the option, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Prepares the option value for the REST response.
	 *
	 * @since 3.7
	 *
	 * @param array           $item    Option values.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param array<non-falsy-string, mixed> $item
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields   = $this->get_fields_for_response( $request );
		$response = array();

		foreach ( $item as $option => $value ) {
			if ( rest_is_field_included( $option, $fields ) ) {
				$response[ $option ] = $value;
			}
		}

		/** @var WP_REST_Response */
		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves the options' schema, conforming to JSON Schema.
	 *
	 * @since 3.7
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		return $this->add_additional_fields_schema( $this->options->get_schema() );
	}
}
