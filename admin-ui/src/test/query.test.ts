import { describe, expect, it } from 'vitest';

import type { View } from '@wordpress/dataviews';

import { viewToQuery } from '../query';

const view = ( overrides: Partial< View > ): View =>
	( { type: 'table', ...overrides } ) as View;

describe( 'viewToQuery', () => {
	it( 'is empty without search and filters', () => {
		expect( viewToQuery( view( {} ) ) ).toBe( '' );
	} );

	it( 'passes the search text through', () => {
		expect( viewToQuery( view( { search: '  hello world ' } ) ) ).toBe(
			'hello world'
		);
	} );

	it( 'repeats keys for multiple values, which the search syntax treats as OR', () => {
		expect(
			viewToQuery(
				view( {
					filters: [
						{
							field: 'action',
							operator: 'isAny',
							value: [ 'post/create', 'post/update' ],
						},
					],
				} )
			)
		).toBe( 'action:post/create action:post/update' );
	} );

	it( 'quotes values with spaces', () => {
		expect(
			viewToQuery(
				view( {
					filters: [
						{
							field: 'author',
							operator: 'isAny',
							value: [ 'John Doe' ],
						},
					],
				} )
			)
		).toBe( 'author:"John Doe"' );
	} );

	it( 'maps date operators to before/after/date keys', () => {
		expect(
			viewToQuery(
				view( {
					filters: [
						{
							field: 'date',
							operator: 'after',
							value: '2026-01-02T10:00:00',
						},
						{ field: 'date', operator: 'on', value: '2026-03-04' },
					],
					search: 'menu',
				} )
			)
		).toBe( 'after:2026-01-02 date:2026-03-04 menu' );
	} );

	it( 'ignores empty filters and unknown fields', () => {
		expect(
			viewToQuery(
				view( {
					filters: [
						{ field: 'scope', operator: 'isAny', value: [] },
						{
							field: 'environment',
							operator: 'is',
							value: 'staging',
						},
					],
				} )
			)
		).toBe( '' );
	} );
} );
