import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Undo', () => {
	test( 'reverts a change and removes the post', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const title = `Post to undo ${ Date.now() }`;
		const post = await requestUtils.createPost( { title, status: 'publish' } );

		await admin.visitAdminPage( 'admin.php', 'page=versionpress' );
		const row = page.getByRole( 'row', {
			name: new RegExp( `post '${ title }'` ),
		} );
		await expect( row ).toBeVisible();

		await row.getByRole( 'button', { name: 'Actions' } ).click();
		await page.getByRole( 'menuitem', { name: 'Undo' } ).click();
		const dialog = page.getByRole( 'dialog', { name: 'Undo changes' } );
		await dialog.getByRole( 'button', { name: 'Undo', exact: true } ).click();

		await expect(
			page.getByRole( 'row', {
				name: new RegExp( `Reverted change.*${ title }` ),
			} )
		).toBeVisible();

		const response = await requestUtils.rest( {
			path: `/wp/v2/posts/${ post.id }`,
			method: 'GET',
		} ).catch( ( error: { code?: string } ) => error );
		expect( response ).toMatchObject( { code: 'rest_post_invalid_id' } );
	} );
} );
