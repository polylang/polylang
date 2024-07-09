<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST\V2;

use PLL_Language;
use PLL_Model;
use stdClass;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Allows to access languages via the REST API.
 *
 * @since 3.7
 *
 * @phpstan-template T of array{
 *     id: int<1, max>,
 *     locale: non-empty-string,
 *     code?: non-empty-string,
 *     name?: non-empty-string,
 *     direction?: 'ltr'|'rtl',
 *     order?: int,
 *     flag?: string,
 *     set_default_cat?: bool
 * }
 */
class Languages extends WP_REST_Controller {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Model $model Polylang's model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->namespace = 'pll/v2';
		$this->rest_base = 'languages';
		$this->model     = $model;
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
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						/* translators: %s is the name of the identifier. */
						'description' => sprintf( __( 'Unique identifier for the language (%s).', 'polylang' ), '`term_id`' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
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
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves a collection of languages.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_items( $request ) {
		$languages = array();

		foreach ( $this->model->language_model->get_languages_list() as $language ) {
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
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_exists',
				__( 'Cannot create existing language.', 'polylang' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * $request: name, code, locale, direction, order,      flag
		 * $args:    name, slug, locale, rtl,       term_group, flag? (from add_language())
		 */
		if ( ! isset( $request['name'], $request['code'], $request['direction'], $request['flag'] ) ) {
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
				'name'       => $language['name'],
				'slug'       => $language['code'],
				'rtl'        => (int) ( 'rtl' === $language['dir'] ),
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
		 *     name: string,
		 *     slug: string,
		 *     rtl: 0|1,
		 *     flag: string,
		 *     term_group: 0|1
		 * } $prepared
		 */
		$result = $this->model->language_model->add( $prepared );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/** @var PLL_Language */
		$language = $this->model->language_model->get( $prepared['slug'] );
		$response = $this->prepare_item_for_response( $language, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item( $request ) {
		$language = $this->get_language( $request['id'] );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		$response = $this->prepare_item_for_response( $language, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Updates one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item( $request ) {
		$language = $this->get_language( $request['id'] );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		$prepared = (array) $this->prepare_item_for_database( $request );

		if ( ! empty( $prepared ) ) {
			if ( ! isset( $prepared['locale'], $prepared['name'], $prepared['slug'], $prepared['rtl'], $prepared['term_group'] ) ) {
				$prepared = array_merge(
					array(
						'locale'     => $language->locale,
						'name'       => $language->name,
						'slug'       => $language->slug,
						'rtl'        => $language->is_rtl,
						'term_group' => $language->term_group,
					),
					$prepared
				);
			}
			/**
			 * @phpstan-var array{
			 *     lang_id: int,
			 *     locale: string,
			 *     name: string,
			 *     slug: string,
			 *     rtl: 0|1,
			 *     term_group: int
			 * } $prepared
			 */
			$update = $this->model->language_model->update( $prepared );

			if ( is_wp_error( $update ) ) {
				return $update;
			}
		}

		/** @var PLL_Language */
		$language = $this->model->language_model->get( $request['id'] );
		$response = $this->prepare_item_for_response( $language, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Deletes one language from the collection.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function delete_item( $request ) {
		$language = $this->get_language( $request['id'] );

		if ( is_wp_error( $language ) ) {
			return $language;
		}

		$this->model->language_model->delete( $language->term_id );

		$previous = $this->prepare_item_for_response( $language, $request );

		if ( is_wp_error( $previous ) ) {
			return $previous;
		}

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
	 * Checks if a given request has access to create a language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create languages, WP_Error object otherwise.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create a language.', 'polylang' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Checks if a given request has access to update a specific language.
	 *
	 * @since 3.7
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the language, WP_Error object otherwise.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! current_user_can( 'manage_options' ) ) {
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
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function delete_item_permissions_check( $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! current_user_can( 'manage_options' ) ) {
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
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function prepare_item_for_response( $item, $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$data     = $item->to_array();
		$schema   = $this->get_item_schema();
		$response = array();
		$props    = array(
			'term_id'         => 'id',
			'name'            => 'name',
			'slug'            => 'code',
			'locale'          => 'locale',
			'w3c'             => 'w3c',
			'facebook'        => 'facebook',
			'is_rtl'          => 'direction',
			'term_group'      => 'order',
			'flag_code'       => 'flag',
			'flag_url'        => 'flag_url',
			'flag'            => 'flag_tag',
			'custom_flag_url' => 'custom_flag_url',
			'custom_flag'     => 'custom_flag_tag',
			'is_default'      => 'is_default',
			'active'          => 'is_active',
			'home_url'        => 'home_url',
			'search_url'      => 'search_url',
			'page_on_front'   => 'page_on_front',
			'page_for_posts'  => 'page_for_posts',
			'fallbacks'       => 'fallbacks',
			'term_props'      => 'term_props',
		);

		foreach ( $props as $language_prop => $rest_item_prop ) {
			if ( ! isset( $data[ $language_prop ] ) || empty( $schema['properties'][ $rest_item_prop ] ) ) {
				continue;
			}

			switch ( $rest_item_prop ) {
				case 'direction':
					$response[ $rest_item_prop ] = $data[ $language_prop ] ? 'rtl' : 'ltr';
					break;
				default:
					$response[ $rest_item_prop ] = $data[ $language_prop ];
			}
		}

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
				'id'              => array(
					/* translators: %s is the name of the identifier. */
					'description' => sprintf( __( 'Unique identifier for the language (%s).', 'polylang' ), '`term_id`' ),
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
				'code'            => array(
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
				'direction'       => array(
					'description' => __( 'Text direction.', 'polylang' ),
					'type'        => 'string',
					'enum'        => array( 'ltr', 'rtl' ),
					'context'     => array( 'view', 'edit' ),
				),
				'order'           => array(
					'description' => __( 'Position of the language in the language switcher.', 'polylang' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'flag'            => array(
					/* translators: %s is a path to file. */
					'description' => sprintf( __( 'Flag code (for example: en). See %s.', 'polylang' ), '`settings/flags.php`' ),
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
				'flag_tag'        => array(
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
				'custom_flag_tag' => array(
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
				'is_active'       => array(
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
				'set_default_cat' => array(
					'description' => __( 'Tells whether the default category must be created when creating a new language.', 'polylang' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
					'default'     => true,
				),
			),
		);

		$taxonomies = $this->model->translatable_objects->get_taxonomy_names( array( 'language' ) );

		foreach ( $taxonomies as $taxonomy ) {
			if ( 'language' === $taxonomy ) {
				$description = __( 'Properties for posts, pages, etc in this language.', 'polylang' );
			} elseif ( 'term_language' === $taxonomy ) {
				$description = __( 'Properties for taxonomy terms in this language.', 'polylang' );
			} else {
				/**
				 * Filters the description to use for language term properties.
				 *
				 * @since 3.7
				 *
				 * @param string $description The description.
				 */
				$description = apply_filters( "pll_{$taxonomy}_properties_description", '' );
			}

			$this->schema['properties']['term_props']['properties'][ $taxonomy ] = array(
				'description' => $description,
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
	 * @phpstan-param WP_REST_Request<T> $request
	 * @phpstan-return stdClass
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared = new stdClass(); // WP_REST_Controller imposes to return an object.
		$schema   = $this->get_item_schema();
		$props    = array(
			'id'              => 'lang_id',
			'name'            => 'name',
			'code'            => 'slug',
			'locale'          => 'locale',
			'direction'       => 'rtl',
			'order'           => 'term_group',
			'flag'            => 'flag',
			'set_default_cat' => 'no_default_cat',
		);

		foreach ( $props as $rest_item_prop => $prepared_prop ) {
			if ( ! isset( $request[ $rest_item_prop ] ) || empty( $schema['properties'][ $rest_item_prop ] ) ) {
				continue;
			}

			switch ( $rest_item_prop ) {
				case 'direction':
					$prepared->$prepared_prop = (int) ( 'rtl' === $request[ $rest_item_prop ] );
					break;
				case 'set_default_cat':
					$prepared->$prepared_prop = ! $request[ $rest_item_prop ];
					break;
				default:
					$prepared->$prepared_prop = $request[ $rest_item_prop ];
			}
		}

		return $prepared;
	}

	/**
	 * Get the language, if the ID is valid.
	 *
	 * @since 3.7
	 *
	 * @param int $id Supplied ID (`term_id`).
	 * @return PLL_Language|WP_Error Language object if ID is valid, WP_Error otherwise.
	 */
	private function get_language( int $id ) {
		$error = new WP_Error(
			'rest_invalid_id',
			__( 'Invalid language ID.', 'polylang' ),
			array( 'status' => 404 )
		);

		if ( $id <= 0 ) {
			return $error;
		}

		$language = $this->model->language_model->get( $id );

		if ( ! $language instanceof PLL_Language ) {
			return $error;
		}

		return $language;
	}
}
