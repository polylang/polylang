<?php

class WP_Offload_Media_Test extends PLL_UnitTestCase {
	public function test_copy_post_metas_excludes_internal_cache() {
		$integration = new PLL_AS3CF();
		$metas       = $integration->copy_post_metas( array( 'custom_meta', 'amazonS3_cache' ) );

		$this->assertSame( array( 'custom_meta', 'amazonS3_info', 'as3cf_filesize_total' ), $metas );
	}
}
