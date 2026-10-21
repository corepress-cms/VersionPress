import { __ } from '@wordpress/i18n';

import { parseDiff } from '../diff';

/**
 * Renders a unified diff (`git diff` output) file by file.
 *
 * @param root0
 * @param root0.diff
 */
export default function DiffView( { diff }: { diff: string } ) {
	const files = parseDiff( diff );

	if ( files.length === 0 ) {
		return <p>{ __( 'No file changes.', 'versionist' ) }</p>;
	}

	return (
		<div className="versionist-diff">
			{ files.map( ( file ) => (
				<section key={ file.path } className="versionist-diff__file">
					<h3>{ file.path }</h3>
					{ file.isBinary ? (
						<p>{ __( 'Binary file changed.', 'versionist' ) }</p>
					) : (
						<pre>
							{ file.lines.map( ( line, i ) => (
								<span
									key={ i }
									className={ `is-${ line.kind }` }
								>
									{ line.text }
									{ '\n' }
								</span>
							) ) }
						</pre>
					) }
				</section>
			) ) }
		</div>
	);
}
