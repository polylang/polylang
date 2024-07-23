<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST;

use PLL_Model;

defined( 'ABSPATH' ) || exit;

/**
 * Sets all Polylang REST controllers up.
 *
 * @since 3.7
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
