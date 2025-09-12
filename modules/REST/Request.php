<?php
/**
 * @package Polylang Pro
 */

namespace WP_Syntex\Polylang\REST;

use Closure;
use PLL_Model;
use PLL_Language;
use WP_REST_Request;
use WP_REST_Posts_Controller;
use WP_REST_Terms_Controller;

/**
 * Class that mediates the current request.
 *
 * @since 3.8
 */
class Request {
	/**
	 * @var WP_REST_Request|null
	 *
	 * @phpstan-var WP_REST_Request<array>|null
	 */
	private $request;

	/**
	 * @var array|null
	 */
	private $handler;

	/**
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Model $model Instance of PLL_Model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->model = $model;

		/*
		 * Priorities 0 and 10000 allow to have a stored request from the very beginning until the very end of the
		 * process. This allows to filter queries (see `Filtered_Object\Post::parse_query()` for example) that may be located
		 * in callbacks hooked to `rest_request_before_callbacks` and `rest_request_after_callbacks`.
		 */
		add_filter( 'rest_request_before_callbacks', Closure::fromCallable( array( $this, 'save_request' ) ), 0, 3 );
		add_filter( 'rest_request_after_callbacks', Closure::fromCallable( array( $this, 'reset_request' ) ), 10000 );
	}

	/**
	 * Stores the request to use, for example, parameters when filtering queries.
	 *
	 * @since 3.2
	 * @since 3.8 Added the `$server` parameter.
	 *            Hooked to `rest_pre_dispatch`.
	 *            Moved from PLL_REST_Filtered_Object.
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
	 * @param array                                            $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed Response to send to the client.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	private function save_request( $response, $handler, $request ) {
		$this->request = $request;
		$this->handler = $handler;

		return $response;
	}

	/**
	 * Resets the stored request.
	 * Prevents to keep it after the request ends.
	 *
	 * @since 3.8
	 *
	 * @param mixed $result Response.
	 * @return mixed
	 */
	private function reset_request( $result ) {
		$this->request = null;
		$this->handler = null;

		return $result;
	}

	/**
	 * Returns a parameter from the current request if defined.
	 *
	 * @since 3.8
	 *
	 * @param string $param Parameter name.
	 * @return mixed|null Parameter value or null if not defined.
	 */
	public function get_param( string $param ) {
		if ( ! $this->request ) {
			return null;
		}

		return $this->request->get_param( $param );
	}

	/**
	 * Returns the language of the current request.
	 *
	 * @since 3.8
	 *
	 * @return PLL_Language|null Language of the current request, or null if no request is set or the language is not found.
	 */
	public function get_language(): ?PLL_Language {
		if ( ! $this->request ) {
			return null;
		}

		$lang = $this->get_param( 'lang' );

		if ( empty( $lang ) || ! is_string( $lang ) ) {
			return null;
		}

		$lang = $this->model->get_language( $lang );

		if ( ! $lang ) {
			return null;
		}

		return $lang;
	}

	/**
	 * Returns the ID of the current requested object if defined.
	 *
	 * @since 3.8
	 *
	 * @return int|null ID of the current requested object, or null if no request is set or the ID is not defined.
	 */
	public function get_id(): ?int {
		if ( ! $this->request ) {
			return null;
		}

		$id = $this->get_param( 'id' );
		if ( empty( $id ) || ! is_numeric( $id ) ) {
			return null;
		}

		return (int) $id;
	}

	/**
	 * Returns the attributes of the current request.
	 *
	 * @since 3.8
	 *
	 * @return array|null Attributes of the current request, or null if no request is set.
	 */
	public function get_attributes(): ?array {
		if ( ! $this->request ) {
			return null;
		}

		return $this->request->get_attributes();
	}

	/**
	 * Tells if the current request is a "read only" request, i.e. not `POST`, `PUT`, `PATCH`, `DELETE`.
	 *
	 * @since 3.8
	 *
	 * @return bool
	 */
	public function is_read_only(): bool {
		return ! empty( $this->request ) && ! in_array( $this->request->get_method(), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true );
	}

	/**
	 * Returns the object type of the current request.
	 *
	 * @since 3.8
	 *
	 * @return string|null Object type of the current request, or null if not defined.
	 *                     Returned values are 'post' and 'term'.
	 *
	 * @phpstan-return 'post'|'term'|null
	 */
	public function get_object_type(): ?string {
		if ( ! $this->request || ! $this->handler ) {
			return null;
		}

		/**
		 * Filters the object type of the current request.
		 *
		 * @since 3.8
		 *
		 * @param string|null     $type    Object type of the current request, or null if not defined.
		 * @param array           $handler Route handler used for the request.
		 * @param WP_REST_Request $request Request used to generate the response.
		 *                                 Accepted values are 'post' and 'term'.
		 */
		$type = apply_filters( 'pll_rest_request_object_type', null, $this->handler, $this->request );

		if ( in_array( $type, array( 'post', 'term' ), true ) ) {
			return $type;
		}

		if ( ! is_array( $this->handler['callback'] ) ) {
			return null;
		}

		$controller = reset( $this->handler['callback'] );
		if ( $controller instanceof WP_REST_Posts_Controller ) {
			return 'post';
		} elseif ( $controller instanceof WP_REST_Terms_Controller ) {
			return 'term';
		}

		return null;
	}
}
