import { describe, expect, it } from 'vitest';

import { parseDiff } from '../diff';

describe( 'parseDiff', () => {
	it( 'splits files and classifies lines', () => {
		const files = parseDiff(
			[
				'diff --git a/wp-content/vpdb/options/bl/blogname.ini b/wp-content/vpdb/options/bl/blogname.ini',
				'index 1111111..2222222 100644',
				'--- a/wp-content/vpdb/options/bl/blogname.ini',
				'+++ b/wp-content/vpdb/options/bl/blogname.ini',
				'@@ -1,2 +1,2 @@',
				' [blogname]',
				'-option_value = "Old"',
				'+option_value = "New"',
				'diff --git a/image.png b/image.png',
				'Binary files a/image.png and b/image.png differ',
			].join( '\n' )
		);

		expect( files ).toHaveLength( 2 );
		expect( files[ 0 ].path ).toBe(
			'wp-content/vpdb/options/bl/blogname.ini'
		);
		expect( files[ 0 ].lines.map( ( l ) => l.kind ) ).toEqual( [
			'hunk',
			'context',
			'removed',
			'added',
		] );
		expect( files[ 1 ] ).toMatchObject( {
			path: 'image.png',
			isBinary: true,
			lines: [],
		} );
	} );
} );
