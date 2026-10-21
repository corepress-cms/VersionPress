import { Modal, Notice, Spinner, TabPanel } from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import * as api from '../api';
import type { Commit } from '../api';
import { formatChangeGroup, groupChanges } from '../changes';
import DiffView from './DiffView';

function Overview( { commit }: { commit: Commit } ) {
	if ( commit.isMerge ) {
		return (
			<p>
				{ __(
					'Merged changes from another environment.',
					'versionist'
				) }
			</p>
		);
	}

	const groups = groupChanges( commit );
	if ( groups.length === 0 ) {
		return <p>{ __( 'No changes.', 'versionist' ) }</p>;
	}

	return (
		<ul className="versionist-changes">
			{ groups.map( ( group ) => (
				<li key={ group.key }>{ formatChangeGroup( group ) }</li>
			) ) }
		</ul>
	);
}

function Diff( { hash }: { hash: string } ) {
	const [ diff, setDiff ] = useState< string | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		api.getDiff( hash )
			.then( ( result ) => setDiff( result.diff ) )
			.catch( ( e ) => setError( api.getErrorMessage( e ) ) );
	}, [ hash ] );

	if ( error ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}
	return diff === null ? <Spinner /> : <DiffView diff={ diff } />;
}

export default function CommitDetails( {
	commit,
	onClose,
}: {
	commit: Commit;
	onClose: () => void;
} ) {
	return (
		<Modal title={ commit.message } onRequestClose={ onClose } size="large">
			<p className="versionist-commit-meta">
				{ commit.author.name } ·{ ' ' }
				{ dateI18n(
					getSettings().formats.datetime,
					commit.date,
					undefined
				) }{ ' ' }
				· <code>{ commit.hash.slice( 0, 7 ) }</code>
				{ commit.environment && commit.environment !== '?' && (
					<> · { commit.environment }</>
				) }
			</p>
			<TabPanel
				tabs={ [
					{
						name: 'overview',
						title: __( 'Overview', 'versionist' ),
					},
					{ name: 'diff', title: __( 'Raw diff', 'versionist' ) },
				] }
			>
				{ ( tab ) =>
					tab.name === 'overview' ? (
						<Overview commit={ commit } />
					) : (
						<Diff hash={ commit.hash } />
					)
				}
			</TabPanel>
		</Modal>
	);
}
