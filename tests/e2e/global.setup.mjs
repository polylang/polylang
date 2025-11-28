import { globalSetup as pllGlobalSetup } from '@wpsyntex/e2e-test-utils';
import { execSync } from 'child_process';

/**
 * @param {Object} config Playwright config object.
 */
async function globalSetup( config ) {
	await pllGlobalSetup( config );

	// Set pretty permalink structure.
	execSync(
		'npx wp-env run tests-cli wp rewrite structure "/%postname%/" --allow-root',
		{
			cwd: process.cwd(),
			stdio: 'inherit',
		}
	);
}

export default globalSetup;
