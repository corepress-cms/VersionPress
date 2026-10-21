import { defineConfig, devices } from '@playwright/test';

// The fixtures of @wordpress/e2e-test-utils-playwright (requestUtils etc.) read these, too.
process.env.WP_BASE_URL ??= 'http://localhost:8890';
process.env.STORAGE_STATE_PATH ??= 'artifacts/storage-state.json';

const baseURL = process.env.WP_BASE_URL;

export default defineConfig( {
	testDir: './specs',
	// Tests share one site and its Git history.
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'github' ], [ 'list' ] ] : 'list',
	globalSetup: './global-setup.ts',
	use: {
		baseURL,
		storageState: process.env.STORAGE_STATE_PATH,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [ { name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } } ],
} );
