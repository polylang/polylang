// @ts-check
import { execSync } from 'child_process';

async function globalTeardown() {
	// Set permalink structure back to default.
	execSync(
		/*
		 * Use hard flush to update .htaccess rules as well as rewrite rules in database.
		 */
		'npx wp-env run tests-cli wp rewrite structure "" --hard --allow-root',
		{
			cwd: process.cwd(),
			stdio: 'ignore',
		}
	);
}

export default globalTeardown;
