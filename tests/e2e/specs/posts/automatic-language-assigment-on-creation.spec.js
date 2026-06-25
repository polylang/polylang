// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import { createLanguage, deleteAllLanguages } from '@wpsyntex/e2e-test-utils';

/**
 * Covers content creation and automatic default language assignment.
 */
test.describe
	.serial( 'Content Creation and Automatic Default Language Assignment', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'fr_FR' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
	} );

	/**
	 * Check the default language is assigned to a new draft post.
	 *
	 * Steps:
	 * - Visit the "Add New Post" page in the admin.
	 * - Check the selected value of the language dropdown.
	 *
	 * Expected behavior:
	 * Assert that the selected value contains 'en' (the default language).
	 */
	test( 'Check default language assigment on draft post', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'post-new.php' );
		const selectedValue = await page
			.getByRole( 'combobox', { name: /language/i } )
			.inputValue();

		expect( selectedValue ).toContain( 'en' );
	} );
} );
