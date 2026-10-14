import { globalSetup as pllGlobalSetup, wpEnvRun } from '@wpsyntex/e2e-test-utils';

/**
 * @param {Object} config Playwright config object.
 */
async function globalSetup( config ) {
	await pllGlobalSetup( config );

	// Set pretty permalink structure.
	/*
	 * Use hard flush to update .htaccess rules as well as rewrite rules in database.
	 */
	wpEnvRun( 'wp rewrite structure "/%postname%/" --hard --allow-root' );
}

export default globalSetup;
