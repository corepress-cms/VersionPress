import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Block editor', () => {
	test( 'publishing a post creates a commit', async ( {
		admin,
		editor,
		page,
	} ) => {
		const title = `Block editor post ${ Date.now() }`;

		await admin.createNewPost( { title } );
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Saved through the REST API' },
		} );
		await editor.publishPost();

		await admin.visitAdminPage( 'admin.php', 'page=versionpress' );
		await expect(
			page.getByRole( 'row', {
				name: new RegExp( `(Created|Published) post '${ title }'` ),
			} )
		).toBeVisible();
	} );
} );
