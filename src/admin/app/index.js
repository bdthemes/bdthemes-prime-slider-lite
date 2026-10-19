/**
 * Prime Slider admin dashboard entry point.
 *
 * Mounts the React root into the PHP-rendered #ps-admin-app node.
 */
import { createRoot } from '@wordpress/element';
import App from './app';
import './styles/index.scss';

const node = document.getElementById( 'ps-admin-app' );

if ( node ) {
	createRoot( node ).render( <App /> );
}
