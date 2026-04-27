import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
import { devices } from '@playwright/test'; // eslint-disable-line import/no-extraneous-dependencies
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );

/**
 * Playwright config for running e2e tests with browsers in Docker.
 *
 * @return {import('@playwright/test').PlaywrightTestConfig} Playwright config object.
 */
export default getPlaywrightConfig( {
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
	webServer: {
		command: `npm run --prefix ${ projectRoot } env:start`,
		url: 'http://localhost:8889',
		reuseExistingServer: true,
		timeout: 120 * 1000,
	},
} );
