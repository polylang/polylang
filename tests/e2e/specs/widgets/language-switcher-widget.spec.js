// @ts-check
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	resetAllSettings,
} from '@wpsyntex/e2e-test-utils';

/**
 * Covers language switcher block in the widget editor.
 */
test.describe(
	'Language Switcher block in Widget Editor',
	{ tag: [ '@widget-editor' ] },
	() => {
		let initialTheme;

		/**
		 * Before all tests:
		 *     - Activate a theme with widget areas.
		 *     - Create en_US and fr_FR languages.
		 */
		test.beforeAll( async ( { requestUtils } ) => {
			const [ activeTheme ] = await requestUtils.rest( {
				path: '/wp/v2/themes',
				params: { status: 'active' },
			} );
			initialTheme = activeTheme.stylesheet;

			// Activate a theme with widget areas.
			await requestUtils.activateTheme( 'twentytwentyone' );

			await createLanguage( requestUtils, 'en_US' );
			await createLanguage( requestUtils, 'fr_FR' );
		} );

		/**
		 * Reset after all tests.
		 */
		test.afterAll( async ( { requestUtils } ) => {
			await requestUtils.activateTheme( initialTheme );
			await deleteAllLanguages( requestUtils );
			await resetAllSettings( requestUtils );
		} );

		/**
		 * Ensures the language switcher block can be added in the widget editor without crashing and displays the languages.
		 *
		 * Prerequisites:
		 *     - en_US and fr_FR languages exist.
		 *     - A classic theme with widget areas is active (Twenty Twenty-One).
		 *
		 * Steps:
		 *     - Go to "Appearance" > "Widgets".
		 *     - Dismiss the welcome guide if present.
		 *     - Add a Language Switcher block.
		 *     - Verify the block is displayed without the "block has encountered an error" message.
		 *     - Verify the block preview lists English and French.
		 */
		test( 'Block can be added in a widget area and displays languages', async ( {
			admin,
			page,
		} ) => {
			// Navigate to widget editor.
			await admin.visitAdminPage( 'widgets.php' );

			// Wait for the block editor to load.
			await page.waitForSelector( '.edit-widgets-header' );

			// Close the welcome guide if it appears.
			const closeButton = page.locator(
				'.edit-widgets-welcome-guide button[aria-label="Close"]'
			);
			if ( ( await closeButton.count() ) > 0 ) {
				await closeButton.click();
				await page.waitForTimeout( 300 );
			}

			// Open Block Inserter.
			await page
				.getByRole( 'button', { name: 'Block Inserter' } )
				.click();

			// Search for the Language Switcher block.
			await page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'Language Switcher' );
			await page
				.getByRole( 'option', {
					name: 'Language Switcher',
					exact: true,
				} )
				.click();

			// The block error message must not appear.
			await expect(
				page.getByText(
					'This block has encountered an error and cannot be previewed.'
				)
			).not.toBeVisible();

			// The block must be visible and display both languages.
			const block = page.getByRole( 'document', {
				name: 'Block: Language Switcher',
			} );
			await expect( block ).toBeVisible();
			await expect( block.getByText( 'English' ) ).toBeVisible();
			await expect( block.getByText( 'Français' ) ).toBeVisible();
		} );
	}
);
