<?php
/**
 * @package Polylang
 */

/**
 * Some common code for PLL_Admin_Filters_Post and PLL_Admin_Filters_Media
 *
 * @since 1.5
 */
abstract class PLL_Admin_Filters_Post_Base {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * @var PLL_Links
	 */
	public $links;

	/**
	 * Language selected in the admin language filter.
	 *
	 * @var PLL_Language
	 */
	public $filter_lang;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->links = &$polylang->links;
		$this->model = &$polylang->model;
		$this->pref_lang = &$polylang->pref_lang;
	}

	/**
	 * Save translations from the languages metabox.
	 *
	 * @since 1.5
	 *
	 * @param int   $post_id Post id of the post being saved.
	 * @param int[] $arr     An array with language codes as key and post id as value.
	 * @return int[] The array of translated post ids.
	 */
	protected function save_translations( $post_id, $arr ) {
		// Security check as 'wp_insert_post' can be called from outside WP admin.
		check_admin_referer( 'pll_language', '_pll_nonce' );

		$translations = array();

		// Save translations after checking the translated post is in the right language.
		foreach ( $arr as $lang => $tr_id ) {
			$translations[ $lang ] = ( $tr_id && $this->model->post->get_language( (int) $tr_id )->slug == $lang ) ? (int) $tr_id : 0;
		}

		$this->model->post->save_translations( $post_id, $translations );
		return $translations;
	}
}
