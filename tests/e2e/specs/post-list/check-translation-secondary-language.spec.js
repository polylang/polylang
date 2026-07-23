// @ts-check
// Working on https://github.com/polylang/polylang-pro/issues/3034
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createLanguage, deleteAllLanguages } from '@wpsyntex/e2e-test-utils';

test.describe( 'Check Post language', { tag: [ '@pre-release' ] }, async () => {
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
	 * Tests that the block editor opens for the FR translation when clicking the "+" sign.
	 *
	 * Prerequisite:
	 * - 2 languages (EN, FR).
	 * - 1 English post.
	 *
	 * Steps:
	 * - Edit your English post.
	 * - Click on the "+" sign in the Polylang metabox ("Add a translation in Français").
	 *
	 * Expected behaviour:
	 * - The block editor opens with the language set to Français.
	 * - The title field is visible and can be edited.
	 */
	test( 'The block editor opens for the FR translation', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ englishPost.id }&action=edit`
		);

		// Trigger navigation and wait for it to settle.
		await page
			.getByRole( 'link', { name: 'Add a translation in Français' } )
			.click();

		await page.waitForURL(
			new RegExp(
				`/wp-admin/post-new\\.php\\?post_type=post&from_post=${ englishPost.id }&new_lang=fr&_wpnonce=\\w+`
			)
		);

		// Verify that the language of this new post is indeed FR: FR should be selected in the pll_post_lang_choice field of the language metabox
		await expect(
			page.getByRole( 'combobox', { name: 'Language' } )
		).toHaveValue( 'fr' );

		await page
			.locator( 'iframe[name="editor-canvas"]' )
			.contentFrame()
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'My FR Post' );

		await page.evaluate( () => {
			return window.wp.data.dispatch( 'core/editor' ).savePost();
		} );

		// The French post is correctly linked to the English post
		await expect(
			page.getByRole( 'textbox', { name: 'Translation' } )
		).toHaveValue( 'An English Post' );

		// Check the slug is correctly calculated
		await expect(
			page.getByText( 'my-fr-post', { exact: true } ).nth( 1 )
		);

		// No content is “orphaned” after the translation is created. The English and French posts are properly linked via the translation.
		await admin.visitAdminPage( 'edit.php' );
		await expect(
			page.getByRole( 'link', {
				name: 'Edit the translation in Français',
			} )
		).toBeVisible();
	} );
} );
