import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Logs in once (wp-env's default admin) and stores the session for all tests.
 *
 * @param config
 */
export default async function globalSetup( config: FullConfig ) {
	const { baseURL, storageState } = config.projects[ 0 ].use;

	const requestUtils = await RequestUtils.setup( {
		baseURL,
		storageStatePath:
			typeof storageState === 'string' ? storageState : undefined,
		user: { username: 'admin', password: 'password' },
	} );

	await requestUtils.setupRest();
}
