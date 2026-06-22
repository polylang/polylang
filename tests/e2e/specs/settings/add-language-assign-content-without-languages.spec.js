// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllLanguages } from '@wpsyntex/e2e-test-utils';

test.describe( 'create language and test the bulk assignment of content without languages', () => {
	/**
	 * Before all tests:
	 * - Create post without languages
	 */
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.createPost( {
			title: 'Test Post',
			content: 'This is a test post without languages.',
		} );
	} );

	/**
	 * Reset after all tests.
	 */
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await deleteAllLanguages( requestUtils );
	} );

	/**
	 * Create English en_US as the default language
	 *
	 * Steps:
	 * - visit language setting page
	 * - click on language select
	 * - select English (en_US)
	 * - save
	 *
	 * Behaviour expected
	 * - As this is the 1st language, it should be set as the default language
	 */
	test( 'create English en_US as the default language', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'admin.php', 'page=mlang' );

		await page
			.getByRole( 'textbox', { name: 'Full name' } )
			.fill( 'English' );
		await page.getByRole( 'textbox', { name: 'Locale' } ).fill( 'en_US' );
		await page
			.getByRole( 'textbox', { name: 'Language code' } )
			.fill( 'en' );
		await page.getByRole( 'radio', { name: 'left to right' } ).check();
		//		await page.getByLabel( 'Flag' ).selectOption( 'us' );

		// Submit the form to add the new language.
		await page.getByRole( 'button', { name: 'Add new language' } ).click();

		// Search for the English row in the languages list table by its row role
		// then check the visually-hidden span that contains the text "Default language".
		// We avoid using page.locator() and instead use getByRole/getByText chaining.
		//	const englishRow = page.locator( '#the-list tr', { hasText: 'English' } ).first();
		const englishRow = page.getByRole( 'row', { name: /English/ } ).first();

		// Target the <span class="screen-reader-text">Default language</span>
		await expect(
			englishRow.getByText( 'Default language', { exact: true } )
		).toBeVisible();
	} );
} );
