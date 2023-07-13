// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Environment Setup Test', () => {
	test( 'Should load properly', async ( { page } ) => {
		page.goto( '/wp-admin' );
		await page.waitForLoadState( 'networkidle' );
		await page.waitForLoadState( 'domcontentloaded' );

		// Admin page should load properly.
		await expect( page.locator( 'div.wrap > h1' ) ).toHaveText( 'Dashboard' );

		// Polylang admin menu should be available.
		await expect( page.locator( 'li.toplevel_page_mlang > a > div.wp-menu-name' ) ).toHaveText( 'Languages' );
	} );

	test( 'Polylang test plugin manages languages properly', async ( { requestUtils } ) => {
		const newLanguage = await requestUtils.rest(
			{
				path: '/pll-test/v1/languages',
				method: 'POST',
				params: {
					locale: 'en_US',
				}
			}
		);
		expect( newLanguage ).toStrictEqual(
			{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "en_US", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"English\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "us", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/us.png", "host": null, "is_default": true, "is_rtl": 0, "locale": "en_US", "name": "English", "page_for_posts": 0, "page_on_front": 0, "slug": "en", "term_group": 0, "term_id": 2, "w3c": "en-US"}
		);

		const allLanguages = await requestUtils.rest(
			{
				path: '/pll-test/v1/languages',
				method: 'GET',
			}
		);
		expect( allLanguages ).toStrictEqual(
			[
				{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "en_US", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"English\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "us", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/us.png", "host": null, "is_default": true, "is_rtl": 0, "locale": "en_US", "name": "English", "page_for_posts": 0, "page_on_front": 0, "slug": "en", "term_group": 0, "term_id": 2, "w3c": "en-US"}
			]
		);

		const deleteLanguages = await requestUtils.rest(
			{
				path: '/pll-test/v1/languages',
				method: 'DELETE',
			}
		);
		expect( newLanguage ).toBeTruthy();
	} );
});
