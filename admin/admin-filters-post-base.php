<?php

/**
 * Some common code for PLL_Admin_Filters_Post and PLL_Admin_Filters_Media
 *
 * @since 1.5
 */
abstract class PLL_Admin_Filters_Post_Base {
	public $links, $model, $pref_lang;

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
	 * Save translations from language metabox
	 *
	 * @since 1.5
	 *
	 * @param int   $post_id
	 * @param array $arr
	 * @return array
	 */
	protected function save_translations( $post_id, $arr ) {
		// Security check as 'wp_insert_post' can be called from outside WP admin
		check_admin_referer( 'pll_language', '_pll_nonce' );

		$translations = array();

		// Save translations after checking the translated post is in the right language
		foreach ( $arr as $lang => $tr_id ) {
			$translations[ $lang ] = ( $tr_id && $this->model->post->get_language( (int) $tr_id )->slug == $lang ) ? (int) $tr_id : 0;
		}

		$this->model->post->save_translations( $post_id, $translations );
		return $translations;
	}
}
