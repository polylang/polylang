<?php

class PLL_UnitTest_Factory_For_Post extends WP_UnitTest_Factory_For_Post {
	use Factory_For_Translated_Object_Trait;

	public function __construct( PLL_UnitTest_Factory $factory ) {
		parent::__construct( $factory );
		$this->translatable_object = $factory->pll_model->post;
	}
}
