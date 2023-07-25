// @ts-check

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies.
 */
import {
	deleteAllLanguages,
	createLanguage,
	deleteLanguage,
	getAllLanguages,
	getOptions,
	setOption,
	deleteOptions
} from '../tools';

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
		const english = await createLanguage( requestUtils, 'en_US' );
		expect( english, 'English should be created and returned.' ).toStrictEqual(
			{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "en_US", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"English\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "us", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/us.png", "host": null, "is_default": true, "is_rtl": 0, "locale": "en_US", "name": "English", "page_for_posts": 0, "page_on_front": 0, "slug": "en", "term_group": 0, "term_id": 2, "w3c": "en-US"}
		);

		const french = await createLanguage( requestUtils, 'fr_FR' );
		expect( french, 'French should be created and returned.' ).toStrictEqual(
			{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "fr_FR", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAbFBMVEVzldTg4ODS0tLxDwDtAwDjAADD0uz39/fy8vL3k4nzgna4yOixwuXu7u7s6+zn5+fyd2rvcGPtZljYAABrjNCpvOHrWkxegsqfs93NAADpUUFRd8THAABBa7wnVbERRKa8vLyxsLCoqKigoKClCvcsAAAAXklEQVR4AS3JxUEAQQAEwZo13Mk/R9w5/7UERJCIGIgj5qfRJZEpPyNfCgJTjMR1eRRnJiExFJz5Mf1PokWr/UztIjRGQ3V486u0HO55m634U6dMcf0RNPfkVCTvKjO16xHA8miowAAAAABJRU5ErkJggg==\" alt=\"Français\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "fr", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/fr.png", "host": null, "is_default": false, "is_rtl": 0, "locale": "fr_FR", "name": "Français", "page_for_posts": 0, "page_on_front": 0, "slug": "fr", "term_group": 0, "term_id": 4, "w3c": "fr-FR",}
		);

		const allLanguages = await getAllLanguages( requestUtils );
		expect( allLanguages ).toStrictEqual(
			[
				{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "en_US", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAmVBMVEViZsViZMJiYrf9gnL8eWrlYkjgYkjZYkj8/PujwPybvPz4+PetraBEgfo+fvo3efkydfkqcvj8Y2T8UlL8Q0P8MzP9k4Hz8/Lu7u4DdPj9/VrKysI9fPoDc/EAZ7z7IiLHYkjp6ekCcOTk5OIASbfY/v21takAJrT5Dg6sYkjc3Nn94t2RkYD+y8KeYkjs/v7l5fz0dF22YkjWvcOLAAAAgElEQVR4AR2KNULFQBgGZ5J13KGGKvc/Cw1uPe62eb9+Jr1EUBFHSgxxjP2Eca6AfUSfVlUfBvm1Ui1bqafctqMndNkXpb01h5TLx4b6TIXgwOCHfjv+/Pz+5vPRw7txGWT2h6yO0/GaYltIp5PT1dEpLNPL/SdWjYjAAZtvRPgHJX4Xio+DSrkAAAAASUVORK5CYII=\" alt=\"English\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "us", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/us.png", "host": null, "is_default": true, "is_rtl": 0, "locale": "en_US", "name": "English", "page_for_posts": 0, "page_on_front": 0, "slug": "en", "term_group": 0, "term_id": 2, "w3c": "en-US"},
				{"active": true, "custom_flag": "", "custom_flag_url": "", "facebook": "fr_FR", "fallbacks": [], "flag": "<img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAALCAMAAABBPP0LAAAAbFBMVEVzldTg4ODS0tLxDwDtAwDjAADD0uz39/fy8vL3k4nzgna4yOixwuXu7u7s6+zn5+fyd2rvcGPtZljYAABrjNCpvOHrWkxegsqfs93NAADpUUFRd8THAABBa7wnVbERRKa8vLyxsLCoqKigoKClCvcsAAAAXklEQVR4AS3JxUEAQQAEwZo13Mk/R9w5/7UERJCIGIgj5qfRJZEpPyNfCgJTjMR1eRRnJiExFJz5Mf1PokWr/UztIjRGQ3V486u0HO55m634U6dMcf0RNPfkVCTvKjO16xHA8miowAAAAABJRU5ErkJggg==\" alt=\"Français\" width=\"16\" height=\"11\" style=\"width: 16px; height: 11px;\" />", "flag_code": "fr", "flag_url": "http://localhost:8889/wp-content/plugins/polylang/flags/fr.png", "host": null, "is_default": false, "is_rtl": 0, "locale": "fr_FR", "name": "Français", "page_for_posts": 0, "page_on_front": 0, "slug": "fr", "term_group": 0, "term_id": 4, "w3c": "fr-FR",}
			]
		);

		const isFrenchDeleted = await deleteLanguage( requestUtils, 'fr' );
		expect( isFrenchDeleted, 'French should be deleted.' ).toBeTruthy();

		const isAllLanguagesDeleted = await deleteAllLanguages( requestUtils );
	expect( isAllLanguagesDeleted, 'All languages should be deleted.' ).toBeTruthy();

		const noMoreLanguages = await getAllLanguages( requestUtils );
		expect( noMoreLanguages, 'Languages list should be empty.' ).toStrictEqual([]);
	} );

	test( 'Polylang test plugin manages options properly', async ( { requestUtils } ) => {
		const supportMedia = await setOption( requestUtils, 'media_support', '1' );
		expect( supportMedia, 'media_support option should be updated.' ).toBeTruthy();

		const resetOptions = await deleteOptions( requestUtils );
		expect( resetOptions, 'Options should be set to default values.' ).toBeTruthy();

		// Test all default options values.
		const options = await getOptions( requestUtils );
		expect( options.browser ).toStrictEqual( 0 );
		expect( options.domains ).toStrictEqual( [] );
		expect( options.force_lang ).toStrictEqual( 1 );
		expect( options.hide_default ).toStrictEqual( 1 );
		expect( options.media_support ).toStrictEqual( 0 );
		expect( options.post_types ).toStrictEqual( [] );
		expect( options.redirect_lang ).toStrictEqual( 0 );
		expect( options.rewrite ).toStrictEqual( 1 );
		expect( options.sync ).toStrictEqual( [] );
		expect( options.taxonomies ).toStrictEqual( [] );
		expect( options.uninstall ).toStrictEqual( 0 );
		expect( options.first_activation ).toBeDefined();
		expect( options.version ).toBeDefined();
	} );
});
