import { Button, Notice } from '@wordpress/components';
import { DataViews } from '@wordpress/dataviews';
import type { Action, Field, View } from '@wordpress/dataviews';
import { dateI18n, getSettings, humanTimeDiff } from '@wordpress/date';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { backup, undo } from '@wordpress/icons';

import * as api from '../api';
import type { Commit, ListOption } from '../api';
import { viewToQuery } from '../query';
import CommitDetails from './CommitDetails';
import RevertConfirm from './RevertConfirm';

/** Fixed on the server, see `VersionPressApi::getCommits()`. */
const PER_PAGE = 25;
const UPDATE_CHECK_INTERVAL = 15_000;

const DEFAULT_VIEW: View = {
	type: 'table',
	page: 1,
	perPage: PER_PAGE,
	search: '',
	filters: [],
	titleField: 'message',
	fields: [ 'author', 'date' ],
	layout: { density: 'balanced' },
};

const listElements = ( options?: ListOption[] ) =>
	( options ?? [] ).map( ( { label, value } ) => ( { label, value } ) );

interface Props {
	/** Changes when the history has to be reloaded. */
	revision: number;
	onDirtyChange: ( isDirty: boolean ) => void;
}

export default function CommitHistory( { revision, onDirtyChange }: Props ) {
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );
	const [ commits, setCommits ] = useState< Commit[] >( [] );
	const [ pagination, setPagination ] = useState( {
		totalItems: 0,
		totalPages: 0,
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ hasNewerCommits, setHasNewerCommits ] = useState( false );
	const [ filterConfig, setFilterConfig ] = useState< api.FilterConfig >();
	const [ detailsOf, setDetailsOf ] = useState< Commit | null >( null );
	const [ refreshCount, setRefreshCount ] = useState( 0 );

	const query = viewToQuery( view );
	const page = ( view.page ?? 1 ) - 1;

	useEffect( () => {
		api.getFilterConfig()
			.then( setFilterConfig )
			.catch( () => undefined );
	}, [] );

	useEffect( () => {
		let isCurrent = true;
		setIsLoading( true );
		api.getCommits( page, query )
			.then( ( result ) => {
				if ( ! isCurrent ) {
					return;
				}
				setCommits( result.commits );
				setPagination( {
					totalItems: result.totalItems,
					totalPages: result.totalPages,
				} );
				setError( null );
				setHasNewerCommits( false );
			} )
			.catch( ( e ) => {
				if ( ! isCurrent ) {
					return;
				}
				setCommits( [] );
				setPagination( { totalItems: 0, totalPages: 0 } );
				setError( api.getErrorMessage( e ) );
			} )
			.finally( () => isCurrent && setIsLoading( false ) );

		return () => {
			isCurrent = false;
		};
	}, [ page, query, revision, refreshCount ] );

	// Polls for commits made elsewhere (other tabs, WP-CLI) and for uncommitted changes.
	const latestCommit = commits[ 0 ]?.hash;
	useEffect( () => {
		if ( ! latestCommit ) {
			return;
		}
		const check = () =>
			api
				.getUpdateStatus( latestCommit, query )
				.then( ( status ) => {
					setHasNewerCommits( page === 0 && status.update );
					onDirtyChange( ! status.cleanWorkingDirectory );
				} )
				.catch( () => undefined );
		check();
		const interval = window.setInterval( check, UPDATE_CHECK_INTERVAL );
		return () => window.clearInterval( interval );
	}, [ latestCommit, query, page, onDirtyChange ] );

	const fields: Field< Commit >[] = useMemo(
		() => [
			{
				id: 'message',
				label: __( 'Change', 'versionpress' ),
				enableHiding: false,
				enableGlobalSearch: true,
				render: ( { item } ) => (
					<span
						className={
							item.isEnabled
								? 'versionpress-message'
								: 'versionpress-message is-disabled'
						}
					>
						{ item.message }
					</span>
				),
			},
			{
				id: 'author',
				label: __( 'Author', 'versionpress' ),
				getValue: ( { item } ) => item.author.name,
				render: ( { item } ) => (
					<span className="versionpress-author">
						<img
							src={ item.author.avatar }
							alt=""
							width={ 24 }
							height={ 24 }
						/>
						{ item.author.name }
					</span>
				),
				elements: listElements( filterConfig?.[ 'author:' ]?.content ),
				filterBy: { operators: [ 'isAny' ] },
			},
			{
				id: 'date',
				label: __( 'Date', 'versionpress' ),
				type: 'datetime',
				getValue: ( { item } ) => item.date,
				render: ( { item } ) => (
					<time
						dateTime={ item.date }
						title={ dateI18n(
							getSettings().formats.datetime,
							item.date,
							undefined
						) }
					>
						{ humanTimeDiff( item.date ) }
					</time>
				),
				filterBy: { operators: [ 'before', 'after', 'on' ] },
			},
			{
				id: 'environment',
				label: __( 'Environment', 'versionpress' ),
				getValue: ( { item } ) => item.environment,
			},
			{
				id: 'action',
				label: __( 'Action', 'versionpress' ),
				getValue: ( { item } ) =>
					item.changes.map( ( c ) => `${ c.type }/${ c.action }` ),
				elements: listElements( filterConfig?.[ 'action:' ]?.content ),
				filterBy: { operators: [ 'isAny' ] },
			},
			{
				id: 'scope',
				label: __( 'Scope', 'versionpress' ),
				getValue: ( { item } ) => item.changes.map( ( c ) => c.type ),
				elements: listElements( filterConfig?.[ 'scope:' ]?.content ),
				filterBy: { operators: [ 'isAny' ] },
			},
		],
		[ filterConfig ]
	);

	const actions: Action< Commit >[] = useMemo(
		() => [
			{
				id: 'undo',
				label: __( 'Undo', 'versionpress' ),
				icon: undo,
				supportsBulk: true,
				isEligible: ( item ) => item.canUndo,
				modalHeader: __( 'Undo changes', 'versionpress' ),
				RenderModal: ( { items, closeModal } ) => (
					<RevertConfirm
						method="undo"
						commits={ items }
						onCancel={ () => closeModal?.() }
					/>
				),
			},
			{
				id: 'rollback',
				label: __( 'Roll back to this', 'versionpress' ),
				icon: backup,
				isEligible: ( item ) => item.canRollback,
				modalHeader: __( 'Roll back the site', 'versionpress' ),
				RenderModal: ( { items, closeModal } ) => (
					<RevertConfirm
						method="rollback"
						commits={ items }
						onCancel={ () => closeModal?.() }
					/>
				),
			},
		],
		[]
	);

	return (
		<>
			{ hasNewerCommits && (
				<Notice status="info" isDismissible={ false }>
					{ __( 'There are newer changes.', 'versionpress' ) }{ ' ' }
					<Button
						variant="link"
						onClick={ () => setRefreshCount( ( c ) => c + 1 ) }
					>
						{ __( 'Refresh', 'versionpress' ) }
					</Button>
				</Notice>
			) }
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<DataViews< Commit >
				data={ commits }
				fields={ fields }
				view={ view }
				onChangeView={ setView }
				actions={ actions }
				getItemId={ ( item ) => item.hash }
				isLoading={ isLoading }
				paginationInfo={ pagination }
				onClickItem={ setDetailsOf }
				isItemClickable={ () => true }
				searchLabel={ __( 'Search history', 'versionpress' ) }
				defaultLayouts={ { table: {} } }
				config={ { perPageSizes: [ PER_PAGE ] } }
			/>
			{ detailsOf && (
				<CommitDetails
					commit={ detailsOf }
					onClose={ () => setDetailsOf( null ) }
				/>
			) }
		</>
	);
}
