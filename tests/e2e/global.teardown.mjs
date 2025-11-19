// @ts-check
import { execSync } from 'child_process';

/**
 * @param {Object} config Playwright config object.
 */
async function globalTeardown( config ) {
	// Set permalink structure back to default.
	execSync(
		'npx wp-env run tests-cli wp rewrite structure "" --allow-root',
		{
			cwd: process.cwd(),
			stdio: 'ignore',
		}
	);
}

export default globalTeardown;
