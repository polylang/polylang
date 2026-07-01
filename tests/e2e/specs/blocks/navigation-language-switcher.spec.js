import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createLanguage, deleteAllLanguages } from '@wpsyntex/e2e-test-utils';

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
			editor,
		} ) => {
			await navigateToNavigationEditor( admin, page, navigation );

			// Navigation editor: Add page (WP 7) or Add block appender (WP 6.9) → Link UI → Browse all.
			const navigationBlock = editor.canvas.getByRole( 'document', {
				name: 'Block: Navigation',
			} );
			await navigationBlock.click();

			const navInserter = navigationBlock
				.getByRole( 'button', { name: 'Add page' } )
				.or(
					navigationBlock.getByRole( 'button', { name: 'Add block' } ) // Fallback for WP 6.9
				);
			await expect( navInserter ).toBeVisible();
			await navInserter.click();

			await page
				.getByRole( 'button', { name: 'Add block' } )
				.first()
				.click();

			// QuickInserter only lists a few blocks; Polylang's block is not among them.
			const addBlockDialog = page.getByRole( 'dialog', {
				name: 'Add block',
			} );
			await expect( addBlockDialog ).toBeVisible();

			await addBlockDialog
				.getByRole( 'button', {
					name: 'Browse all. This will open the main inserter panel in the editor toolbar.',
				} )
				.click();

			const blockLibrary = page.getByRole( 'region', {
				name: 'Block Library',
			} );
			await blockLibrary
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'language' );
			await blockLibrary
				.getByRole( 'option', { name: 'Navigation Language Switcher' } )
				.click();

			// Check the navigation language switcher block is present in the content.
			await expect
				.poll( editor.getEditedPostContent )
				.toContain( 'polylang/navigation-language-switcher' );

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
		test( 'Block in dropdown mode', async ( { admin, page } ) => {
			await navigateToNavigationEditor( admin, page, navigation );

			// Edit the Navigation Language Switcher block settings to dropdown and add language names and flags.
			await page
				.locator( 'iframe[name="editor-canvas"]' )
				.contentFrame()
				.getByRole( 'document', { name: 'Block: Navigation Language' } )
				.click();
			await setSwitcherLayout( page, 'Dropdown' );
			await setSwitcherLabels( page, 'Names' );
			await page.getByRole( 'checkbox', { name: 'Show flags' } ).check();

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
			await setSwitcherLabels( page, 'None' );

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
				.getByRole( 'checkbox', { name: 'Show flags' } )
				.uncheck();
			await expect(
				page.getByRole( 'combobox', { name: 'Labels' } )
			).toHaveValue( 'names' );

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
		test( 'Block in list mode', async ( { admin, page } ) => {
			await navigateToNavigationEditor( admin, page, navigation );

			await selectNavigationLanguageSwitcherBlock( page );
			await setSwitcherLayout( page, 'Horizontal' );
			await setSwitcherLabels( page, 'Names' );
			await page.getByRole( 'checkbox', { name: 'Show flags' } ).check();

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
			await setSwitcherLabels( page, 'None' );

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
				.getByRole( 'checkbox', { name: 'Show flags' } )
				.uncheck();
			await expect(
				page.getByRole( 'combobox', { name: 'Labels' } )
			).toHaveValue( 'names' );

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
		test( 'Block advanced settings can be edited', async ( {
			admin,
			page,
		} ) => {
			await navigateToNavigationEditor( admin, page, navigation );

			// Edit the Navigation Language Switcher block settings to forces link to front page.
			await selectNavigationLanguageSwitcherBlock( page );
			await page.getByRole( 'checkbox', { name: 'Force home' } ).check();
			expect(
				await page
					.getByRole( 'checkbox', { name: 'Force home' } )
					.isChecked()
			).toBeTruthy();

			// Edit the Navigation Language Switcher block settings to hides the current language.
			await page
				.getByRole( 'checkbox', { name: 'Hide current' } )
				.check();
			expect(
				await page
					.getByRole( 'checkbox', { name: 'Hide current' } )
					.isChecked()
			).toBeTruthy();

			// Edit the Navigation Language Switcher block settings to hide languages with no translation.
			await page
				.getByRole( 'checkbox', {
					name: 'Hide if no translation',
				} )
				.check();
			expect(
				await page
					.getByRole( 'checkbox', {
						name: 'Hide if no translation',
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
 * Navigates to the Navigation editor page and waits for the canvas to be ready.
 *
 * @param {Object} admin      The admin object.
 * @param {Page}   page       The page object.
 * @param {Object} navigation The navigation object with id and title properties.
 */
const navigateToNavigationEditor = async ( admin, page, navigation ) => {
	await admin.visitSiteEditor( {
		postId: navigation.id,
		postType: 'wp_navigation',
		canvas: 'edit',
	} );

	await expect(
		page.locator( 'iframe[name="editor-canvas"]' )
	).toBeVisible();
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

/**
 * Sets the switcher layout in the block inspector.
 *
 * @param {Page}   page   The page object.
 * @param {string} layout The layout label (e.g. 'Horizontal', 'Dropdown').
 */
const setSwitcherLayout = async ( page, layout ) => {
	await page.getByRole( 'combobox', { name: 'Layout' } ).selectOption( {
		label: layout,
	} );
};

/**
 * Sets the switcher labels option in the block inspector.
 *
 * @param {Page}   page   The page object.
 * @param {string} labels The labels option (e.g. 'Names', 'None').
 */
const setSwitcherLabels = async ( page, labels ) => {
	await page.getByRole( 'combobox', { name: 'Labels' } ).selectOption( {
		label: labels,
	} );
};
