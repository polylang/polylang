<?php

class Polylang_Test extends PLL_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// The call to go_to triggers the parse_request action and rest_api_loaded ends with a die().
		remove_action( 'parse_request', 'rest_api_loaded' );
	}

	public function test_rest_request_plain_permalinks() {
		// A real rest_route parameter is detected as a REST request.
		$this->go_to(
			add_query_arg(
				array(
					'rest_route' => '/wp/v2/posts/1',
				),
				home_url( '/' )
			)
		);

		$this->assertTrue( Polylang::is_rest_request() );

		// An empty rest_route parameter is not considered as a REST request.
		$this->go_to(
			add_query_arg(
				array(
					'rest_route' => '',
				),
				home_url( '/' )
			)
		);

		$this->assertFalse( Polylang::is_rest_request() );

		// No rest_route parameter is not considered as a REST request.
		$this->go_to(
			add_query_arg(
				array(
					'no_route' => '',
				),
				home_url( '/' )
			)
		);

		$this->assertFalse( Polylang::is_rest_request() );

		// No query string.
		$this->go_to(
			home_url( '/' )
		);

		$this->assertFalse( Polylang::is_rest_request() );
	}

	public function test_rest_request_pretty_permalinks() {
		// A call with a REST URL.
		$this->go_to(
			home_url( '/wp-json/wp/v2/posts/1' )
		);

		$this->assertTrue( Polylang::is_rest_request() );

		// A call with a wrong REST URL.
		$this->go_to(
			home_url( '/wp-json/' )
		);

		$this->assertFalse( Polylang::is_rest_request() );
	}
}
