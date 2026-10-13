import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';

const baseURL = 'http://localhost:8891';

process.env.WP_BASE_URL = baseURL;
process.env.WP_ENV_CONFIG = '.wp-env.test.json';

export default getPlaywrightConfig( {
	globalSetup: './global.setup.mjs',
	globalTeardown: './global.teardown.mjs',
	use: {
		baseURL,
	},
	webServer: {
		url: baseURL,
		command: 'npm run env:start:test && sleep 86400',
	},
} );
