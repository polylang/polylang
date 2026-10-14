// @ts-check
import { wpEnvRun } from '@wpsyntex/e2e-test-utils';

async function globalTeardown() {
	// Set permalink structure back to default.
	/*
	 * Use hard flush to update .htaccess rules as well as rewrite rules in database.
	 */
	wpEnvRun( 'wp rewrite structure "" --hard --allow-root' );
}

export default globalTeardown;
