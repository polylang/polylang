import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createLanguage, deleteAllLanguages } from '@wpsyntex/e2e-test-utils';
import { execSync } from 'child_process';

/**
 * @typedef {import('@playwright/test').Page} Page
 * @typedef {import('@playwright/test').Locator} Locator
 */

/**
 * Covers navigation language switcher block.
 */
test.describe.serial(
	'Navigation Language Switcher',
	{ tag: [ '@navigation-language-switcher' ] },
	() => {
		/**
		 * @type {Object}
		 */
		let navigation;

		/**
		 * Before all tests:
		 *     - Create en_US, fr_FR and de_DE languages.
		 *     - Create the main Navigation menu in English.
		 */
		test.beforeAll( async ( { requestUtils } ) => {
			// Site Editor utilies doesn't like pretty permalinks.
			execSync(
				'npx wp-env run tests-cli wp rewrite structure "" --allow-root',
				{
					cwd: process.cwd(),
					stdio: 'inherit',
				}
			);

			await createLanguage( requestUtils, 'en_US' );
			await createLanguage( requestUtils, 'fr_FR' );
			await createLanguage( requestUtils, 'de_DE' );

			navigation = await requestUtils.createNavigationMenu( {
				title: 'My Menu',
				content: '<!-- wp:page-list /-->',
			} );
		} );

		/**
		 * Reset after all tests.
		 */
		test.afterAll( async ( { requestUtils } ) => {
			await requestUtils.deleteAllMenus();
			await deleteAllLanguages( requestUtils );
			// Set pretty permalink structure.
			execSync(
				'npx wp-env run tests-cli wp rewrite structure "/%postname%/" --allow-root',
				{
					cwd: process.cwd(),
					stdio: 'inherit',
				}
			);
		} );

		/**
		 * Ensures the navigation language switcher block is displayed.
		 *
		 * Prerequisites:
		 *     - Create en_US, fr_FR and de_DE languages.
		 *
		 * Steps:
		 *     - Visit the Navigation Editor.
		 *     - Add a new `polylang/navigation-language-switcher` block.
		 *     - Check the navigation language switcher block is displayed in the editor.
		 *     - Check the navigation language switcher block has the correct languages in a list.
		 */
		test( 'Block should be displayed in the editor', async ( {
			admin,
			page,
		} ) => {
			// Go to the Navigation Editor.
			await admin.visitSiteEditor();
			await page.getByRole( 'button', { name: 'Navigation' } ).click();
			await page.getByRole( 'button', { name: 'Actions' } ).click();
			await page.getByRole( 'menuitem', { name: 'Edit' } ).click();

			// Check the editor opens.
			await expect(
				page.getByRole( 'heading', { name: 'Navigation' } )
			).toBeVisible();

			// Add the Navigation Language Switcher block.
			await page.locator( '.editor-visual-editor' ).click();
			await page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.locator( 'html' )
				.click();
			await page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByRole( 'button', { name: 'Add block' } )
				.click();
			await page.getByText( 'Add block' ).click();
			await page
				.getByRole( 'button', { name: 'Browse all. This will open' } )
				.click();
			await page.getByRole( 'searchbox', { name: 'Search' } ).click();
			await page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'language' );
			await page
				.getByRole( 'option', { name: 'Navigation Language Switcher' } )
				.click();

			// Check the Navigation Language Switcher block is displayed in the canvas.
			await expect(
				page
					.locator( 'iframe[name="editor-canvas"]' )
					.contentFrame()
					.getByRole( 'document', {
						name: 'Block: Navigation Language',
					} )
			).toBeVisible();

			// Save the changes for next tests.
			await saveNavigationChanges( page );
		} );

		/**
		 * Ensures the navigation language switcher block settings can be edited to dropdown.
		 *
		 * Prerequisites:
		 *     - Create en_US, fr_FR and de_DE languages.
		 *     - The Navigation Language Switcher block should be already added in the editor in list mode.
		 *
		 * Steps:
		 *     - Visit the Navigation Editor with the Navigation Language Switcher block added.
		 *     - Edit the Navigation Language Switcher block settings to dropdown.
		 *     - Ensure the dropdown is displayed in the editor.
		 */
		test( 'Block in dropdown mode', async ( { page } ) => {
			await navigateToNavigationEditor( page, navigation );

			// Edit the Navigation Language Switcher block settings to dropdown and add language names and flags.
			await page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByRole( 'document', { name: 'Block: Navigation Language' } )
				.click();
			await page
				.getByRole( 'checkbox', { name: 'Displays as a dropdown' } )
				.check();
			await page
				.getByRole( 'checkbox', { name: 'Displays language names' } )
				.check();
			await page
				.getByRole( 'checkbox', { name: 'Displays flags' } )
				.check();

			const blockWithNamesAndFlags =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithNamesAndFlagsScreenshot =
				await blockWithNamesAndFlags.screenshot();
			expect( blockWithNamesAndFlagsScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-dropdown-with-names-and-flags.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Remove the language names and keep the flags.
			await page
				.getByRole( 'checkbox', { name: 'Displays language names' } )
				.uncheck();

			const blockWithFlags =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithFlagsScreenshot = await blockWithFlags.screenshot();
			expect( blockWithFlagsScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-dropdown-with-flags.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Remove the flags and ensure names are toggled on automatically.
			await page
				.getByRole( 'checkbox', { name: 'Displays flags' } )
				.uncheck();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Displays language names',
					} )
					.isChecked()
			).toBeTruthy();

			const blockWithNames =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithNamesScreenshot = await blockWithNames.screenshot();
			expect( blockWithNamesScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-dropdown-with-names.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Save the changes for next tests.
			await saveNavigationChanges( page );
		} );

		/**
		 * Ensures the navigation language switcher block settings can be edited to list.
		 *
		 * Prerequisites:
		 *     - Create en_US, fr_FR and de_DE languages.
		 *     - The Navigation Language Switcher block should be already added in the editor in dropdown mode.
		 *
		 * Steps:
		 *     - Visit the Navigation Editor with the Navigation Language Switcher block added.
		 *     - Edit the Navigation Language Switcher block settings to list.
		 *     - Ensure the list is displayed in the editor.
		 */
		test( 'Block in list mode', async ( { page } ) => {
			await navigateToNavigationEditor( page, navigation );

			// Edit the Navigation Language Switcher block settings to dropdown and add language names and flags.
			await selectNavigationLanguageSwitcherBlock( page );
			await page
				.getByRole( 'checkbox', { name: 'Displays as a dropdown' } )
				.uncheck();
			await page
				.getByRole( 'checkbox', { name: 'Displays language names' } )
				.check();
			await page
				.getByRole( 'checkbox', { name: 'Displays flags' } )
				.check();

			const blockWithNamesAndFlags =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithNamesAndFlagsScreenshot =
				await blockWithNamesAndFlags.screenshot();
			expect( blockWithNamesAndFlagsScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-list-with-names-and-flags.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Remove the language names and keep the flags.
			await page
				.getByRole( 'checkbox', { name: 'Displays language names' } )
				.uncheck();

			const blockWithFlags =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithFlagsScreenshot = await blockWithFlags.screenshot();
			expect( blockWithFlagsScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-list-with-flags.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Remove the flags and ensure names are toggled on automatically.
			await page
				.getByRole( 'checkbox', { name: 'Displays flags' } )
				.uncheck();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Displays language names',
					} )
					.isChecked()
			).toBeTruthy();

			const blockWithNames =
				await getNavigationLanguageSwitcherBlockLocator( page );
			const blockWithNamesScreenshot = await blockWithNames.screenshot();
			expect( blockWithNamesScreenshot ).toMatchSnapshot(
				'navigation-language-switcher-list-with-names.png',
				{
					maxDiffPixelRatio: 0.25,
				}
			);

			// Save the changes for next tests.
			await saveNavigationChanges( page );
		} );

		/**
		 * Ensures the navigation language switcher block advanced settings can be edited.
		 *
		 * Prerequisites:
		 *     - Create en_US, fr_FR and de_DE languages.
		 *     - The Navigation Language Switcher block should be already added in the editor in dropdown mode.
		 *
		 * Steps:
		 *     - Visit the Navigation Editor with the Navigation Language Switcher block added.
		 *     - Edit the Navigation Language Switcher block settings to forces link to front page.
		 *     - Ensure the forces link to front page setting is displayed correctly.
		 *     - Edit the Navigation Language Switcher block settings to hides the current language.
		 *     - Ensure the hides the current language setting is displayed correctly.
		 *     - Edit the Navigation Language Switcher block settings to hides languages with no translation.
		 *     - Ensure the hides languages with no translation setting is displayed correctly.
		 *     - Ensure settings (forces link to front page, hides the current language, hides languages with no translation) are dispal correctly.
		 */
		test( 'Block advanced settings can be edited', async ( { page } ) => {
			await navigateToNavigationEditor( page, navigation );

			// Edit the Navigation Language Switcher block settings to forces link to front page.
			await selectNavigationLanguageSwitcherBlock( page );
			await page
				.getByRole( 'checkbox', { name: 'Forces link to front page' } )
				.check();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Forces link to front page',
					} )
					.isChecked()
			).toBeTruthy();

			// Edit the Navigation Language Switcher block settings to hides the current language.
			await page
				.getByRole( 'checkbox', { name: 'Hides the current language' } )
				.check();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Hides the current language',
					} )
					.isChecked()
			).toBeTruthy();

			// Edit the Navigation Language Switcher block settings to hide languages with no translation.
			await page
				.getByRole( 'checkbox', {
					name: 'Hides languages with no translation',
				} )
				.check();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Hides languages with no translation',
					} )
					.isChecked()
			).toBeTruthy();
		} );
	}
);

/**
 * Selects the Navigation Language Switcher block in the editor iframe.
 *
 * @param {Page} page The page object.
 * @return {Promise<Locator>} The locator of the Navigation Language Switcher block.
 */
const selectNavigationLanguageSwitcherBlock = async ( page ) => {
	return page
		.locator( 'iframe[name="editor-canvas"]' )
		.contentFrame()
		.getByRole( 'document', { name: 'Block: Navigation Language' } )
		.click();
};

/**
 * Gets the Navigation Language Switcher block element in the editor iframe.
 *
 * @param {Page} page The page object.
 * @return {Promise<Locator>} The locator of the Navigation Language Switcher block element.
 */
const getNavigationLanguageSwitcherBlockLocator = async ( page ) => {
	return page
		.locator( 'iframe[name="editor-canvas"]' )
		.contentFrame()
		.getByRole( 'document', { name: 'Block: Navigation Language' } );
};

/**
 * Navigates to the Navigation editor page.
 *
 * @param {Page}   page       The page object.
 * @param {Object} navigation The navigation object with id property.
 */
const navigateToNavigationEditor = async ( page, navigation ) => {
	await page.goto(
		`wp-admin/site-editor.php?p=/wp_navigation/${ navigation.id }&canvas=edit`
	);
};

/**
 * Saves the navigation changes by clicking the save button.
 *
 * @param {Page} page The page object.
 */
const saveNavigationChanges = async ( page ) => {
	await page
		.getByRole( 'button', { name: /Submit for Review|Save/ } )
		.click();
};
