import { getPlaywrightConfig } from '@wpsyntex/e2e-test-utils';

export default getPlaywrightConfig( {
	globalSetup: './global.setup.mjs',
	globalTeardown: './global.teardown.mjs',
} );
