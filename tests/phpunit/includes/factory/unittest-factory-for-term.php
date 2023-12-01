<?php

class PLL_UnitTest_Factory_For_Term extends WP_UnitTest_Factory_For_Term {
	use Factory_For_Translated_Object_Trait;

	public function __construct( PLL_UnitTest_Factory $factory, $taxonomy = null ) {
		parent::__construct( $factory, $taxonomy );
		$this->translatable_object = $factory->pll_model->term;
	}
}
