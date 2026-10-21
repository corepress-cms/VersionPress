import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import * as api from '../api';
import ActivationScreen from './ActivationScreen';
import CommitHistory from './CommitHistory';
import UncommittedChanges from './UncommittedChanges';
import WelcomeNotice from './WelcomeNotice';

export default function App( { isActive }: { isActive: boolean } ) {
	return isActive ? <History /> : <ActivationScreen />;
}

function History() {
	const [ showWelcome, setShowWelcome ] = useState( false );
	const [ isDirty, setIsDirty ] = useState( false );
	// Bumped to make the history reload, e.g. after committing or discarding changes.
	const [ revision, setRevision ] = useState( 0 );
	const reload = useCallback( () => setRevision( ( r ) => r + 1 ), [] );

	useEffect( () => {
		api.shouldDisplayWelcomePanel()
			.then( setShowWelcome )
			.catch( () => setShowWelcome( false ) );
	}, [] );

	const dismissWelcome = () => {
		setShowWelcome( false );
		api.hideWelcomePanel();
	};

	return (
		<div className="versionpress-admin">
			<h1 className="wp-heading-inline">
				{ __( 'VersionPress', 'versionpress' ) }
			</h1>
			{ showWelcome && <WelcomeNotice onDismiss={ dismissWelcome } /> }
			{ isDirty && <UncommittedChanges onChange={ reload } /> }
			<CommitHistory revision={ revision } onDirtyChange={ setIsDirty } />
		</div>
	);
}
