// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	getLanguage,
	runWpCli,
} from '@wpsyntex/e2e-test-utils';

/**
 * @param {import('@playwright/test').Page} page
 */
async function expectTranslationUpdatesNotice( page ) {
	await expect(
		page.getByText( 'New language packs are available for WordPress, plugins, and themes.' )
	).toBeVisible();
	await expect( page.getByRole( 'button', { name: 'Update language packs' } ) ).toBeVisible();
}

/**
 * Dismisses all translation updates notices on the page.
 *
 * @param {import('@playwright/test').Page} page
 */
async function dismissAllTranslationUpdatesNotices( page ) {
	const dismissButtons = page.getByRole( 'button', { name: 'Dismiss this notice.' } );

	// eslint-disable-next-line no-await-in-loop
	while ( ( await dismissButtons.count() ) > 0 ) {
		const dismissButton = dismissButtons.first();

		await dismissButton.click(); // eslint-disable-line no-await-in-loop
		await dismissButton.waitFor( { state: 'detached' } ); // eslint-disable-line no-await-in-loop
	}
}

test.describe( 'Language pack updates notice on the Languages settings screen', () => {
	test.beforeAll( async () => {
		await runWpCli( 'wp transient delete settings_errors' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
	} );

	test( 'shows the notice after updating a language', async ( { requestUtils, page, admin } ) => {
		// Updating en_US does not download language packs, so no translation updates appear.
		await createLanguage( requestUtils, 'fr_FR' );
		const french = await getLanguage( requestUtils, 'fr' );
		await admin.visitAdminPage(
			'admin.php',
			`page=mlang&pll_action=edit&lang=${ french.term_id }`
		);
		await dismissAllTranslationUpdatesNotices( page );

		await page.getByRole( 'textbox', { name: 'Order' } ).fill( '1' );
		await page.getByRole( 'button', { name: 'Update', exact: true } ).click();

		await expectTranslationUpdatesNotice( page );
	} );

	test( 'shows the notice after adding a language', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'admin.php', 'page=mlang' );
		await dismissAllTranslationUpdatesNotices( page );

		await page.getByRole( 'textbox', { name: 'Full name' } ).fill( 'Français' );
		await page.getByRole( 'textbox', { name: 'Locale' } ).fill( 'fr_FR' );
		await page.getByRole( 'textbox', { name: 'Language code' } ).fill( 'fr' );
		await page.getByRole( 'radio', { name: 'left to right' } ).check();

		await page.getByRole( 'button', { name: 'Add new language' } ).click();

		await expectTranslationUpdatesNotice( page );
	} );

	test( 'show the notice on settings and translations pages', async ( {
		requestUtils,
		page,
		admin,
	} ) => {
		const hasFr = await getLanguage( requestUtils, 'fr' );
		if ( ! hasFr ) {
			await createLanguage( requestUtils, 'fr_FR' );
		}

		await admin.visitAdminPage( 'admin.php', 'page=mlang_strings' );
		await expectTranslationUpdatesNotice( page );

		await admin.visitAdminPage( 'admin.php', 'page=mlang_settings' );
		await expectTranslationUpdatesNotice( page );
	} );
} );
