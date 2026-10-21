import type { View } from '@wordpress/dataviews';

/** Filters that map 1:1 to a `key:value` term of the Versionist search syntax. */
const KEYED_FILTERS: Record< string, string > = {
	action: 'action',
	scope: 'scope',
	author: 'author',
};

/** Operators of the `date` filter and the search key each one maps to. */
const DATE_OPERATORS: Record< string, string > = {
	before: 'before',
	after: 'after',
	on: 'date',
};

const quote = ( value: string ) =>
	/[\s"]/.test( value ) ? `"${ value.replace( /"/g, '\\"' ) }"` : value;

/**
 * Converts the DataViews search and filters into a Versionist search query,
 * e.g. `author:admin action:post/update "hello world"`.
 *
 * @param view
 */
export function viewToQuery( view: View ): string {
	const terms: string[] = [];

	for ( const filter of view.filters ?? [] ) {
		const values: string[] = (
			Array.isArray( filter.value ) ? filter.value : [ filter.value ]
		).filter( ( value ) => value !== undefined && value !== '' );

		if ( values.length === 0 ) {
			continue;
		}

		if ( filter.field === 'date' ) {
			const key = DATE_OPERATORS[ filter.operator ];
			if ( key ) {
				// Dates come as ISO strings; the search syntax expects YYYY-MM-DD.
				terms.push(
					`${ key }:${ String( values[ 0 ] ).slice( 0, 10 ) }`
				);
			}
			continue;
		}

		const key = KEYED_FILTERS[ filter.field ];
		if ( key ) {
			values.forEach( ( value ) =>
				terms.push( `${ key }:${ quote( String( value ) ) }` )
			);
		}
	}

	const search = view.search?.trim();
	if ( search ) {
		terms.push( search );
	}

	return terms.join( ' ' );
}
