import { globalSetup as pllGlobalSetup } from '@wpsyntex/e2e-test-utils';
import { execSync } from 'child_process';

/**
 * @param {Object} config Playwright config object.
 */
async function globalSetup( config ) {
	await pllGlobalSetup( config );

	// Set pretty permalink structure.
	execSync(
		/*
		 * Use hard flush to update .htaccess rules as well as rewrite rules in database.
		 */
		'npx wp-env run tests-cli wp rewrite structure "/%postname%/" --hard --allow-root',
		{
			cwd: process.cwd(),
			stdio: 'inherit',
		}
	);
}

export default globalSetup;
