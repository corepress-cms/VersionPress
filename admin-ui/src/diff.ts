export type DiffLineKind = 'added' | 'removed' | 'context' | 'hunk';

export interface DiffLine {
	kind: DiffLineKind;
	text: string;
}

export interface DiffFile {
	path: string;
	isBinary: boolean;
	lines: DiffLine[];
}

/**
 * Splits `git diff` output into files with classified lines. Header lines
 * (`index`, `---`, `+++`, mode changes) are dropped; the path is taken from `diff --git`.
 *
 * @param diff
 */
export function parseDiff( diff: string ): DiffFile[] {
	const files: DiffFile[] = [];
	let current: DiffFile | null = null;
	let inHunk = false;

	for ( const line of diff.split( '\n' ) ) {
		const header = /^diff --git a\/(.*) b\/(.*)$/.exec( line );
		if ( header ) {
			current = { path: header[ 2 ], isBinary: false, lines: [] };
			files.push( current );
			inHunk = false;
			continue;
		}
		if ( ! current ) {
			continue;
		}
		if ( line.startsWith( 'Binary files ' ) ) {
			current.isBinary = true;
			continue;
		}
		if ( line.startsWith( '@@' ) ) {
			inHunk = true;
			current.lines.push( { kind: 'hunk', text: line } );
			continue;
		}
		if ( ! inHunk ) {
			continue;
		}
		if ( line.startsWith( '+' ) ) {
			current.lines.push( { kind: 'added', text: line } );
		} else if ( line.startsWith( '-' ) ) {
			current.lines.push( { kind: 'removed', text: line } );
		} else if ( line.startsWith( ' ' ) ) {
			current.lines.push( { kind: 'context', text: line } );
		}
	}

	return files;
}
