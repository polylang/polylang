// @ts-check
// Working on https://github.com/polylang/polylang-pro/issues/3034
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createLanguage, deleteAllLanguages } from '@wpsyntex/e2e-test-utils';

test.describe( 'Check Post language', async () => {
	let englishPost;
	/**
	 * Before all tests:
	 *     - Create en_US and fr_FR languages.
	 *     - Create an English and a French post. Not translated to each other.
	 */
	test.beforeAll( async ( { requestUtils } ) => {
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'fr_FR' );

		englishPost = await requestUtils.createPost( {
			title: 'An English Post',
			status: 'publish',
			lang: 'en',
		} );
	} );

	/**
	 * Reset after all tests.
	 */
	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
	} );

	/**
	 * Tests that the French translation is correctly created by cicking on the "+" sign.
	 *
	 * Prerequisite:
	 * - 2 languages (EN, FR).
	 * - 1 English post.
	 *
	 * Steps:
	 * - Edit your English post.
	 * - Click on the "+" sign in the Polylang metabox.
	 *
	 * Expected behaviour:
	 * - The French translation is correctly created.
	 */
	test( 'The block editor opens for the FR translation', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ englishPost.id }&action=edit`
		);
		await page
			.getByRole( 'link', { name: 'Add a translation in Français' } )
			.click();
		await expect(
			page.getByRole( 'combobox', { name: 'Language' } )
		).toHaveValue( 'fr' );
	} );
} );
