import { devices } from '@playwright/test';
import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
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
	use: {
		baseURL: 'http://localhost:8891',
		storageState: path.join( process.cwd(), 'artifacts/storage-states/admin.json' ),
	},
	webServer: {
		command: `npm run --prefix ${ projectRoot } env:start`,
		url: 'http://localhost:8891',
		reuseExistingServer: true,
		timeout: 120 * 1000,
	},
} );
