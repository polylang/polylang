// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies.
 */
import { deleteAllLanguages } from '../../tools';

test.describe( 'Launch Polylang', () => {
	test.afterAll(async ({ requestUtils }) => {
		await deleteAllLanguages( requestUtils );
	});

	test('Test add language through Wizard', async ({ page }) => {
		// Display the Wizard.
		await page.goto('/wp-admin/admin.php?page=mlang_wizard');

		// Select American English language to add.
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'English - en_US' }).click();
		await page.getByRole('button', { name: ' Add new language' }).click();
		await expect(page.getByRole('cell', { name: 'English - en_US' }), 'English should be displayed in the languages to add list. ').toBeVisible();

		// Validate steps and navigate to the dashboard.
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('link', { name: 'Return to the Dashboard' }).click();

		// Go to languages panel and check that English is in the list.
		await page.getByRole('link', { name: 'Languages' }).first().click();
		await expect( page.getByRole('cell', { name: 'English Edit | Delete' }) ).toBeVisible();
		await expect(page.locator('table.languages > tbody > tr > td').nth(3), 'English should be visible in the list table and be set to default language.').toContainText('Default language');
	});

	test('Test add language through languages panel', async ({ page }) => {
		// Navigate to the dashboard and click on "Languages" to go to the languages panel.
		await page.goto('/wp-admin');
		await page.getByRole('link', { name: 'Languages' }).first().click();

		// Select French, add it and check it is added to the list table.
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'Français - fr_FR' }).click();
		await page.getByRole('button', { name: 'Add new language' }).click();
		await expect( page.getByRole('cell', { name: 'Français Edit | Delete' }), 'French should be visible in the table.').toBeVisible();
		await expect(page.locator('table.languages > tbody > tr > td').nth(3), 'English should still be visible in the list table and be set to default language.').toContainText('Default language');
	});
});
