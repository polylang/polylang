<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities\Create;

use PLL_Model;
use PLL_Language;
use WP_Syntex\Polylang\REST\Request;
use WP_Syntex\Polylang\Capabilities\User\User_Interface;

/**
 * Class to manage the language context for posts creation or update.
 *
 * @since 3.8
 */
abstract class Abstract_Object {
	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * @var PLL_Language|null
	 */
	protected $pref_lang;

	/**
	 * @var PLL_Language|null
	 */
	protected $curlang;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param PLL_Model         $model     The model instance.
	 * @param Request           $request   The request instance.
	 * @param PLL_Language|null $pref_lang The preferred language.
	 * @param PLL_Language|null $curlang   The current language.
	 */
	public function __construct( PLL_Model $model, Request $request, ?PLL_Language $pref_lang, ?PLL_Language $curlang ) {
		$this->model     = $model;
		$this->request   = $request;
		$this->pref_lang = $pref_lang;
		$this->curlang   = $curlang;
	}

	/**
	 * Returns the language to set for an object creation or update based on the global context.
	 *
	 * @since 3.8
	 *
	 * @param User_Interface $user The user object.
	 * @param int            $id   The object ID.
	 * @return PLL_Language The language defined from the global context.
	 */
	abstract public function get_language( User_Interface $user, int $id = 0 ): PLL_Language;
}
