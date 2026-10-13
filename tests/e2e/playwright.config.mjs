import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );

export default getPlaywrightConfig( {
	globalSetup: './global.setup.mjs',
	globalTeardown: './global.teardown.mjs',
	use: {
		baseURL: 'http://localhost:8891',
	},
	webServer: {
		command: `npm run --prefix ${ projectRoot } env:start && sleep 86400`,
		url: 'http://localhost:8891',
		reuseExistingServer: true,
		timeout: 120 * 1000,
	},
} );
