import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function WelcomeNotice( {
	onDismiss,
}: {
	onDismiss: () => void;
} ) {
	return (
		<Notice
			status="info"
			onRemove={ onDismiss }
			className="versionpress-welcome"
		>
			<p>
				<strong>{ __( 'Welcome!', 'versionpress' ) }</strong>{ ' ' }
				{ createInterpolateElement(
					__(
						'Below is the history of this site which will grow as changes are made to it. You can <undo>Undo</undo> specific changes or <rollback>Roll back</rollback> the site entirely to a previous state.',
						'versionpress'
					),
					{ undo: <strong />, rollback: <strong /> }
				) }
			</p>
		</Notice>
	);
}
