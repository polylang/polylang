// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	setSetting,
	resetAllSettings,
} from '@wpsyntex/e2e-test-utils';

/**
 * Covers strings translations in admin and on the frontend.
 *
 * Serial execution is required: frontend assertions depend on translations saved in admin tests
 * and on a shared WordPress database state.
 */
test.describe.serial( 'Strings translations', () => {
	/** @type {string} */
	let frenchPostUrl;

	test.beforeAll( async ( { requestUtils } ) => {
		await setSetting( requestUtils, 'force_lang', 1 );
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'fr_FR' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
		await resetAllSettings( requestUtils );
	} );

	test.describe( 'Admin', () => {
		test.describe( 'Core strings', () => {
			/**
			 * Ensures the blogname string is translatable in "Translations" screen.
			 *
			 * Prerequisites:
			 *     - English (en_US) and French (fr_FR) languages exist.
			 *
			 * Steps:
			 *     - Go to the "Translations" screen filtered to the WooCommerce group.
			 *     - Check that the blogname string is visible.
			 *     - Focus the French translation field for that row.
			 *     - Fill in the input with the "polylang FR" value.
			 *     - Click the "Save Changes" button.
			 *     - Check that the "polylang FR" string is visible in the "Translations" screen.
			 */
			test( 'Blogname core string should be translatable', async ( {
				page,
			} ) => {
				await page.goto(
					'wp-admin/admin.php?page=mlang_strings&paged=1'
				);
				await expect(
					page.getByRole( 'cell', { name: 'blogname' } )
				).toBeVisible();

				const blognameRow = page
					.getByRole( 'row', { name: 'Select polylang polylang' } )
					.getByLabel( 'Français' );

				await blognameRow.click();
				await blognameRow.fill( 'polylang FR' );

				await page
					.getByRole( 'button', { name: 'Save Changes' } )
					.click();

				await page
					.getByRole( 'button', { name: 'Save Changes' } )
					.click();
				await expect(
					page.getByRole( 'cell', {
						name: 'polylang FR',
					} )
				).toBeVisible();
			} );
		} );

		test.describe( 'Custom strings plugin', () => {
			test.beforeAll( async ( { requestUtils } ) => {
				await requestUtils.activatePlugin( 'custom-strings-e2e' );
			} );

			test.afterAll( async ( { requestUtils } ) => {
				await requestUtils.deactivatePlugin( 'custom-strings-e2e' );
			} );

			/**
			 * Ensures the registered string appears on the Translations screen (filtered by group).
			 *
			 * Prerequisites:
			 *     - English (en_US) and French (fr_FR) languages exist.
			 *     - The Custom Strings E2E test plugin is active (registers `Hello Polylang E2E` on admin load).
			 *
			 * Steps:
			 *     - Go to the "Translations" screen filtered to the "Polylang E2E" group.
			 *     - Check that the string and name cells show the expected values.
			 *     - Fill the French translation and save (read on the frontend below).
			 */
			test( 'Custom string is listed in admin and can be translated', async ( {
				admin,
			} ) => {
				await admin.visitAdminPage(
					'admin.php',
					`page=mlang_strings&group=${ encodeURIComponent(
						'Polylang E2E'
					) }`
				);

				await expect(
					admin.page.getByRole( 'cell', {
						name: 'Hello Polylang E2E',
						exact: true,
					} )
				).toBeVisible();
				await expect(
					admin.page.getByRole( 'cell', {
						name: 'e2e_custom_greeting',
						exact: true,
					} )
				).toBeVisible();

				const stringRow = admin.page
					.getByRole( 'row' )
					.filter( { hasText: 'Hello Polylang E2E' } );

				await stringRow
					.getByLabel( 'Français' )
					.fill( 'Bonjour Polylang E2E FR' );
				await admin.page
					.getByRole( 'button', { name: 'Save Changes' } )
					.click();

				await expect( stringRow.getByLabel( 'Français' ) ).toHaveValue(
					'Bonjour Polylang E2E FR'
				);
			} );

			/**
			 * Ensures the multiline string uses a textarea in admin and can be translated.
			 *
			 * Prerequisites:
			 *     - English (en_US) and French (fr_FR) languages exist.
			 *     - The Custom Strings E2E test plugin is active (registers a multiline string).
			 *
			 * Steps:
			 *     - Go to the "Translations" screen filtered to the "Polylang E2E" group.
			 *     - Find the multiline row and check the French field is a textarea.
			 *     - Fill the French translation and save (read on the frontend below).
			 */
			test( 'Multiline custom string is listed in admin and can be translated', async ( {
				admin,
			} ) => {
				await admin.visitAdminPage(
					'admin.php',
					`page=mlang_strings&group=${ encodeURIComponent(
						'Polylang E2E'
					) }`
				);

				const multilineRow = admin.page
					.getByRole( 'row' )
					.filter( { hasText: 'Line one' } )
					.filter( { hasText: 'Line two' } );

				await expect(
					multilineRow.getByRole( 'cell', {
						name: 'e2e_custom_multiline',
					} )
				).toBeVisible();

				const frenchField = multilineRow.getByLabel( 'Français' );

				await expect( frenchField ).toHaveAttribute(
					'name',
					/translation\[fr\]/
				);

				await frenchField.fill( 'Ligne un\nLigne deux' );
				await admin.page
					.getByRole( 'button', { name: 'Save Changes' } )
					.click();

				const frenchFieldAfterSave = admin.page
					.getByRole( 'row' )
					.filter( { hasText: 'Line one' } )
					.filter( { hasText: 'Line two' } )
					.getByLabel( 'Français' );

				await expect( frenchFieldAfterSave ).toHaveValue(
					'Ligne un\nLigne deux'
				);
			} );
		} );
	} );

	test.describe( 'Frontend', () => {
		test.beforeAll( async ( { requestUtils } ) => {
			await requestUtils.activatePlugin( 'custom-strings-e2e' );

			const publishedFrenchPost = await requestUtils.createPost( {
				title: 'PLL E2E strings post',
				content: '<p>PLL E2E post body</p>',
				status: 'publish',
				lang: 'fr',
			} );

			frenchPostUrl = publishedFrenchPost.link;
		} );

		test.afterAll( async ( { requestUtils } ) => {
			await requestUtils.deactivatePlugin( 'custom-strings-e2e' );
		} );

		test.describe( 'Core strings', () => {
			/**
			 * Ensures the French translation for `blogname` is used on the French front (document title).
			 *
			 * Prerequisites:
			 *     - The admin test above saved the French blogname as "polylang FR".
			 *     - English and French languages exist.
			 *
			 * Steps:
			 *     - Open the French post URL created in `beforeAll`.
			 *     - Check that the document title includes the French site title (WordPress appends site name on singular views).
			 *     - Check that the header site title block shows "polylang FR" (block themes: `header` + `.wp-block-site-title`).
			 */
			test( 'Blogname French translation appears on the frontend', async ( {
				page,
			} ) => {
				const response = await page.goto( frenchPostUrl );

				expect( response.status() ).toBe( 200 );

				await expect( page ).toHaveTitle( /polylang FR/ );

				await expect(
					page
						.locator( 'header' )
						.locator( '.wp-block-site-title' )
						.getByText( 'polylang FR', { exact: true } )
				).toBeVisible();
			} );
		} );

		test.describe( 'Custom strings plugin', () => {
			/**
			 * Ensures the custom string French translation appears on the frontend via the_content output.
			 *
			 * Prerequisites:
			 *     - The admin test above saved the French translation "Bonjour Polylang E2E FR".
			 *     - This section's `beforeAll` activated the plugin and created a published French post.
			 *
			 * Steps:
			 *     - Open the French post on the frontend.
			 *     - Check that the appended paragraph shows the French translation.
			 */
			test( 'French translation of custom string appears on the frontend', async ( {
				page,
			} ) => {
				await page.goto( frenchPostUrl );

				await expect(
					page.locator( '.pll-e2e-custom-string' )
				).toHaveText( 'Bonjour Polylang E2E FR' );
			} );

			/**
			 * Ensures the multiline custom string French translation appears on the frontend.
			 *
			 * Prerequisites:
			 *     - The admin test above saved the French multiline translation.
			 *     - This section's `beforeAll` activated the plugin and created a published French post.
			 *
			 * Steps:
			 *     - Open the French post on the frontend.
			 *     - Check that the multiline block shows the French lines.
			 */
			test( 'French translation of multiline custom string appears on the frontend', async ( {
				page,
			} ) => {
				await page.goto( frenchPostUrl );

				await expect(
					page.locator( '.pll-e2e-custom-string-multiline' )
				).toContainText( 'Ligne un' );
				await expect(
					page.locator( '.pll-e2e-custom-string-multiline' )
				).toContainText( 'Ligne deux' );
			} );
		} );
	} );
} );
