// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies.
 */
import { deleteOptions, createLanguage, deleteAllLanguages } from '../../tools';


test.describe( 'Plugin Settings UI Test', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await createLanguage( requestUtils, 'en_US' );
	});

	test.afterAll( async ( { requestUtils } ) => {
		await deleteOptions( requestUtils );
		await deleteAllLanguages( requestUtils );
	});

	test.beforeEach( async ( { page } ) => {
		// Each tests are made by navigating to Polylang settings.
		await page.goto('/wp-admin/admin.php?page=mlang_settings');
	} );

	test( 'URL modification module', async ( { page } ) => {
		// Open URL modifications options panel and test each ones are accessible.
		await page.getByRole('cell', { name: 'URL modifications Settings' }).getByTitle('Configure this module').click();
		await expect( page.getByText('The language is set from content') ).toBeVisible();
		await expect( page.getByText('The language is set from the code in the URL') ).toBeVisible();
		await expect( page.getByText('The language is set from the subdomain name in pretty permalinks') ).toBeVisible();
		await expect( page.getByText('The language is set from different domains') ).toBeVisible();
		await expect( page.getByText('Hide URL language information for default language') ).toBeVisible();
	});

	test( 'Detect browser language module', async ( { page } ) => {
		// Active "Detect browser language" module then test it can be deactivated.
		await page.getByRole('cell', { name: 'Detect browser language Activate' }).getByTitle('Activate this module').click();
		await expect( page.locator('#pll-module-browser').getByText('Deactivate') ).toBeVisible();
	});

	test( 'Media module', async ( { page } ) => {
		// Active "Media translation" module then test it can be deactivated.
		await page.getByRole('cell', { name: 'Media Activate' }).getByTitle('Activate this module').click();
		await expect( page.locator('#pll-module-media').getByText('Deactivate') ).toBeVisible();
	});

	test( 'Custom post types and Taxonomies module', async ( { page } ) => {
		// Test "Custom post types and Taxonomies" module is display and can be deactivated.
		await expect( page.getByText('Custom post types and Taxonomies', { exact: true }) ).toBeVisible();
		await expect( page.locator('#pll-module-cpt').getByText('Deactivated') ).toBeVisible();
	});

	test( 'Share slugs module', async ( { page } ) => {
		// Test "Share slugs" module is unavailable and Polylang Pro purchase link is accessible.
		await expect( page.getByText('Share slugs') ).toBeVisible();
		await expect( page.locator('#pll-module-share-slugs').getByText('Deactivated') ).toBeVisible();
		await expect( page.locator('div').filter({ hasText: /^To enable this feature, you need Polylang Pro\. Upgrade now\.$/ }).first() ).toBeVisible();
	});

	test( 'Translate slugs module', async ( { page } ) => {
		// Test "Translate slugs" module is unavailable and Polylang Pro purchase link is accessible.
		await expect( page.getByText('Translate slugs') ).toBeVisible();
		await expect( page.locator('#pll-module-translate-slugs').getByText('Deactivated') ).toBeVisible();
		await expect( page.locator('div').filter({ hasText: /^To enable this feature, you need Polylang Pro\. Upgrade now\.$/ }).nth(1) ).toBeVisible();
	});

	test( 'License keys module', async ( { page } ) => {
		// Test "License" is deactivated.
		await expect( page.getByText('License keys') ).toBeVisible();
		await expect( page.locator('#pll-module-licenses').getByText('Deactivated') ).toBeVisible();
	});
});
