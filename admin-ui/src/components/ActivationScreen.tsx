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
 * Shown until Versionist is initialized: requirements check and the one-time initialization.
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
				// Reload into the history; Versionist now tracks the site.
				window.location.reload();
			} )
			.catch( ( e ) => {
				setProgress( e?.data?.progress ?? [] );
				setError( api.getErrorMessage( e ) );
				setIsInitializing( false );
			} );
	};

	return (
		<div className="versionist-admin versionist-activation">
			<h1>{ __( 'Welcome to Versionist!', 'versionist' ) }</h1>
			<p className="versionist-activation__intro">
				{ __(
					'Versionist needs a one-time initialization that stores the whole site in a Git repository. This is resource-intensive and might take a while on sites with many posts, comments etc. The site is in maintenance mode until it finishes.',
					'versionist'
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
						{ __( 'System requirements', 'versionist' ) }
					</strong>
				</CardHeader>
				<CardBody>
					{ ! report && ! error && <Spinner /> }
					{ report && (
						<ul className="versionist-requirements">
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
								'This site is already versioned in Git. Versionist will add some rules to .gitignore and install a merge driver for its own files.',
								'versionist'
							) }
						</Notice>
					) }
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<strong>{ __( 'Before you start', 'versionist' ) }</strong>
				</CardHeader>
				<CardBody>
					<ul className="versionist-activation__notes">
						<li>
							{ __(
								'Versionist is a Developer Preview. Some workflows, third-party plugins or hosts might not be fully supported.',
								'versionist'
							) }
						</li>
						<li>
							{ __( 'Have a backup. Seriously.', 'versionist' ) }
						</li>
					</ul>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Environment name', 'versionist' ) }
						help={
							isValidEnvironment
								? __(
										'E.g. production, staging or my-machine; helps to tell this environment apart later. Letters, numbers, - and _ only.',
										'versionist'
									)
								: __(
										'Please use letters, numbers, - and _ only.',
										'versionist'
									)
						}
						value={ environment }
						onChange={ setEnvironment }
					/>
				</CardBody>
			</Card>

			<Flex
				justify="flex-start"
				className="versionist-activation__actions"
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
								'versionist'
							)
						: __( 'Activate Versionist', 'versionist' ) }
				</Button>
			</Flex>

			{ progress.length > 0 && (
				<ol className="versionist-activation__progress">
					{ progress.map( ( message, i ) => (
						<li key={ i }>{ message }</li>
					) ) }
				</ol>
			) }
		</div>
	);
}
