// @ts-check
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createLanguage,
	deleteAllLanguages,
	resetAllSettings,
	setSetting,
} from '@wpsyntex/e2e-test-utils';

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

		await context.route( '**/*', ( route, request ) => {
			route.continue( {
				headers: {
					...request.headers(),
					'accept-language': 'en-GB;q=1.0',
				},
			} );
		} );

		await page.goto( '/' );

		await expect( page ).toHaveURL( /\/en-gb\/?$/ );

		const cookies = await page.context().cookies();
		const languageCookie = cookies.find(
			( cookie ) => cookie.name === 'pll_language'
		);

		expect( languageCookie, 'Language cookie should be set' ).toBeDefined();
		expect(
			languageCookie.value,
			'Preferred language should be en-gb'
		).toBe( 'en-gb' );

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

		await context.route( '**/*', ( route, request ) => {
			route.continue( {
				headers: {
					...request.headers(),
					'accept-language': 'zh-HK;q=1.0,en;q=0.9',
				},
			} );
		} );

		await page.goto( '/' );

		await expect( page ).toHaveURL( /\/zh\/?$/ );

		const cookies = await page.context().cookies();
		const languageCookie = cookies.find(
			( cookie ) => cookie.name === 'pll_language'
		);

		expect( languageCookie, 'Language cookie should be set' ).toBeDefined();
		expect( languageCookie.value, 'Preferred language should be zh' ).toBe(
			'zh'
		);

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

		await context.route( '**/*', ( route, request ) => {
			route.continue( {
				headers: {
					...request.headers(),
					'accept-language': 'zh-Hant-HK;q=1.0,en;q=0.9',
				},
			} );
		} );

		await page.goto( '/' );

		const cookies = await page.context().cookies();
		const languageCookie = cookies.find(
			( cookie ) => cookie.name === 'pll_language'
		);

		expect( languageCookie, 'Language cookie should be set' ).toBeDefined();
		expect( languageCookie.value, 'Preferred language should be zh' ).toBe(
			'zh'
		);

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

		await context.route( '**/*', ( route, request ) => {
			route.continue( {
				headers: {
					...request.headers(),
					'accept-language': 'zh-Hant-HK;q=1.0,zh-CN;q=0.9,en;q=0.8',
				},
			} );
		} );

		await page.goto( '/' );

		const cookies = await page.context().cookies();
		const languageCookie = cookies.find(
			( cookie ) => cookie.name === 'pll_language'
		);

		expect( languageCookie, 'Language cookie should be set' ).toBeDefined();
		expect(
			languageCookie.value,
			'Preferred language should be zh-hk'
		).toBe( 'zh-hk' );

		await context.close();
	} );
} );
