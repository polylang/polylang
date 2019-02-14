<?php

class PLL_Canonical_UnitTestCase extends PLL_UnitTestCase {

	// mainly copy paste from WP_Canonical_UnitTestCase::assertCanonical
	public function assertCanonical( $test_url, $expected ) {
		if ( is_string( $expected ) ) {
			$expected = array( 'url' => $expected );
		} elseif ( is_array( $expected ) && ! isset( $expected['url'] ) && ! isset( $expected['qv'] ) ) {
			$expected = array( 'qv' => $expected );
		}

		if ( ! isset( $expected['url'] ) && ! isset( $expected['qv'] ) ) {
			$this->markTestSkipped( 'No valid expected output was provided' );
		}

		$this->go_to( home_url( $test_url ) );

		// Does the redirect match what's expected?
		$can_url = self::$polylang->filters_links->check_canonical_url( home_url( $test_url ), false ); // FIXME TODO define links ( need $curlang )
		if ( $wp_can_url = redirect_canonical( $can_url, false ) ) {
			$parsed_can_url = wp_parse_url( $wp_can_url );
		} else {
			$parsed_can_url = wp_parse_url( $can_url );
		}

		// Just test the Path and Query if present
		if ( isset( $expected['url'] ) ) {
			$this->assertEquals( $expected['url'], $parsed_can_url['path'] . ( ! empty( $parsed_can_url['query'] ) ? '?' . $parsed_can_url['query'] : '' ) );
		}

		if ( ! isset( $expected['qv'] ) ) {
			return;
		}

		// "make" that the request and check the query is correct
		$this->go_to( $can_url );

		// Are all query vars accounted for, And correct?
		global $wp;

		$query_vars = array_diff( $wp->query_vars, $wp->extra_query_vars );
		if ( ! empty( $parsed_can_url['query'] ) ) {
			parse_str( $parsed_can_url['query'], $_qv );

			// $_qv should not contain any elements which are set in $query_vars already ( ie. $_GET vars should not be present in the Rewrite )
			$this->assertEquals( array(), array_intersect( $query_vars, $_qv ) );

			$query_vars = array_merge( $query_vars, $_qv );
		}

		$this->assertEquals( $expected['qv'], $query_vars );
	}
}
