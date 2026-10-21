import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'History screen', () => {
	test( 'lists the site history', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'admin.php', 'page=versionist' );

		await expect(
			page.getByRole( 'heading', { name: 'Versionist', level: 1 } )
		).toBeVisible();
		await expect( page.getByRole( 'row' ).nth( 1 ) ).toBeVisible();
	} );

	test( 'searches the history', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'admin.php', 'page=versionist' );

		await page
			.getByRole( 'searchbox', { name: 'Search history' } )
			.fill( 'Activated Versionist' );

		await expect(
			page.getByRole( 'row', { name: /Activated Versionist/ } )
		).toBeVisible();
	} );
} );
