import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.beforeEach(async ({ page }) => {
	await page.goto('http://localhost:8889/wp-login.php?redirect_to=http%3A%2F%2Flocalhost%3A8889%2Fwp-admin%2F&reauth=1');
	await page.getByLabel('Username or Email Address').click();
	await page.getByLabel('Username or Email Address').fill('admin');
	await page.getByLabel('Username or Email Address').press('Tab');
	await page.getByLabel('Password', { exact: true }).fill('password');
	await page.getByLabel('Password', { exact: true }).press('Enter');
	await page.getByRole('link', { name: 'Languages' }).first().click();

	const englist_language = await page.getByRole('link', { name: 'English' }).count();
	if ( englist_language === 0 ) {
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'English - en_US' }).click();
		await page.getByRole('button', { name: 'Add new language' }).click();
	}

	const french_language = await page.getByRole('link', { name: 'Français' }).count();
	if ( french_language === 0 ) {
		await page.locator('#lang_list-button span').nth(1).click();
		await page.getByRole('option', { name: 'Français - fr_FR' }).click();
		await page.getByRole('button', { name: 'Add new language' }).click();
	}

	const set_post_to_default_language = await page.getByRole('link', { name: 'You can set them all to the default language.' }).count();
	if ( set_post_to_default_language === 1 ) {
		await page.getByRole('link', { name: 'You can set them all to the default language.' }).click();
	}

	await page.getByRole('link', { name: 'Posts', exact: true }).click();
});

test.describe( 'Test posts translation in Block Editor', () => {
	test( 'Check if language metabox exists', async ( { page } ) => {
		await page.locator('#wpbody-content').getByRole('link', { name: 'Add New' }).click();

		await expect( page.locator( '#ml_box > div.postbox-header > h2' ) ).toHaveText( 'Languages' );
	});

	test( 'Create English post', async ( { page } ) => {
		await page.locator('#wpbody-content').getByRole('link', { name: 'Add New' }).click();
		await page.getByLabel('Add title').fill('English Post');
		await page.getByRole('button', { name: 'Publish', exact: true }).click();
		await page.getByLabel('Editor publish').getByRole('button', { name: 'Publish', exact: true }).click();
		await page.getByLabel('Close panel').click();

		// Check language.
		await expect( page.locator( '#post_lang_choice option[selected="selected"]' ) ).toHaveText( 'English' );
		await expect (page.locator( '#select-post-language span img' ) ).toHaveAttribute( 'alt', 'English');

		// Check post translations table.
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Add a translation in Français' );
		await expect (page.locator( '#post-translations > table > tbody > tr > th > img' ) ).toHaveAttribute( 'alt', 'Français');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a' ) ).toHaveAttribute( 'class', 'pll_icon_add');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Add a translation in Français');
		await expect( page.getByLabel('Translation') ).toHaveAttribute( 'value', '' );

	});

	test( 'Create English post translation in French', async ( { page } ) => {
		await page.getByRole('link', { name: 'Posts', exact: true }).click();
		await page.getByLabel('“English Post” (Edit)').click();
		await page.getByRole('link', { name: ' Add a translation in Français' }).click();

		// Check language.
		await expect( page.locator( '#post_lang_choice option[selected="selected"]' ) ).toHaveText( 'Français' );
		await expect( page.locator( '#post_lang_choice option[selected="selected"]' ) ).toHaveText( 'Français' );

		// Check post translations table.
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Edit the translation in English' );
		await expect( page.locator( '#post-translations > table > tbody > tr > th > img' ) ).toHaveAttribute( 'alt', 'English');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a' ) ).toHaveAttribute( 'class', 'pll_icon_edit');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Edit the translation in English');
		await expect( page.getByLabel('Translation') ).toHaveAttribute( 'value', 'English Post' );


		// Add title and save the post.
		await page.getByLabel('Add title').click();
		await page.getByLabel('Add title').fill('Post en français');
		await page.getByRole('button', { name: 'Publish', exact: true }).click();
		await page.getByLabel('Editor publish').getByRole('button', { name: 'Publish', exact: true }).click();
		await page.getByLabel('Close panel').click();

		// Check again language after saving the post.
		await expect( page.locator( '#post_lang_choice option[selected="selected"]' ) ).toHaveText( 'Français' );
		await expect( page.locator( '#post_lang_choice option[selected="selected"]' ) ).toHaveText( 'Français' );

		// Check again post translations table.
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Edit the translation in English' );
		await expect (page.locator( '#post-translations > table > tbody > tr > th > img' ) ).toHaveAttribute( 'alt', 'English');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a' ) ).toHaveAttribute( 'class', 'pll_icon_edit');
		await expect( page.locator( '#post-translations > table > tbody > tr > td.pll-edit-column.pll-column-icon > a > span' ) ).toHaveText( 'Edit the translation in English');
		await expect( page.getByLabel('Translation') ).toHaveAttribute( 'value', 'English Post' );
	});
});


