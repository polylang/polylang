// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies.
 */
import { deleteAllLanguages } from '../../tools';

/** @type {import('@playwright/test').Page} */
let page;

test.describe( 'Launch Polylang', () => {
	test.beforeAll(async ({ browser }) => {
		page = await browser.newPage();
		await page.goto('/wp-login.php');
		await page.getByLabel('Username or Email Address').click();
		await page.getByLabel('Username or Email Address').fill('admin');
		await page.getByLabel('Username or Email Address').press('Tab');
		await page.getByLabel('Password', { exact: true }).fill('password');
		await page.getByLabel('Password', { exact: true }).press('Tab');
		await page.getByRole('button', { name: 'Show password' }).press('Tab');
		await page.getByLabel('Remember Me').check();
		await page.getByLabel('Remember Me').press('Tab');
		await page.getByRole('button', { name: 'Log In' }).press('Enter');
	})

	test.afterAll(async ({ requestUtils }) => {
		await deleteAllLanguages( requestUtils );
		await page.close();
	});

	test('Test add language through Wizard', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=mlang_wizard');
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'English - en_US' }).click();
		await page.getByRole('button', { name: ' Add new language' }).click();
		await expect(page.getByRole('cell', { name: 'English - en_US' })).toBeVisible();
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('button', { name: 'Continue ' }).click();
		await page.getByRole('link', { name: 'Return to the Dashboard' }).click();
		await page.getByRole('link', { name: 'Languages' }).first().click();
		await page.getByRole('link', { name: 'Languages' }).first().click();
		await expect( page.getByRole('cell', { name: 'English Edit | Delete' }) ).toBeVisible();
		await page.getByRole('link', { name: 'Languages' }).first().click();
		await expect(page.locator('table.languages > tbody > tr > td').nth(3)).toContainText('Default language');
	});

	test('Test add language through settings', async ({ page }) => {
		await page.goto('/wp-admin');
		await page.getByRole('link', { name: 'Languages' }).first().click();
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'Français - fr_FR' }).click();
		await page.getByRole('button', { name: 'Add new language' }).click();
		await expect( page.getByRole('cell', { name: 'Français Edit | Delete' }) ).toBeVisible();
	});
});
