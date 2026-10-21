import { createRoot } from '@wordpress/element';

import App from './components/App';
import './style.scss';

const root = document.getElementById( 'versionpress-admin' );
if ( root ) {
	createRoot( root ).render(
		<App isActive={ root.dataset.active === '1' } />
	);
}
