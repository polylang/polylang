// @ts-check
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	deleteAllTerms,
} from '@wpsyntex/e2e-test-utils';

/**
 * Tests Quick Edit language selection for terms.
 */
test.describe( 'Quick Edit language selection for terms', () => {
	/**
	 * Before all tests: Create English and French languages.
	 */
	test.beforeAll( async ( { requestUtils } ) => {
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'fr_FR' );
	} );

	/**
	 * Clean up after all tests.
	 */
	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllTerms( requestUtils, 'categories' );
		await deleteAllLanguages( requestUtils );
	} );

	/**
	 * Ensures quick edit displays the correct language for a term without translations.
	 *
	 * Prerequisites:
	 *     - English (en) and French (fr) languages exist.
	 *
	 * Steps:
	 *     1. Create a French category via UI.
	 *     2. Click "Quick Edit" on the French category.
	 *     3. Verify the language dropdown shows "Français" (not "English").
	 */
	test( 'Should display correct language when quick editing a term without translations', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		// Navigate to Categories admin page.
		await admin.visitAdminPage(
			'edit-tags.php',
			'taxonomy=category&post_type=post'
		);

		// Create a French category via UI.
		const categoryName = 'Test Catégorie FR';

		await page.locator( '#tag-name' ).fill( categoryName );
		await page
			.locator( '#term_lang_choice' )
			.selectOption( { label: 'Français' } );
		await page.locator( '#submit' ).click();

		// Wait for AJAX success message.
		await page
			.locator( '#ajax-response .notice-success' )
			.waitFor( { state: 'visible' } );

		// Get the term ID from the API (after it was created via UI).
		const terms = await requestUtils.rest( {
			method: 'GET',
			path: '/wp/v2/categories',
			params: { search: categoryName, per_page: 1 },
		} );
		const termId = terms[ 0 ].id;

		// Find the row by precise ID.
		const categoryRow = page.locator( `#tag-${ termId }` );
		await categoryRow.hover();
		await categoryRow.locator( 'button.editinline' ).click();

		// Wait for the specific inline edit form.
		await page
			.locator( `#edit-${ termId }` )
			.waitFor( { state: 'visible' } );

		// Get the selected language value.
		const selectedLanguage = await page
			.locator( `#edit-${ termId } select[name="inline_lang_choice"]` )
			.inputValue();

		// Language should be 'fr' (French), not 'en' (English).
		expect( selectedLanguage ).toBe( 'fr' );

		// Verify the displayed text.
		const selectedOption = await page
			.locator(
				`#edit-${ termId } select[name="inline_lang_choice"] option:checked`
			)
			.textContent();

		expect( selectedOption ).toBe( 'Français' );
	} );
} );
