import {
	Button,
	Flex,
	Card,
	CardBody,
	CardHeader,
	Modal,
	Notice,
	TextareaControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

import * as api from '../api';
import type { FileStatus } from '../api';
import DiffView from './DiffView';

/**
 * Files changed outside of VersionPress (e.g. edited via FTP), which can be committed or discarded.
 *
 * @param root0
 * @param root0.onChange Called after the changes were committed or discarded.
 */
export default function UncommittedChanges( {
	onChange,
}: {
	onChange: () => void;
} ) {
	const [ files, setFiles ] = useState< FileStatus[] >( [] );
	const [ message, setMessage ] = useState( '' );
	const [ diff, setDiff ] = useState< string | null >( null );
	const [ isConfirmingDiscard, setIsConfirmingDiscard ] = useState( false );
	const [ isBusy, setIsBusy ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		api.getGitStatus()
			.then( setFiles )
			.catch( ( e ) => setError( api.getErrorMessage( e ) ) );
	}, [] );

	const run = ( request: () => Promise< unknown > ) => {
		setIsBusy( true );
		setError( null );
		request()
			.then( onChange )
			.catch( ( e ) => setError( api.getErrorMessage( e ) ) )
			.finally( () => setIsBusy( false ) );
	};

	return (
		<Card className="versionpress-uncommitted">
			<CardHeader>
				<strong>
					{ sprintf(
						/* translators: %d: number of changed files */
						_n(
							'%d file has uncommitted changes',
							'%d files have uncommitted changes',
							files.length,
							'versionpress'
						),
						files.length
					) }
				</strong>
				<Button
					variant="link"
					onClick={ () =>
						api
							.getDiff()
							.then( ( result ) => setDiff( result.diff ) )
							.catch( ( e ) =>
								setError( api.getErrorMessage( e ) )
							)
					}
				>
					{ __( 'Show changes', 'versionpress' ) }
				</Button>
			</CardHeader>
			<CardBody>
				<Flex direction="column" gap={ 3 } align="stretch">
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
					<ul className="versionpress-uncommitted__files">
						{ files.map( ( [ status, path ] ) => (
							<li key={ path }>
								<code>{ status }</code> { path }
							</li>
						) ) }
					</ul>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Commit message', 'versionpress' ) }
						value={ message }
						onChange={ setMessage }
						rows={ 2 }
					/>
					<Flex justify="flex-start">
						<Button
							variant="primary"
							disabled={ ! message.trim() || isBusy }
							isBusy={ isBusy }
							onClick={ () =>
								run( () => api.commitChanges( message ) )
							}
						>
							{ __( 'Commit changes', 'versionpress' ) }
						</Button>
						<Button
							variant="secondary"
							isDestructive
							disabled={ isBusy }
							onClick={ () => setIsConfirmingDiscard( true ) }
						>
							{ __( 'Discard changes', 'versionpress' ) }
						</Button>
					</Flex>
				</Flex>
			</CardBody>
			{ diff !== null && (
				<Modal
					title={ __( 'Uncommitted changes', 'versionpress' ) }
					onRequestClose={ () => setDiff( null ) }
					size="large"
				>
					<DiffView diff={ diff } />
				</Modal>
			) }
			{ isConfirmingDiscard && (
				<Modal
					title={ __( 'Discard changes?', 'versionpress' ) }
					onRequestClose={ () => setIsConfirmingDiscard( false ) }
				>
					<p>
						{ __(
							'The changed files will be restored to their last committed state. This cannot be undone.',
							'versionpress'
						) }
					</p>
					<Flex justify="flex-end">
						<Button
							variant="tertiary"
							onClick={ () => setIsConfirmingDiscard( false ) }
						>
							{ __( 'Cancel', 'versionpress' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ () => {
								setIsConfirmingDiscard( false );
								run( api.discardChanges );
							} }
						>
							{ __( 'Discard changes', 'versionpress' ) }
						</Button>
					</Flex>
				</Modal>
			) }
		</Card>
	);
}
