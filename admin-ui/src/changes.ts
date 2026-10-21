import { __, _n, sprintf } from '@wordpress/i18n';

import type { Change, Commit } from './api';

export interface ChangeGroup {
	key: string;
	/** E.g. "Updated post" or "Created comment". */
	label: string;
	/** Entity this group belongs to, e.g. the post of a group of post meta changes. */
	context?: string;
	names: string[];
	count: number;
}

/**
 * Converts an action like `update` to `updated`. Actions are defined by plugin definitions
 * (`actions.yml`) in English, like the commit messages generated from them.
 *
 * @param verb
 */
export function verbToPastTense( verb: string ): string {
	const base = verb.split( '-' )[ 0 ];
	if ( /[^aeiou]y$/.test( base ) ) {
		return base.slice( 0, -1 ) + 'ied';
	}
	return base + ( base.endsWith( 'e' ) ? 'd' : 'ed' );
}

const capitalize = ( word: string ) =>
	word.charAt( 0 ).toUpperCase() + word.slice( 1 );

/** Tag used to group changes of a type into one line, with the entity it refers to. */
const GROUP_BY_TAG: Record< string, { tag: string; context: string } > = {
	usermeta: { tag: 'VP-User-Login', context: 'user' },
	postmeta: { tag: 'VP-Post-Title', context: 'post' },
	comment: { tag: 'VP-Comment-PostTitle', context: 'post' },
	post: { tag: 'VP-Post-Type', context: '' },
};

const tag = ( change: Change, name: string ): string => {
	const value = change.tags?.[ name ];
	return typeof value === 'string' ? value : '';
};

function describeName( change: Change ): string {
	if ( change.type === 'comment' ) {
		return tag( change, 'VP-Comment-Author' ) || change.name;
	}
	if ( change.type === 'post' ) {
		return tag( change, 'VP-Post-Title' ) || change.name;
	}
	if (
		change.type === 'versionpress' &&
		( change.action === 'undo' || change.action === 'rollback' )
	) {
		const details = change.tags?.[ 'VP-Commit-Details' ] as
			{ message?: string } | undefined;
		return details?.message ?? change.name;
	}
	return change.name ?? '';
}

function getLabel( scope: string, action: string, type: string ): string {
	if ( scope === 'versionpress' && action === 'undo' ) {
		return __( 'Reverted change', 'versionist' );
	}
	if ( scope === 'versionpress' && action === 'rollback' ) {
		return __( 'Rolled back to', 'versionist' );
	}
	return `${ capitalize( verbToPastTense( action ) ) } ${ type }`;
}

/** Names shown before a group is summarized as "… and N more". */
const MAX_NAMES = 5;

/**
 * Groups the changes of a commit into lines like "Updated post: Hello world, About".
 *
 * @param commit
 */
export function groupChanges( commit: Commit ): ChangeGroup[] {
	const groups = new Map< string, ChangeGroup >();

	for ( const change of commit.changes ) {
		const grouping = GROUP_BY_TAG[ change.type ];
		const groupValue = grouping ? tag( change, grouping.tag ) : '';
		const key = [ change.type, change.action, groupValue ].join( '|' );

		let group = groups.get( key );
		if ( ! group ) {
			const type =
				change.type === 'post' && groupValue ? groupValue : change.type;
			group = {
				key,
				label: getLabel( change.type, change.action, type ),
				context:
					grouping?.context && groupValue
						? `${ grouping.context } ${ groupValue }`
						: undefined,
				names: [],
				count: 0,
			};
			groups.set( key, group );
		}

		group.count++;
		const name = describeName( change );
		if ( name && ! group.names.includes( name ) ) {
			group.names.push( name );
		}
	}

	return [ ...groups.values() ];
}

/**
 * One-line text for a change group, e.g. "Updated post (2): Hello world, About".
 *
 * @param group
 */
export function formatChangeGroup( group: ChangeGroup ): string {
	const label =
		group.count > 1
			? sprintf(
					/* translators: 1: action and entity type, e.g. "Updated post"; 2: number of changes */
					__( '%1$s (%2$d)', 'versionist' ),
					group.label,
					group.count
				)
			: group.label;

	const withContext = group.context
		? sprintf(
				/* translators: 1: action and entity type; 2: entity the change belongs to, e.g. "post Hello world" */
				__( '%1$s for %2$s', 'versionist' ),
				label,
				group.context
			)
		: label;

	if ( group.names.length === 0 ) {
		return withContext;
	}

	const hidden = group.names.length - MAX_NAMES;
	const names =
		hidden > 0
			? sprintf(
					/* translators: 1: comma-separated names; 2: number of further names not shown */
					_n(
						'%1$s and %2$d more',
						'%1$s and %2$d more',
						hidden,
						'versionist'
					),
					group.names.slice( 0, MAX_NAMES ).join( ', ' ),
					hidden
				)
			: group.names.join( ', ' );

	return sprintf(
		/* translators: 1: description of the change; 2: names of changed items */
		__( '%1$s: %2$s', 'versionist' ),
		withContext,
		names
	);
}
