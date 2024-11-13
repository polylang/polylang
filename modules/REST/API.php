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
	 * @var V1\Languages|null
	 */
	private $languages;

	/**
	 * REST options.
	 *
	 * @var V1\Options|null
	 */
	private $options;

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
	 * Adds hooks and registers endpoints.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public function init(): void {
		$this->languages = new V1\Languages( $this->model->languages, $this->model->translatable_objects );
		$this->languages->register_routes();

		$this->options = new V1\Options( $this->model->options );
		$this->options->register_routes();
	}
}
