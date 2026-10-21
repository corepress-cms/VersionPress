import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Flex,
	Notice,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, caution, check } from '@wordpress/icons';

import * as api from '../api';
import type { RequirementsReport } from '../api';

const ENVIRONMENT_PATTERN = /^[a-zA-Z0-9_-]+$/;

/**
 * Shown until VersionPress is initialized: requirements check and the one-time initialization.
 */
export default function ActivationScreen() {
	const [ report, setReport ] = useState< RequirementsReport | null >( null );
	const [ environment, setEnvironment ] = useState( 'default' );
	const [ isInitializing, setIsInitializing ] = useState( false );
	const [ progress, setProgress ] = useState< string[] >( [] );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		api.getRequirements()
			.then( setReport )
			.catch( ( e ) => setError( api.getErrorMessage( e ) ) );
	}, [] );

	const isValidEnvironment = ENVIRONMENT_PATTERN.test( environment );

	const startInitialization = () => {
		setIsInitializing( true );
		setError( null );
		api.initialize( environment )
			.then( ( result ) => {
				setProgress( result.progress );
				// Reload into the history; VersionPress now tracks the site.
				window.location.reload();
			} )
			.catch( ( e ) => {
				setProgress( e?.data?.progress ?? [] );
				setError( api.getErrorMessage( e ) );
				setIsInitializing( false );
			} );
	};

	return (
		<div className="versionpress-admin versionpress-activation">
			<h1>{ __( 'Welcome to VersionPress!', 'versionpress' ) }</h1>
			<p className="versionpress-activation__intro">
				{ __(
					'VersionPress needs a one-time initialization that stores the whole site in a Git repository. This is resource-intensive and might take a while on sites with many posts, comments etc. The site is in maintenance mode until it finishes.',
					'versionpress'
				) }
			</p>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<strong>
						{ __( 'System requirements', 'versionpress' ) }
					</strong>
				</CardHeader>
				<CardBody>
					{ ! report && ! error && <Spinner /> }
					{ report && (
						<ul className="versionpress-requirements">
							{ report.requirements.map( ( requirement ) => (
								<li
									key={ requirement.name }
									className={
										requirement.fulfilled
											? 'is-fulfilled'
											: `is-${ requirement.level }`
									}
								>
									<Icon
										icon={
											requirement.fulfilled
												? check
												: caution
										}
									/>
									<div>
										<strong>{ requirement.name }</strong>
										{ ! requirement.fulfilled && (
											<RawHTML>
												{ requirement.help }
											</RawHTML>
										) }
									</div>
								</li>
							) ) }
						</ul>
					) }
					{ report?.isVersioned && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'This site is already versioned in Git. VersionPress will add some rules to .gitignore and install a merge driver for its own files.',
								'versionpress'
							) }
						</Notice>
					) }
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<strong>
						{ __( 'Before you start', 'versionpress' ) }
					</strong>
				</CardHeader>
				<CardBody>
					<ul className="versionpress-activation__notes">
						<li>
							{ __(
								'VersionPress is a Developer Preview. Some workflows, third-party plugins or hosts might not be fully supported.',
								'versionpress'
							) }
						</li>
						<li>
							{ __(
								'Have a backup. Seriously.',
								'versionpress'
							) }
						</li>
					</ul>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Environment name', 'versionpress' ) }
						help={
							isValidEnvironment
								? __(
										'E.g. production, staging or my-machine; helps to tell this environment apart later. Letters, numbers, - and _ only.',
										'versionpress'
									)
								: __(
										'Please use letters, numbers, - and _ only.',
										'versionpress'
									)
						}
						value={ environment }
						onChange={ setEnvironment }
					/>
				</CardBody>
			</Card>

			<Flex
				justify="flex-start"
				className="versionpress-activation__actions"
			>
				<Button
					variant="primary"
					size="compact"
					onClick={ startInitialization }
					disabled={
						! report?.canInitialize ||
						! isValidEnvironment ||
						isInitializing
					}
					isBusy={ isInitializing }
				>
					{ isInitializing
						? __(
								'Activating… this may take a while',
								'versionpress'
							)
						: __( 'Activate VersionPress', 'versionpress' ) }
				</Button>
			</Flex>

			{ progress.length > 0 && (
				<ol className="versionpress-activation__progress">
					{ progress.map( ( message, i ) => (
						<li key={ i }>{ message }</li>
					) ) }
				</ol>
			) }
		</div>
	);
}
