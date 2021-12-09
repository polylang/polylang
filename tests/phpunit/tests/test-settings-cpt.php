<?php

class Settings_CPT_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// De-activate cache for translated post types and taxonomies
		self::$model->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$model->cache->method( 'get' )->willReturn( false );

		self::$model->options['post_types'] = array();
		self::$model->options['taxonomies'] = array();

		$links_model = self::$model->get_links_model();
		$this->pll_env = new PLL_Settings( $links_model );
	}

	public function tear_down() {
		parent::tear_down();

		_unregister_post_type( 'cpt' );
		_unregister_taxonomy( 'tax' );
	}

	public function filter_translated_post_type_in_settings( $post_types, $is_settings ) {
		$post_types[] = 'cpt';
		return $post_types;
	}

	public function filter_untranslated_post_type_in_settings( $post_types, $is_settings ) {
		if ( $is_settings ) {
			$post_types[] = 'cpt';
		}
		return $post_types;
	}

	public function filter_translated_post_type_not_in_settings( $post_types, $is_settings ) {
		if ( $is_settings ) {
			$k = array_search( 'cpt', $post_types );
			unset( $post_types[ $k ] );
		} else {
			$post_types[] = 'cpt';
		}
		return $post_types;
	}

	public function filter_translated_taxonomy_in_settings( $taxonomies, $is_settings ) {
		$taxonomies[] = 'tax';
		return $taxonomies;
	}

	public function filter_untranslated_taxonomy_in_settings( $taxonomies, $is_settings ) {
		if ( $is_settings ) {
			$taxonomies[] = 'tax';
		}
		return $taxonomies;
	}

	public function filter_translated_taxonomy_not_in_settings( $taxonomies, $is_settings ) {
		if ( $is_settings ) {
			$k = array_search( 'tax', $taxonomies );
			unset( $taxonomies[ $k ] );
		} else {
			$taxonomies[] = 'tax';
		}
		return $taxonomies;
	}

	public function test_no_cpt_no_tax() {
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_untranslated_public_post_type() {
		register_post_type( 'cpt', array( 'public' => true, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_types[cpt]"]' );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_translated_public_post_type() {
		self::$model->options['post_types'] = array( 'cpt' );
		register_post_type( 'cpt', array( 'public' => true, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_types[cpt]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_programmatically_translated_public_post_type() {
		add_filter( 'pll_get_post_types', array( $this, 'filter_translated_post_type_in_settings' ), 10, 2 );
		register_post_type( 'cpt', array( 'public' => true, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_types[cpt]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEquals( 'disabled', $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_untranslated_private_post_type() {
		register_post_type( 'cpt', array( 'public' => false, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_translated_private_post_type() {
		self::$model->options['post_types'] = array( 'cpt' );
		register_post_type( 'cpt', array( 'public' => false, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_programmatically_translated_private_post_type() {
		add_filter( 'pll_get_post_types', array( $this, 'filter_translated_post_type_not_in_settings' ), 10, 2 );
		register_post_type( 'cpt', array( 'public' => false, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_untranslated_private_post_type_in_settings() {
		add_filter( 'pll_get_post_types', array( $this, 'filter_untranslated_post_type_in_settings' ), 10, 2 );
		register_post_type( 'cpt', array( 'public' => false, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_types[cpt]"]' );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_translated_private_post_type_in_settings() {
		self::$model->options['post_types'] = array( 'cpt' );
		add_filter( 'pll_get_post_types', array( $this, 'filter_untranslated_post_type_in_settings' ), 10, 2 );
		register_post_type( 'cpt', array( 'public' => false, 'label' => 'CPT' ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="post_types[cpt]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_untranslated_public_taxonomy() {
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => true ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="taxonomies[tax]"]' );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_translated_public_taxonomy() {
		self::$model->options['taxonomies'] = array( 'tax' );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => true ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="taxonomies[tax]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_programmatically_translated_public_taxonomy() {
		add_filter( 'pll_get_taxonomies', array( $this, 'filter_translated_taxonomy_in_settings' ), 10, 2 );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => true ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="taxonomies[tax]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEquals( 'disabled', $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_untranslated_private_taxonomy() {
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => false ) );
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_translated_private_taxonomy() {
		self::$model->options['taxonomies'] = array( 'tax' );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => false ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$this->assertEmpty( $module->get_form() );
	}

	public function test_programmatically_translated_private_taxonomy() {
		add_filter( 'pll_get_taxonomies', array( $this, 'filter_translated_taxonomy_not_in_settings' ), 10, 2 );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => false ) );
		$module = new PLL_Settings_CPT( $this->pll_env );
		$this->assertEmpty( $module->get_form() );
	}

	public function test_untranslated_private_taxonomy_in_settings() {
		add_filter( 'pll_get_taxonomies', array( $this, 'filter_untranslated_taxonomy_in_settings' ), 10, 2 );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => false ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="taxonomies[tax]"]' );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

	public function test_translated_private_taxonomy_in_settings() {
		self::$model->options['taxonomies'] = array( 'tax' );
		add_filter( 'pll_get_taxonomies', array( $this, 'filter_untranslated_taxonomy_in_settings' ), 10, 2 );
		register_taxonomy( 'tax', array( 'post' ), array( 'public' => false ) );
		$module = new PLL_Settings_CPT( $this->pll_env );

		$doc = new DomDocument();
		$doc->loadHTML( $module->get_form() );
		$xpath = new DOMXpath( $doc );

		$input = $xpath->query( '//input[@name="taxonomies[tax]"]' );
		$this->assertEquals( 'checked', $input->item( 0 )->getAttribute( 'checked' ) );
		$this->assertEmpty( $input->item( 0 )->getAttribute( 'disabled' ) );
	}

}
