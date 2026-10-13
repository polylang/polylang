import { devices } from '@playwright/test';
import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );
const baseURL = 'http://localhost:8891';
process.env.WP_BASE_URL = baseURL;
process.env.WP_ENV_CONFIG = '.wp-env.test.json';

/**
 * Playwright config for running e2e tests with browsers in Docker.
 *
 * @return {import('@playwright/test').PlaywrightTestConfig} Playwright config object.
 */
const config = getPlaywrightConfig( {
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
} );

config.use.baseURL = baseURL;
config.projects[ 0 ].use.baseURL = baseURL;
config.webServer.url = baseURL;
config.webServer.command = `npm run --prefix ${ projectRoot } env:start:test && sleep 86400`;

export default config;
