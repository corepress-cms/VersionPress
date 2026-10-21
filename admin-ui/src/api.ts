import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const NAMESPACE = '/versionist/v1';

export interface Author {
	name: string;
	email: string;
	avatar: string;
}

export interface Change {
	type: string;
	action: string;
	name: string;
	/** VP-* tags of the change; file changes have none. */
	tags?: Record< string, unknown >;
}

export interface Commit {
	hash: string;
	date: string;
	message: string;
	parentHashes: string[];
	canUndo: boolean;
	canRollback: boolean;
	isEnabled: boolean;
	isInitial: boolean;
	isMerge: boolean;
	environment: string;
	changes: Change[];
	author: Author;
}

export interface CommitsPage {
	commits: Commit[];
	totalItems: number;
	totalPages: number;
}

export interface UpdateStatus {
	update: boolean;
	cleanWorkingDirectory: boolean;
}

/** `[status, path]`, where status is a Git short status like `M`, `A`, `D` or `??`. */
export type FileStatus = [ string, string ];

export interface ListOption {
	label: string;
	value: string;
}

export interface FilterConfig {
	'action:': { content: ListOption[] };
	'scope:': { content: ListOption[] };
	'author:': { content: ListOption[] };
}

/**
 * @param page  Zero-based page number.
 * @param query Query in the Versionist search syntax, e.g. `author:admin post/update`.
 */
export const getCommits = ( page: number, query: string ) =>
	apiFetch< CommitsPage >( {
		path: addQueryArgs( `${ NAMESPACE }/commits`, { page, query } ),
	} );

export const undoCommits = ( commits: string[] ) =>
	apiFetch< boolean >( {
		path: `${ NAMESPACE }/undo`,
		method: 'POST',
		data: { commits },
	} );

export const rollbackToCommit = ( commit: string ) =>
	apiFetch< boolean >( {
		path: `${ NAMESPACE }/rollback`,
		method: 'POST',
		data: { commit },
	} );

export const canRevert = () =>
	apiFetch< boolean >( { path: `${ NAMESPACE }/can-revert` } );

/**
 * @param commit Commit hash; omit to get the uncommitted changes of the working directory.
 */
export const getDiff = ( commit?: string ) =>
	apiFetch< { diff: string } >( {
		path: addQueryArgs( `${ NAMESPACE }/diff`, commit ? { commit } : {} ),
	} );

export const shouldDisplayWelcomePanel = () =>
	apiFetch< boolean >( { path: `${ NAMESPACE }/display-welcome-panel` } );

export const hideWelcomePanel = () =>
	apiFetch( { path: `${ NAMESPACE }/hide-welcome-panel`, method: 'POST' } );

export const getUpdateStatus = ( latestCommit: string, query: string ) =>
	apiFetch< UpdateStatus >( {
		path: addQueryArgs( `${ NAMESPACE }/should-update`, {
			latestCommit,
			query,
		} ),
	} );

export const getGitStatus = () =>
	apiFetch< FileStatus[] >( { path: `${ NAMESPACE }/git-status` } );

export const commitChanges = ( message: string ) =>
	apiFetch< boolean >( {
		path: `${ NAMESPACE }/commit`,
		method: 'POST',
		data: { 'commit-message': message },
	} );

export const discardChanges = () =>
	apiFetch< boolean >( {
		path: `${ NAMESPACE }/discard-changes`,
		method: 'POST',
	} );

export const getFilterConfig = () =>
	apiFetch< FilterConfig >( { path: `${ NAMESPACE }/autocomplete-config` } );

export interface Requirement {
	name: string;
	level: 'critical' | 'warning';
	fulfilled: boolean;
	/** Sanitized HTML. */
	help: string;
}

export interface RequirementsReport {
	requirements: Requirement[];
	canInitialize: boolean;
	isVersioned: boolean;
	isActive: boolean;
}

export const getRequirements = () =>
	apiFetch< RequirementsReport >( { path: `${ NAMESPACE }/requirements` } );

/**
 * Initializes Versionist; can take long on big sites.
 *
 * @param environment Environment name, e.g. `production` or `staging`.
 */
export const initialize = ( environment: string ) =>
	apiFetch< { progress: string[] } >( {
		path: `${ NAMESPACE }/initialize`,
		method: 'POST',
		data: { environment },
	} );

/**
 * Extracts a human-readable message from a rejected `apiFetch()` promise.
 *
 * @param error
 */
export const getErrorMessage = ( error: unknown ): string =>
	error && typeof error === 'object' && 'message' in error
		? String( ( error as { message: unknown } ).message )
		: String( error );
