// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Environment Setup Test', () => {
	test( 'Should load properly', async ( { page } ) => {
		page.goto( '/wp-admin' );
		await page.waitForLoadState( 'networkidle' );
		await page.waitForLoadState( 'domcontentloaded' );

		// Admin page should load properly.
		await expect( page.locator( 'div.wrap > h1' ) ).toHaveText( 'Dashboard' );

		// Polylang admin menu should be available.
		await expect( page.locator( 'li.toplevel_page_mlang > a > div.wp-menu-name' ) ).toHaveText( 'Languages' );
	});
});
