<?php

class PLL_UnitTest_Factory extends WP_UnitTest_Factory {
	use PLL_Options_Trait;

	/**
	 * @var PLL_UnitTest_Factory_For_Language
	 */
	public $language;

	/**
	 * @var PLL_Admin_Model
	 */
	public $pll_model;

	/**
	 * Generates post fixtures for use in tests with assignated languages and translations.
	 *
	 * @var PLL_UnitTest_Factory_For_Post
	 */
	public $post;

	/**
	 * Generates taxonomy term fixtures for use in tests with assignated languages and translations.
	 *
	 * @var PLL_UnitTest_Factory_For_Term
	 */
	public $term;

	/**
	 * Generates taxonomy term fixtures for use in tests with assignated languages and translations.
	 *
	 * @var PLL_UnitTest_Factory_For_Term
	 */
	public $category;

	/**
	 * Generates taxonomy term fixtures for use in tests with assignated languages and translations.
	 *
	 * @var PLL_UnitTest_Factory_For_Term
	 */
	public $tag;

	public function __construct() {
		$options = self::create_reset_options();
		$options->set( 'hide_default', false );
		$options->set( 'media_support', true );
		$this->pll_model = new PLL_Admin_Model( $options );

		$this->language = new PLL_UnitTest_Factory_For_Language( $this );

		$this->post       = new PLL_UnitTest_Factory_For_Post( $this );
		$this->attachment = new WP_UnitTest_Factory_For_Attachment( $this );
		$this->comment    = new WP_UnitTest_Factory_For_Comment( $this );
		$this->user       = new WP_UnitTest_Factory_For_User( $this );
		$this->term       = new PLL_UnitTest_Factory_For_Term( $this );
		$this->category   = new PLL_UnitTest_Factory_For_Term( $this, 'category' );
		$this->tag        = new PLL_UnitTest_Factory_For_Term( $this, 'post_tag' );
		$this->bookmark   = new WP_UnitTest_Factory_For_Bookmark( $this );
		if ( is_multisite() ) {
			$this->blog    = new WP_UnitTest_Factory_For_Blog( $this );
			$this->network = new WP_UnitTest_Factory_For_Network( $this );
		}
	}
}
