// @ts-check
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllLanguages, createLanguage } from '@wpsyntex/e2e-test-utils';

test.describe( 'create language and test the bulk assignment of content without languages', () => {
	/**
	 * Before all tests:
	 * - Create post without languages
	 */
	test.beforeAll( async ( { requestUtils } ) => {} );

	/**
	 * Reset after all tests.
	 */
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await deleteAllLanguages( requestUtils );
	} );

	test.afterEach( async ( { requestUtils } ) => {
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

	/**
	 * Assign in bulk the default language to all content without languages.
	 *
	 * Steps:
	 * - visit language setting page
	 * - create a language (English en_US)
	 * - Click on the "Assign" button in the "Content without languages" section
	 *
	 * Expected Behavior
	 * - The already created post without a language should be assigned to the default language (English en_US)
	 */

	test( 'Bulk assign default language to content without languages', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		// Create a post without a language.
		let noLanguagePost;
		noLanguagePost = await requestUtils.createPost( {
			title: 'Test Post',
			content: 'This is a test post without languages.',
		} );
		// create English en_US as the default language.
		await createLanguage( requestUtils, 'en_US' );
		// visit the language settings page and click on the "Assign" link in the "Content without languages" section.
		await admin.visitAdminPage( 'admin.php', 'page=mlang' );
		await page
			.getByRole( 'link', {
				name: 'You can set them all to the default language',
			} )
			.click();
		// After clicking the "Assign" link, the previously created post should now be assigned to the default language (English en_US).
		await admin.visitAdminPage( 'edit.php' );
		const NoLanguagePostRow = page.locator(
			`#post-${ noLanguagePost.id }`
		);
		await expect(
			NoLanguagePostRow.getByAltText( 'English' )
		).toBeVisible();
		await admin.visitAdminPage( 'admin.php', 'page=mlang' );
	} );

	/**
	 * Check the post count are correctly updated in languages table.
	 *
	 * Steps:
	 * - Create a post without a language.
	 * - Create a language (English en_US)
	 * - Assign the language to the post.
	 *
	 * Expected Behavior
	 * - The post count in the language table should be incremented.
	 */

	test( 'Check the post count', async ( { page, admin, requestUtils } ) => {
		// create English en_US as the default language.
		await createLanguage( requestUtils, 'en_US' );
		// Create a post for English language.
		await requestUtils.createPost( {
			title: 'Test Post',
			content: 'This is a test post in English.',
			status: 'publish',
			lang: 'en',
		} );

		// visit the language settings page and click on the "Assign" link in the "Content without languages" section.
		await admin.visitAdminPage( 'admin.php', 'page=mlang' );

		const englishRow = page.getByRole( 'row', { name: /English/ } ).first();
		// The "Posts" column cell contains a link with the post count as its text.

		const postsCell = englishRow.getByRole( 'cell' ).last();

		await expect
			.poll(
				async () => {
					await page.reload();
					return ( await postsCell.innerText() ).trim();
				},
				{ timeout: 10_000 }
			)
			.toBe( '1' );
	} );
} );
