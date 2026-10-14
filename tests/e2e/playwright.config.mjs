import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );
const baseURL = 'http://localhost:8891';

// RequestUtils defaults to http://localhost:8889; align with Playwright baseURL.
process.env.WP_BASE_URL = baseURL;

export default getPlaywrightConfig( {
	wpCliCommand: 'npx wp-env --config=.wp-env.test.json run cli',
	globalSetup: './global.setup.mjs',
	globalTeardown: './global.teardown.mjs',
	use: {
		baseURL,
		storageState: path.join( process.cwd(), 'artifacts/storage-states/admin.json' ),
	},
	webServer: {
		command: `npm run --prefix ${ projectRoot } env:start`,
		url: baseURL,
		reuseExistingServer: true,
		timeout: 120 * 1000,
	},
} );
