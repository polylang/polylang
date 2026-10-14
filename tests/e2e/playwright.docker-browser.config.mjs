import { devices } from '@playwright/test';
import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );
const baseURL = 'http://localhost:8891';

// RequestUtils defaults to http://localhost:8889; align with Playwright baseURL.
process.env.WP_BASE_URL = baseURL;

/**
 * Playwright config for running e2e tests with browsers in Docker.
 *
 * @return {import('@playwright/test').PlaywrightTestConfig} Playwright config object.
 */
export default getPlaywrightConfig( {
	wpCliCommand: 'npx wp-env --config=.wp-env.test.json run cli',
	testDir: path.resolve( __dirname, 'specs' ),
	forbidOnly: false,
	retries: 0,
	snapshotPathTemplate:
		'{testDir}/{testFileDir}/{testFileName}-snapshots/{arg}-{projectName}-linux{ext}', // Force Linux snapshots for Docker browser.
	projects: [
		{
			name: 'chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
				launchOptions: {
					args: [ '--no-sandbox', '--disable-setuid-sandbox' ],
				},
			},
		},
	],
	use: {
		baseURL,
	},
	webServer: {
		command: `npm run --prefix ${ projectRoot } env:start`,
		url: baseURL,
		reuseExistingServer: true,
		timeout: 120 * 1000,
	},
} );
