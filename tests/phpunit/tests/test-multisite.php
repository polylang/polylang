<?php

if ( is_multisite() ) :

	class Multisite_Test extends PLL_UnitTestCase {
		public function test_new_site() {
			$site_id = $this->factory->blog->create();
			$options = get_option( 'polylang' );
			$this->assertNotFalse( $options );
		}
	}

endif;
