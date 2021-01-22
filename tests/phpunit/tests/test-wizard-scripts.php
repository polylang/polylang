<?php

class PLL_Wizard_Scripts_Tests extends PLL_UnitTestCase {
	public function test_all_scripts_enqueued() {
		$options = array();
		$model = $this->getMockbuilder( PLL_Admin_Model::class )
			->setConstructorArgs( array( &$options ) )
			->getMock();
		$links_model = $this->getMockBuilder( PLL_Links_Default::class )
			->setConstructorArgs( array( &$model ) )
			->getMock();
		$polylang = $this->getMockBuilder( PLL_Admin::class )
			->setConstructorArgs( array( &$links_model ) )
			->getMock();

		$wizard = new PLL_Wizard( $polylang );

		// do_action( 'admin_enqueue_scripts' );
		apply_filters( 'pll_wizard_steps', array() );

		$reflection = new ReflectionProperty( PLL_Wizard::class, 'steps' );
		$reflection->setAccessible( true );
		$steps = $reflection->getValue( $wizard );
		foreach ( $steps as $step ) {
			foreach ( $step->scripts as $script ) {
				$this->assertTrue( wp_script_is( $script, 'enqueued' ) );
			}
			foreach ( $step->styles as $style ) {
				$this->assertTrue( wp_style_is( $style, 'enqueued' ) );
			}
		}
	}
}
