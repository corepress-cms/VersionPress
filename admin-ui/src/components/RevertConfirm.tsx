import { Button, Flex, Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

import * as api from '../api';
import type { Commit } from '../api';

interface Props {
	method: 'undo' | 'rollback';
	commits: Commit[];
	onCancel: () => void;
}

export default function RevertConfirm( { method, commits, onCancel }: Props ) {
	const [ canRevert, setCanRevert ] = useState< boolean | null >( null );
	const [ isBusy, setIsBusy ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		api.canRevert()
			.then( setCanRevert )
			.catch( ( e ) => setError( api.getErrorMessage( e ) ) );
	}, [] );

	const confirm = () => {
		setIsBusy( true );
		setError( null );
		const request =
			method === 'undo'
				? api.undoCommits( commits.map( ( c ) => c.hash ) )
				: api.rollbackToCommit( commits[ 0 ].hash );

		request
			// The site itself changed (content, settings, maybe menus), so reload all of wp-admin.
			.then( () => window.location.reload() )
			.catch( ( e ) => {
				setError( api.getErrorMessage( e ) );
				setIsBusy( false );
			} );
	};

	const description =
		method === 'undo'
			? sprintf(
					/* translators: %d: number of changes */
					_n(
						'%d selected change will be reverted. Later changes are kept.',
						'%d selected changes will be reverted. Later changes are kept.',
						commits.length,
						'versionist'
					),
					commits.length
				)
			: sprintf(
					/* translators: %s: description of the change to roll back to */
					__(
						'The site will be rolled back to the state right after “%s”. All later changes are reverted in a new change, so this can be undone too.',
						'versionist'
					),
					commits[ 0 ].message
				);

	return (
		<Flex direction="column" gap={ 4 } align="stretch">
			{ canRevert === null && ! error && <Spinner /> }
			{ canRevert === false && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'There are uncommitted changes. Commit or discard them first.',
						'versionist'
					) }
				</Notice>
			) }
			{ canRevert && <p>{ description }</p> }
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Flex justify="flex-end">
				<Button
					variant="tertiary"
					onClick={ onCancel }
					disabled={ isBusy }
				>
					{ __( 'Cancel', 'versionist' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ confirm }
					disabled={ ! canRevert || isBusy }
					isBusy={ isBusy }
				>
					{ method === 'undo'
						? __( 'Undo', 'versionist' )
						: __( 'Roll back', 'versionist' ) }
				</Button>
			</Flex>
		</Flex>
	);
}
