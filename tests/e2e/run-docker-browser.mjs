#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * Script to start the Playwright Server Docker container for running e2e tests
 * with browsers in Docker (especially useful for consistent Linux snapshots).
 *
 * Usage:
 *   node run-docker-browser.mjs
 *
 * To stop the Playwright Server:
 *   docker stop playwright-server
 *
 * To remove the Playwright Server container:
 *   docker rm -f playwright-server
 */

import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import path from 'path';

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const projectRoot = path.resolve( __dirname, '../..' );

const npmListOutput = execSync( 'npm list @playwright/test --depth=0 --json', {
	encoding: 'utf-8',
	cwd: projectRoot,
	stdio: 'pipe',
} );
const npmList = JSON.parse( npmListOutput );
const PLAYWRIGHT_VERSION =
	npmList.dependencies?.[ '@playwright/test' ]?.version ||
	npmList.packages?.[ 'node_modules/@playwright/test' ]?.version;

if ( ! PLAYWRIGHT_VERSION ) {
	console.error( 'Failed to detect Playwright version from npm list' );
	process.exit( 1 );
}

console.log( `Using Playwright version: ${ PLAYWRIGHT_VERSION }` );

const PLAYWRIGHT_SERVER_NAME = 'playwright-server';
const PLAYWRIGHT_SERVER_PORT = 3000;
const PLAYWRIGHT_IMAGE = `mcr.microsoft.com/playwright:v${ PLAYWRIGHT_VERSION }-noble`;

/**
 * Checks if a Docker container is running.
 *
 * @param {string} containerName - The name of the Docker container to check.
 * @return {boolean} True if the container is running, false otherwise.
 */
const isContainerRunning = ( containerName ) => {
	try {
		const result = execSync(
			`docker ps --filter "name=${ containerName }" --format "{{.Names}}"`,
			{ encoding: 'utf-8', stdio: 'pipe' }
		);
		return result.trim() === containerName;
	} catch {
		return false;
	}
};

/**
 * Checks if a Docker container exists (running or stopped).
 *
 * @param {string} containerName - The name of the Docker container to check.
 * @return {boolean} True if the container exists, false otherwise.
 */
const containerExists = ( containerName ) => {
	try {
		const result = execSync(
			`docker ps -a --filter "name=${ containerName }" --format "{{.Names}}"`,
			{ encoding: 'utf-8', stdio: 'pipe' }
		);
		return result.trim() === containerName;
	} catch {
		return false;
	}
};

/**
 * Gets the image used by an existing Docker container.
 *
 * @param {string} containerName - The name of the Docker container.
 * @return {string|null} The image name or null if not found.
 */
const getContainerImage = ( containerName ) => {
	try {
		const result = execSync(
			`docker inspect --format='{{.Config.Image}}' ${ containerName }`,
			{ encoding: 'utf-8', stdio: 'pipe' }
		);
		return result.trim();
	} catch {
		return null;
	}
};

/**
 * Starts an existing Docker container.
 *
 * @param {string} containerName - The name of the Docker container to start.
 * @return {boolean} True if the container was started successfully, false otherwise.
 */
const startExistingContainer = ( containerName ) => {
	try {
		console.log( `Starting existing container: ${ containerName }...` );
		execSync( `docker start ${ containerName }`, { stdio: 'inherit' } );
		console.log( 'Container started successfully.' );
		return true;
	} catch ( error ) {
		console.error( 'Failed to start container:', error.message );
		return false;
	}
};

/**
 * Removes an existing Docker container.
 *
 * @param {string} containerName - The name of the Docker container to remove.
 * @return {boolean} True if the container was removed successfully, false otherwise.
 */
const removeContainer = ( containerName ) => {
	try {
		console.log( `Removing existing container: ${ containerName }...` );
		execSync( `docker rm -f ${ containerName }`, { stdio: 'inherit' } );
		console.log( 'Container removed successfully.' );
		return true;
	} catch ( error ) {
		console.error( 'Failed to remove container:', error.message );
		return false;
	}
};

/**
 * Starts the Playwright Server Docker container.
 *
 * @return {boolean} True if the Playwright Server was started successfully, false otherwise.
 */
const startPlaywrightServer = () => {
	console.log( `Starting Playwright Server (${ PLAYWRIGHT_IMAGE })...` );

	try {
		execSync(
			`docker run -d --name ${ PLAYWRIGHT_SERVER_NAME } ` +
				`--init --ipc=host --network host ` +
				`${ PLAYWRIGHT_IMAGE } ` +
				`/bin/sh -c "npx -y playwright@${ PLAYWRIGHT_VERSION } run-server --port ${ PLAYWRIGHT_SERVER_PORT } --host 0.0.0.0"`,
			{ stdio: 'inherit' }
		);
		console.log( 'Playwright Server started successfully.' );
		return true;
	} catch ( error ) {
		console.error( 'Failed to start Playwright Server:', error.message );
		return false;
	}
};

/**
 * Checks if Docker is running.
 *
 * @return {boolean} True if Docker is running, false otherwise.
 */
const isDockerRunning = () => {
	try {
		execSync( 'docker ps', { encoding: 'utf-8', stdio: 'pipe' } );
		return true;
	} catch {
		return false;
	}
};

/**
 * Main execution.
 *
 * @return {void}
 */
const main = () => {
	if ( ! isDockerRunning() ) {
		console.error(
			'Docker is not running. Please start Docker and try again.'
		);
		process.exit( 1 );
	}

	if ( isContainerRunning( PLAYWRIGHT_SERVER_NAME ) ) {
		console.log( 'Playwright Server is already running.' );
		process.exit( 0 );
	}

	if ( containerExists( PLAYWRIGHT_SERVER_NAME ) ) {
		const existingImage = getContainerImage( PLAYWRIGHT_SERVER_NAME );

		if ( existingImage === PLAYWRIGHT_IMAGE ) {
			if ( startExistingContainer( PLAYWRIGHT_SERVER_NAME ) ) {
				console.log( 'Playwright Server is running.' );
				process.exit( 0 );
			} else {
				console.error( 'Failed to start Playwright Server.' );
				process.exit( 1 );
			}
		} else {
			console.log(
				`Container exists with different Playwright version (${ existingImage }). Removing...`
			);
			if ( ! removeContainer( PLAYWRIGHT_SERVER_NAME ) ) {
				console.error( 'Failed to remove existing container.' );
				process.exit( 1 );
			}
		}
	}

	if ( ! startPlaywrightServer() ) {
		console.error( 'Failed to start Playwright Server.' );
		process.exit( 1 );
	}

	console.log( 'Playwright Server is running.' );
	process.exit( 0 );
};

main();
