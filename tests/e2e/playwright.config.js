import { defineConfig, devices } from '@playwright/test';

import path from 'path';

const STORAGE_STATE_PATH = process.env.STORAGE_STATE_PATH || path.join( process.cwd(), 'artifacts/storage-states/admin.json' );

export default defineConfig( {
	testDir: './specs',

	globalSetup: require.resolve('./global.setup.js'),

	fullyParallel: true,

	// Fail the build on CI if you accidentally left test.only in the source code.
	forbidOnly: ! ! process.env.CI,

	// Retry on CI only.
	retries: process.env.CI ? 2 : 0,

	// Opt out of parallel tests on CI.
	workers: process.env.CI ? 1 : undefined,

	reporter: 'html',

	use: {
		baseURL: 'http://localhost:8889',
		ignoreHTTPSErrors: true,
		trace: 'on-first-retry',
		headless: true,
		storageState: STORAGE_STATE_PATH,
	},

	// Configure projects only for Chrome for the moment.
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],

	// Run local dev server before starting the tests.
	webServer: {
		command: 'npm run env:start',
		url: 'http://localhost:8889',
		reuseExistingServer: ! process.env.CI,
	},
} );
