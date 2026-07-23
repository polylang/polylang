// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	resetAllSettings,
} from '@wpsyntex/e2e-test-utils';

/**
 * Covers content creation and automatic default language assignment.
 */
test.describe( 'Content Creation and Automatic Default Language Assignment', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'fr_FR' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await resetAllSettings( requestUtils );
		await requestUtils.deleteAllPosts();
	} );

	/**
	 * Check the default language is assigned to a new draft post.
	 *
	 * Prerequisites:
	 * - Polylang activated and setuped with at least 2 languages.
	 * - 1 language set as default.
	 * Steps:
	 * - Visit the "Add New Post" page in the admin.
	 * - Check the selected value of the language dropdown.
	 *
	 * Expected behavior:
	 * Assert that the selected value contains 'en' (the default language).
	 */
	test( 'Check default language assignment on draft post', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'post-new.php' );
		const selectedValue = await page
			.getByRole( 'combobox', { name: /language/i } )
			.inputValue();

		expect( selectedValue ).toContain( 'en' );
	} );

	/**
	 * Check the default language is assigned to a new published post.
	 *
	 * - Polylang activated and setuped with at least 2 languages.
	 * - 1 language set as default.
	 * Steps:
	 * - Visit the "Add New Post" page in the admin.
	 * - Fill in the post title and publish the post.
	 * - Check the selected value of the language dropdown.
	 *
	 * Expected behavior:
	 * Assert that the selected value contains 'en' (the default language).
	 */
	test( 'Check default language assignment on published post', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.canvas
			.getByRole( 'textbox', { name: /add title/i } )
			.fill( 'Test Post Title' );
		await editor.publishPost();
		const selectedValue = await page
			.getByRole( 'combobox', { name: /language/i } )
			.inputValue();

		expect( selectedValue ).toContain( 'en' );
	} );
} );
