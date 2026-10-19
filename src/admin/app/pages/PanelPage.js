/**
 * Hosts a PHP-rendered panel inside the app: Other Plugins, and the tabs an
 * add-on registers (Prime Slider Pro: Rollback, White Label, Custom CSS & JS,
 * License).
 *
 * PHP prints each panel once, hidden, under #ps-admin-panels. This page moves
 * that very node into its card while it is shown and puts it back when the
 * route changes, so the panel's own markup, inline scripts, event handlers and
 * form state survive navigation; nothing is re-rendered or re-executed.
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PageHeader from '../components/shell/PageHeader';
import EmptyState from '../components/EmptyState';

function findPanel( id ) {
	const holder = document.getElementById( 'ps-admin-panels' );
	if ( ! holder ) {
		return null;
	}
	return Array.from( holder.children ).find( ( node ) => node.getAttribute( 'data-ps-panel' ) === id ) || null;
}

/**
 * Code editors (wp.codeEditor / CodeMirror) and similar widgets measure
 * themselves when they initialise, which happened while the panel was hidden.
 */
function refreshWidgets( node ) {
	node.querySelectorAll( '.CodeMirror' ).forEach( ( el ) => {
		if ( el.CodeMirror && typeof el.CodeMirror.refresh === 'function' ) {
			el.CodeMirror.refresh();
		}
	} );
	if ( window.jQuery ) {
		window.jQuery( node ).trigger( 'shown' );
	}
	window.dispatchEvent( new window.Event( 'resize' ) );
}

export default function PanelPage( { panel } ) {
	const hostRef = useRef( null );
	const [ missing, setMissing ] = useState( false );

	useLayoutEffect( () => {
		const host = hostRef.current;
		const node = findPanel( panel.id );
		const holder = document.getElementById( 'ps-admin-panels' );

		if ( ! host || ! node || ! holder ) {
			setMissing( true );
			return undefined;
		}

		setMissing( false );
		host.appendChild( node );
		window.requestAnimationFrame( () => refreshWidgets( node ) );

		return () => {
			holder.appendChild( node );
		};
	}, [ panel.id ] );

	return (
		<div>
			<PageHeader title={ panel.title } />
			<div className="ps-admin-panel-host bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-6 max-[640px]:p-4 overflow-x-auto">
				<div ref={ hostRef } />
				{ missing ? (
					<EmptyState title={ __( 'This panel could not be loaded. Reload the page and try again.', 'bdthemes-prime-slider-lite' ) } />
				) : null }
			</div>
		</div>
	);
}
