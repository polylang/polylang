// @ts-check
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	resetAllSettings,
	setSetting,
} from '@wpsyntex/e2e-test-utils';

/**
 * @typedef {import('@playwright/test').Page} Page
 * @typedef {import('@playwright/test').BrowserContext} BrowserContext
 */

/**
 * Covers browser preferred language detection on server side.
 */
test.describe( 'Should detect browser preferred language', () => {
	/**
	 * Before all tests:
	 *     - Enable browser preferred language detection.
	 */
	test.beforeAll( async ( { requestUtils } ) => {
		await setSetting( requestUtils, 'browser', true );
	} );

	/**
	 * Before each test:
	 *     - Clear cookies.
	 */
	test.beforeEach( async ( { page } ) => {
		await page.context().clearCookies();
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await resetAllSettings( requestUtils );
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await deleteAllLanguages( requestUtils );
		await requestUtils.deleteAllPosts();
	} );

	test( 'Should serve the best matching region', async ( {
		page,
		requestUtils,
		context,
	} ) => {
		await createLanguage( requestUtils, 'en_US' );
		await requestUtils.rest( {
			path: '/pll/v1/languages',
			method: 'POST',
			data: {
				locale: 'en_GB',
				slug: 'en-gb',
			},
		} );

		await requestUtils.createPost( {
			title: 'English US Post',
			status: 'publish',
			lang: 'en',
		} );
		await requestUtils.createPost( {
			title: 'English GB Post',
			status: 'publish',
			lang: 'en-gb',
		} );

		await setAcceptLanguageHeader( context, 'en-GB;q=1.0' );

		await page.goto( '/' );

		await expect( page ).toHaveURL( /\/en-gb\/?$/ );

		await expectLanguageCookie( page, 'en-gb' );

		await context.close();
	} );

	test( 'Should deduce language from unmatched language-region code', async ( {
		page,
		context,
		requestUtils,
	} ) => {
		await createLanguage( requestUtils, 'en_US' );
		await createLanguage( requestUtils, 'zh_CN' );

		await requestUtils.createPost( {
			title: 'English Post',
			status: 'publish',
			lang: 'en',
		} );
		await requestUtils.createPost( {
			title: 'Chinese Post',
			status: 'publish',
			lang: 'zh',
		} );

		await setAcceptLanguageHeader( context, 'zh-HK;q=1.0,en;q=0.9' );

		await page.goto( '/' );

		await expect( page ).toHaveURL( /\/zh\/?$/ );

		await expectLanguageCookie( page, 'zh' );

		await context.close();
	} );

	test( 'Should deduce language from unmatched language-script-region code', async ( {
		page,
		context,
		requestUtils,
	} ) => {
		await createLanguage( requestUtils, 'zh_CN' );
		await createLanguage( requestUtils, 'en_US' );

		await requestUtils.createPost( {
			title: 'Chinese Post',
			status: 'publish',
			lang: 'zh',
		} );
		await requestUtils.createPost( {
			title: 'English Post',
			status: 'publish',
			lang: 'en',
		} );

		await setAcceptLanguageHeader( context, 'zh-Hant-HK;q=1.0,en;q=0.9' );

		await page.goto( '/' );

		await expectLanguageCookie( page, 'zh' );

		await context.close();
	} );

	test( 'Should deduce region from unmatched language-script-region code', async ( {
		page,
		requestUtils,
		context,
	} ) => {
		await requestUtils.rest( {
			path: '/pll/v1/languages',
			method: 'POST',
			data: {
				locale: 'zh_HK',
				slug: 'zh-hk',
			},
		} );
		await createLanguage( requestUtils, 'zh_CN' );
		await createLanguage( requestUtils, 'en_US' );

		await requestUtils.createPost( {
			title: 'Chinese HK Post',
			status: 'publish',
			lang: 'zh-hk',
		} );
		await requestUtils.createPost( {
			title: 'Chinese CN Post',
			status: 'publish',
			lang: 'zh',
		} );
		await requestUtils.createPost( {
			title: 'English Post',
			status: 'publish',
			lang: 'en',
		} );

		await setAcceptLanguageHeader(
			context,
			'zh-Hant-HK;q=1.0,zh-CN;q=0.9,en;q=0.8'
		);

		await page.goto( '/' );

		await expectLanguageCookie( page, 'zh-hk' );

		await context.close();
	} );
} );

/**
 * Expects the language cookie to be set to the expected language slug.
 *
 * @param {Page}   page             - The page object.
 * @param {string} expectedLanguage - The expected language slug.
 */
const expectLanguageCookie = async ( page, expectedLanguage ) => {
	const cookies = await page.context().cookies();
	const languageCookie = cookies.find(
		( cookie ) => cookie.name === 'pll_language'
	);

	expect( languageCookie, 'Language cookie should be set' ).toBeDefined();
	expect(
		languageCookie.value,
		'Preferred language should be ' + expectedLanguage
	).toBe( expectedLanguage );
};

/**
 * Sets the accept language header for the given context.
 *
 * @param {BrowserContext} context        - The browser context.
 * @param {string}         acceptLanguage - The accept language header value.
 */
const setAcceptLanguageHeader = async ( context, acceptLanguage ) => {
	await context.route( '**/*', ( route, request ) => {
		route.continue( {
			headers: {
				...request.headers(),
				'accept-language': acceptLanguage,
			},
		} );
	} );
};
