// @ts-check
import { execSync } from 'child_process';

async function globalTeardown() {
	// Set permalink structure back to default.
	execSync( 'npx wp-env run tests-cli wp rewrite structure "" --allow-root', {
		cwd: process.cwd(),
		stdio: 'ignore',
	} );
}

export default globalTeardown;
