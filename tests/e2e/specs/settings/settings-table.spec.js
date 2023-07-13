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

	test( 'Detect browser language module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await page.getByRole('cell', { name: 'Detect browser language Activate' }).getByTitle('Activate this module').click();
		await expect( page.locator('#pll-module-browser').getByText('Deactivate') ).toBeVisible();
	});

	test( 'Media module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await page.getByRole('cell', { name: 'Media Activate' }).getByTitle('Activate this module').click();
		await expect( page.locator('#pll-module-media').getByText('Deactivate') ).toBeVisible();
	});

	test( 'Custom post types and Taxonomies module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await expect( page.getByText('Custom post types and Taxonomies', { exact: true }) ).toBeVisible();
		await expect( page.locator('#pll-module-cpt').getByText('Deactivated') ).toBeVisible();
	});

	test( 'Share slugs module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await expect( page.getByText('Share slugs') ).toBeVisible();
		await expect( page.locator('#pll-module-share-slugs').getByText('Deactivated') ).toBeVisible();
		await expect( page.locator('div').filter({ hasText: /^To enable this feature, you need Polylang Pro\. Upgrade now\.$/ }).first() ).toBeVisible();
	});

	test( 'Translate slugs module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await expect( page.getByText('Translate slugs') ).toBeVisible();
		await expect( page.locator('#pll-module-translate-slugs').getByText('Deactivated') ).toBeVisible();
		await expect( page.locator('div').filter({ hasText: /^To enable this feature, you need Polylang Pro\. Upgrade now\.$/ }).nth(1) ).toBeVisible();
	});

	test( 'License keys module', async ( { page } ) => {
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
		await expect( page.getByText('License keys') ).toBeVisible();
		await expect( page.locator('#pll-module-licenses').getByText('Deactivated') ).toBeVisible();
	});
});
