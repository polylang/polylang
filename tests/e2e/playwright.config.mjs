import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';

const baseURL = 'http://localhost:8891';

// RequestUtils defaults to http://localhost:8889; align with Playwright baseURL.
process.env.WP_BASE_URL = baseURL;

export default getPlaywrightConfig( {
	wpCliCommand: 'npx wp-env --config=.wp-env.test.json run cli',
	use: {
		baseURL,
	},
	webServer: {
		command: 'npm run env:start:test && sleep 86400',
		url: baseURL,
	},
} );
