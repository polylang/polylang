// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Plugin Settings UI Test', () => {
	test( 'URL modification module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await page.getByRole('cell', { name: 'URL modifications Settings' }).getByTitle('Configure this module').click();
		await expect( page.getByText('The language is set from content') ).toBeVisible();
		await expect( page.getByText('The language is set from the code in the URL') ).toBeVisible();
		await expect( page.getByText('The language is set from the subdomain name in pretty permalinks') ).toBeVisible();
		await expect( page.getByText('The language is set from different domains') ).toBeVisible();
		await expect( page.getByText('Hide URL language information for default language') ).toBeVisible();
	});
});
