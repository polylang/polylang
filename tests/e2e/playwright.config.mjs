import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';

const baseURL = 'http://localhost:8891';
process.env.WP_BASE_URL = baseURL;

const config = getPlaywrightConfig( {
	globalSetup: './global.setup.mjs',
	globalTeardown: './global.teardown.mjs',
} );

config.use.baseURL = baseURL;
config.projects[ 0 ].use.baseURL = baseURL;
config.webServer.url = baseURL;
config.webServer.command = 'npm run env:start:test && sleep 86400';

export default config;
