<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST\V1;

use PLL_Language;
use PLL_Translatable_Objects;
use stdClass;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Syntex\Polylang\Model\Languages as Languages_Model;

defined( 'ABSPATH' ) || exit;

/**
 * Languages REST controller.
 *
 * @since 3.7
 */
class Languages extends WP_REST_Controller {
	/**
	 * @var Languages_Model
	 */
	private $languages;

	/**
	 * @var PLL_Translatable_Objects
	 */
	private $translatable_objects;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param Languages_Model          $languages            Languages' model.
	 * @param PLL_Translatable_Objects $translatable_objects Translatable objects registry.
	 */
	public function __construct( Languages_Model $languages, PLL_Translatable_Objects $translatable_objects ) {
		$this->namespace            = 'pll/v1';
		$this->rest_base            = 'languages';
		$this->languages            = $languages;
		$this->translatable_objects = $translatable_objects;
	}

	/**
	 * Registers the routes for languages.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema'      => array( $this, 'get_public_item_schema' ),
				'allow_batch' => array( 'v1' => true ),
			)
		);

		$readable = array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_item' ),
			'permission_callback' => array( $this, 'get_item_permissions_check' ),
			'args'                => array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			),
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<term_id>[\d]+)",
			array(
				'args'   => array(
					'term_id' => array(
						'description' => __( 'Unique identifier for the language.', 'polylang' ),
						'type'        => 'integer',
					),
				),
				$readable,
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema'      => array( $this, 'get_public_item_schema' ),
				'allow_batch' => array( 'v1' => true ),
			)
		);

		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/(?P<slug>[a-z_-]+)",
			array(
				'args'   => array(
					'slug'    => array(
						'description' => __( 'Language code - preferably 2-letters ISO 639-1 (for example: en).', 'polylang' ),
						'type'        => 'string',
					),
				),
				$readable,
				'schema'      => array( $this, 'get_public_item_schema' ),
				'allow_batch' => array( 'v1' => true ),
			)
		);
	}

	/**
	 * Retrieves all languages.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_items( $request ) {
		$languages = array();

		foreach ( $this->languages->get_list() as $language ) {
			$languages[] = $this->prepare_item_for_response( $language, $request );
		}

		return rest_ensure_response( $languages );
	}

	/**
	 * Creates one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array{
	 *     locale: non-empty-string,
	 *     slug?: non-empty-string,
	 *     name?: non-empty-string,
	 *     is_rtl?: bool,
	 *     term_group?: int,
	 *     flag_code?: non-empty-string,
	 *     no_default_cat?: bool
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['term_id'] ) ) {
			return new WP_Error(
				'rest_exists',
				__( 'Cannot create existing language.', 'polylang' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * $request: locale, slug, name, is_rtl, term_group, flag_code
		 * $args:    locale, slug, name, rtl,    term_group, flag? (from add_language())
		 */
		if ( ! isset( $request['slug'], $request['name'], $request['is_rtl'], $request['flag_code'] ) ) {
			$languages = include POLYLANG_DIR . '/settings/languages.php';

			if ( empty( $languages[ $request['locale'] ] ) ) {
				return new WP_Error(
					'rest_invalid_locale',
					__( 'The locale is invalid.', 'polylang' ),
					array( 'status' => 400 )
				);
			}

			$language = $languages[ $request['locale'] ];
			$defaults = array(
				'slug'       => $language['code'],
				'name'       => $language['name'],
				'rtl'        => 'rtl' === $language['dir'],
				'flag'       => $language['flag'],
				'term_group' => 0,
			);
		} else {
			$defaults = array(
				'term_group' => 0,
			);
		}

		$prepared = (array) $this->prepare_item_for_database( $request );
		$prepared = array_merge( $defaults, $prepared );

		/**
		 * @phpstan-var array{
		 *     locale: non-empty-string,
		 *     slug: non-empty-string,
		 *     name: non-empty-string,
		 *     rtl: bool,
		 *     term_group: int,
		 *     flag: non-empty-string,
		 *     no_default_cat?: bool
		 * } $prepared
		 */
		$result = $this->languages->add( $prepared );

		if ( is_wp_error( $result ) ) {
			return $this->add_status_to_error( $result );
		}

		/** @var PLL_Language */
		$language = $this->languages->get( $prepared['slug'] );
		return $this->prepare_item_for_response( $language, $request );
	}

	/**
	 * Retrieves one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array{
	 *     term_id: int
	 * }|array{
	 *     slug: non-empty-string
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item( $request ) {
		$language = $this->get_language( $request );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		return $this->prepare_item_for_response( $language, $request );
	}

	/**
	 * Updates one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array{
	 *     term_id: int,
	 *     slug?: non-empty-string,
	 *     locale?: non-empty-string,
	 *     name?: non-empty-string,
	 *     is_rtl?: bool,
	 *     term_group?: int,
	 *     flag_code?: non-empty-string
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item( $request ) {
		$language = $this->get_language( $request );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		$prepared = (array) $this->prepare_item_for_database( $request );

		if ( ! empty( $prepared ) ) {
			if ( ! isset( $prepared['slug'], $prepared['locale'], $prepared['name'], $prepared['rtl'], $prepared['term_group'] ) ) {
				$prepared = array_merge(
					array(
						'slug'       => $language->slug,
						'locale'     => $language->locale,
						'name'       => $language->name,
						'rtl'        => (bool) $language->is_rtl,
						'term_group' => $language->term_group,
					),
					$prepared
				);
			}
			/**
			 * @phpstan-var array{
			 *     lang_id: int,
			 *     slug: non-empty-string,
			 *     locale: non-empty-string,
			 *     name: non-empty-string,
			 *     rtl: bool,
			 *     term_group: int,
			 *     flag?: non-empty-string
			 * } $prepared
			 */
			$update = $this->languages->update( $prepared );

			if ( is_wp_error( $update ) ) {
				return $this->add_status_to_error( $update );
			}

			/** @var PLL_Language */
			$language = $this->languages->get( $language->term_id );
		}

		return $this->prepare_item_for_response( $language, $request );
	}

	/**
	 * Deletes one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array{
	 *     term_id: int
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function delete_item( $request ) {
		$language = $this->get_language( $request );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		$this->languages->delete( $language->term_id );

		$previous = $this->prepare_item_for_response( $language, $request );
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		return $response;
	}

	/**
	 * Checks if a given request has access to get the languages.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( 'edit' === $request['context'] && ! $this->check_update_permission() ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit languages.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Checks if a given request has access to create a language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create languages, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! $this->check_update_permission() ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create a language.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Checks if a given request has access to get a specific language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access for the language, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to update a specific language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the language, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! $this->check_update_permission() ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'Sorry, you are not allowed to edit this language.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Checks if a given request has access to delete a specific language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the language, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function delete_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! $this->check_update_permission() ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'Sorry, you are not allowed to delete this language.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Prepares the language for the REST response.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Language    $item    Language object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $item->to_array();
		$fields   = $this->get_fields_for_response( $request );
		$response = array();

		$data['is_rtl'] = (bool) $data['is_rtl'];
		$data['host']   = (string) $data['host'];

		foreach ( $data as $language_prop => $prop_value ) {
			if ( rest_is_field_included( $language_prop, $fields ) ) {
				$response[ $language_prop ] = $prop_value;
			}
		}

		/** @var WP_REST_Response */
		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves the language's schema, conforming to JSON Schema.
	 *
	 * @since 3.7
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'language',
			'type'       => 'object',
			'properties' => array(
				'term_id'         => array(
					'description' => __( 'Unique identifier for the language.', 'polylang' ),
					'type'        => 'integer',
					'minimum'     => 1,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'            => array(
					'description' => __( 'The name is how it is displayed on your site (for example: English).', 'polylang' ),
					'type'        => 'string',
					'minLength'   => 1,
					'context'     => array( 'view', 'edit' ),
				),
				'slug'            => array(
					'description' => __( 'Language code - preferably 2-letters ISO 639-1 (for example: en).', 'polylang' ),
					'type'        => 'string',
					'pattern'     => '[a-z_-]+',
					'context'     => array( 'view', 'edit' ),
				),
				'locale'          => array(
					'description' => __( 'WordPress Locale for the language (for example: en_US).', 'polylang' ),
					'type'        => 'string',
					'pattern'     => '[a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'w3c'             => array(
					'description' => __( 'W3C Locale for the language (for example: en-US).', 'polylang' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'facebook'        => array(
					'description' => __( 'Facebook Locale for the language (for example: en_US).', 'polylang' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_rtl'          => array(
					'description' => __( 'Text direction. True for right-to-left.', 'polylang' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'term_group'      => array(
					'description' => __( 'Position of the language in the language switcher.', 'polylang' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'flag_code'       => array(
					'description' => __( 'Flag code corresponding to ISO 3166-1 (for example: us for the United States flag).', 'polylang' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'flag_url'        => array(
					'description' => __( 'Flag URL.', 'polylang' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'flag'            => array(
					'description' => __( 'HTML tag for the flag.', 'polylang' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'custom_flag_url' => array(
					'description' => __( 'Custom flag URL.', 'polylang' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'custom_flag'     => array(
					'description' => __( 'HTML tag for the custom flag.', 'polylang' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_default'      => array(
					'description' => __( 'Tells whether the language is the default one.', 'polylang' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'active'          => array(
					'description' => __( 'Tells whether the language is active.', 'polylang' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'home_url'        => array(
					'description' => __( 'Home URL in this language.', 'polylang' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'search_url'      => array(
					'description' => __( 'Search URL in this language.', 'polylang' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'host'            => array(
					'description' => __( 'Host for this language.', 'polylang' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'page_on_front'   => array(
					'description' => __( 'Identifier of the page on front in this language.', 'polylang' ),
					'type'        => 'integer',
					'minimum'     => 0,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'page_for_posts'  => array(
					'description' => __( 'Identifier of the page for posts in this language.', 'polylang' ),
					'type'        => 'integer',
					'minimum'     => 0,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'fallbacks'       => array(
					'description' => __( 'List of language locale fallbacks.', 'polylang' ),
					'type'        => 'array',
					'uniqueItems' => true,
					'items'       => array(
						'type'    => 'string',
						'pattern' => '[a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'term_props'      => array(
					'description' => __( 'Language properties.', 'polylang' ),
					'type'        => 'object',
					'properties'  => array(),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'no_default_cat'  => array(
					'description' => __( 'Tells whether the default category must be created when creating a new language.', 'polylang' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
					'default'     => false,
				),
			),
		);

		foreach ( $this->translatable_objects as $translatable_object ) {
			$this->schema['properties']['term_props']['properties'][ $translatable_object->get_tax_language() ] = array(
				'description' => $translatable_object->get_rest_description(),
				'type'        => 'object',
				'properties'  => array(
					'term_id'          => array(
						/* translators: %s is the name of the term property. */
						'description' => sprintf( __( 'Term\'s %s of this type of content in this language.', 'polylang' ), '`term_id`' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
					'term_taxonomy_id' => array(
						/* translators: %s is the name of the term property. */
						'description' => sprintf( __( 'Term\'s %s of this type of content in this language.', 'polylang' ), '`term_taxonomy_id`' ),
						'type'        => 'integer',
						'minimum'     => 1,
					),
					'count'            => array(
						'description' => __( 'Number of items of this type of content in this language.', 'polylang' ),
						'type'        => 'integer',
						'minimum'     => 0,
					),
				),
			);
		}

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Prepares one language for create or update operation.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return object|WP_Error The prepared language, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array{
	 *     term_id?: int,
	 *     name?: non-empty-string,
	 *     slug?: non-empty-string,
	 *     locale?: non-empty-string,
	 *     flag_code?: non-empty-string,
	 *     is_rtl?: bool,
	 *     term_group?: int,
	 *     no_default_cat?: bool
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 * @phpstan-return stdClass
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared = new stdClass(); // WP_REST_Controller imposes to return an object.
		$fields   = $this->get_fields_for_response( $request );
		$props    = array(
			// From => To.
			'term_id'        => 'lang_id',
			'name'           => 'name',
			'slug'           => 'slug',
			'locale'         => 'locale',
			'flag_code'      => 'flag',
			'is_rtl'         => 'rtl',
			'term_group'     => 'term_group',
			'no_default_cat' => 'no_default_cat',
		);

		foreach ( $props as $rest_item_prop => $prepared_prop ) {
			if ( isset( $request[ $rest_item_prop ] ) && rest_is_field_included( $rest_item_prop, $fields ) ) {
				$prepared->$prepared_prop = $request[ $rest_item_prop ];
			}
		}

		return $prepared;
	}

	/**
	 * Tells if languages can be edited.
	 *
	 * @since 3.7
	 *
	 * @return bool
	 */
	protected function check_update_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Returns the language, if the ID is valid.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return PLL_Language|WP_Error Language object if the ID or slug is valid, WP_Error otherwise.
	 *
	 * @phpstan-template T of array{
	 *     term_id: int
	 * }|array{
	 *     slug: non-empty-string
	 * }
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	private function get_language( WP_REST_Request $request ) {
		if ( ! empty( $request['term_id'] ) ) {
			$error = new WP_Error(
				'rest_invalid_id',
				__( 'Invalid language ID', 'polylang' ),
				array( 'status' => 404 )
			);

			if ( $request['term_id'] <= 0 ) {
				return $error;
			}

			$language = $this->languages->get( (int) $request['term_id'] );
		} else {
			$error = new WP_Error(
				'rest_invalid_slug',
				__( 'Invalid language slug', 'polylang' ),
				array( 'status' => 404 )
			);

			$language = $this->languages->get( (string) $request['slug'] );
		}

		if ( ! $language instanceof PLL_Language ) {
			return $error;
		}

		return $language;
	}

	/**
	 * Adds a status code to the given error and returns the error.
	 *
	 * @since 3.7
	 *
	 * @param WP_Error $error       A `WP_Error` object.
	 * @param int      $status_code Optional. A status code. Default is 400.
	 * @return WP_Error
	 */
	private function add_status_to_error( WP_Error $error, int $status_code = 400 ): WP_Error {
		$error->add_data( array( 'status' => $status_code ) );
		return $error;
	}
}
