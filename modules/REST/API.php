<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST;

use PLL_Model;

defined( 'ABSPATH' ) || exit;

/**
 * Setup the REST API endpoints and filters.
 *
 * @since 3.7
 *
 * @phpstan-import-type Fields from V2\Languages as RestFields
 * @phpstan-template T of RestFields
 */
class API {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * REST languages.
	 *
	 * @var V2\Languages|null
	 */
	private $languages;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Model $model Polylang's model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->model = $model;
		add_action( 'rest_api_init', array( $this, 'init' ) );
	}

	/**
	 * Init filters and new endpoints.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function init(): void {
		$this->languages = new V2\Languages( $this->model );
		$this->languages->register_routes();
	}
}
