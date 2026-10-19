// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllLanguages, resetAllSettings } from '@wpsyntex/e2e-test-utils';

/**
 * Select a predefined language in the wizard jQuery UI selectmenu.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @param {string} locale Language locale, e.g. `en_US`.
 */
async function selectWizardLanguage( page, locale ) {
	const languagesForm = page
		.locator( 'form' )
		.filter( { has: page.getByRole( 'heading', { name: 'Languages', exact: true } ) } );
	const languageSelect = languagesForm.locator( 'select' );

	await languageSelect.selectOption( locale, { force: true } );
}

/**
 * Continue past optional wizard steps until the Languages step is visible.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 */
async function goToLanguagesStep( page ) {
	const languagesHeading = page.getByRole( 'heading', { name: 'Languages', exact: true } );

	if ( ! ( await languagesHeading.isVisible() ) ) {
		await page.getByRole( 'button', { name: 'Continue' } ).click();
	}

	await expect( languagesHeading ).toBeVisible();
}

test.describe( 'Translated page after wizard languages step', { tag: [ '@pre-release' ] }, () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
		await requestUtils.deleteAllPages();
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await requestUtils.deleteAllPages();
		await deleteAllLanguages( requestUtils );
		await resetAllSettings( requestUtils );
	} );

	/**
	 * French page is reachable at /fr/{slug}/ right after adding EN + FR in one wizard request.
	 *
	 * Prerequisites:
	 * - Pretty permalinks (`/%postname%/`).
	 * - No languages configured (`force_lang` directory, `hide_default` default).
	 *
	 * Steps:
	 * - Open the setup wizard and add English then French in one languages step submit.
	 * - Leave the wizard via "Not right now".
	 * - Create a published French page with slug `fr-page`.
	 * - Visit `/fr/fr-page/`.
	 *
	 * Expected:
	 * - HTTP 200 and the page title is visible (not a WordPress 404).
	 */
	test( 'serves a French page without flushing rewrite rules', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( 'admin.php', 'page=mlang_wizard' );

		await goToLanguagesStep( page );

		await selectWizardLanguage( page, 'en_US' );
		await page.getByRole( 'button', { name: 'Add new language' } ).click();
		await expect( page.getByRole( 'row', { name: /English - en_US/ } ) ).toBeVisible();

		await selectWizardLanguage( page, 'fr_FR' );
		await page.getByRole( 'button', { name: 'Add new language' } ).click();
		await expect( page.getByRole( 'row', { name: /Français - fr_FR/ } ) ).toBeVisible();

		await page.getByRole( 'button', { name: 'Continue' } ).click();

		await page.getByRole( 'link', { name: 'Not right now' } ).click();

		await requestUtils.createPage( {
			title: 'French Page',
			content: 'Contenu de la page française.',
			status: 'publish',
			lang: 'fr',
			slug: 'fr-page',
		} );

		const response = await page.goto( '/fr/fr-page/' );

		expect( response?.status() ).toBe( 200 );
		await expect( page.getByRole( 'heading', { name: 'French Page' } ) ).toBeVisible();
	} );
} );
