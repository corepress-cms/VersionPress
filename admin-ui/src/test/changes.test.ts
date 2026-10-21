import { describe, expect, it } from 'vitest';

import type { Commit } from '../api';
import { formatChangeGroup, groupChanges, verbToPastTense } from '../changes';

const commit = ( changes: Commit[ 'changes' ] ): Commit =>
	( { changes } ) as Commit;

describe( 'verbToPastTense', () => {
	it.each( [
		[ 'update', 'updated' ],
		[ 'create', 'created' ],
		[ 'trash', 'trashed' ],
		[ 'modify', 'modified' ],
		[ 'create-pending', 'created' ],
	] )( '%s → %s', ( verb, expected ) => {
		expect( verbToPastTense( verb ) ).toBe( expected );
	} );
} );

describe( 'groupChanges', () => {
	it( 'groups posts by post type and lists their titles', () => {
		const groups = groupChanges(
			commit( [
				{
					type: 'post',
					action: 'update',
					name: 'A',
					tags: { 'VP-Post-Type': 'page', 'VP-Post-Title': 'About' },
				},
				{
					type: 'post',
					action: 'update',
					name: 'B',
					tags: {
						'VP-Post-Type': 'page',
						'VP-Post-Title': 'Contact',
					},
				},
				{
					type: 'post',
					action: 'update',
					name: 'C',
					tags: { 'VP-Post-Type': 'post', 'VP-Post-Title': 'Hello' },
				},
			] )
		);

		expect( groups.map( formatChangeGroup ) ).toEqual( [
			'Updated page (2): About, Contact',
			'Updated post: Hello',
		] );
	} );

	it( 'describes meta changes with the entity they belong to', () => {
		const [ group ] = groupChanges(
			commit( [
				{
					type: 'postmeta',
					action: 'create',
					name: '_thumbnail_id',
					tags: { 'VP-Post-Title': 'Hello' },
				},
			] )
		);

		expect( formatChangeGroup( group ) ).toBe(
			'Created postmeta for post Hello: _thumbnail_id'
		);
	} );

	it( 'shows the reverted commit for undo', () => {
		const [ group ] = groupChanges(
			commit( [
				{
					type: 'versionpress',
					action: 'undo',
					name: 'abc123',
					tags: {
						'VP-Commit-Details': {
							message: "Created post 'Hello'",
							date: '',
						},
					},
				},
			] )
		);

		expect( formatChangeGroup( group ) ).toBe(
			"Reverted change: Created post 'Hello'"
		);
	} );

	it( 'summarizes long lists of files', () => {
		const files = Array.from( { length: 8 }, ( _, i ) => ( {
			type: 'file',
			action: 'modify',
			name: `file-${ i }.php`,
		} ) );

		expect(
			formatChangeGroup( groupChanges( commit( files ) )[ 0 ] )
		).toBe(
			'Modified file (8): file-0.php, file-1.php, file-2.php, file-3.php, file-4.php and 3 more'
		);
	} );
} );
