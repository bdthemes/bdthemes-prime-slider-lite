/**
 * Draft state and saving for one settings section (an option such as
 * prime_slider_active_modules).
 *
 * Saves through the existing `prime_slider_settings_save` AJAX action, which
 * checks the nonce and manage_options, keeps writes inside the plugin's own
 * option names and sanitises every value. The whole section is posted, as the
 * classic form did, including stored values the app does not list.
 */
import { useState, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import config, { sections } from '../utils/config';

/** Draft key of a row: widget names are unique only within their section. */
export function rowKey( section, name ) {
	return `${ section }|${ name }`;
}

function rowsFor( ids ) {
	return ids.flatMap( ( id ) =>
		( ( sections[ id ] && sections[ id ].fields ) || [] ).map( ( field ) => ( {
			...field,
			section: id,
			key: rowKey( id, field.name ),
		} ) )
	);
}

function initialState( rows ) {
	const state = {};
	rows.forEach( ( row ) => {
		state[ row.key ] = !! row.enabled;
	} );
	return state;
}

export async function saveSection( id, values ) {
	const body = new window.FormData();
	body.append( 'action', 'prime_slider_settings_save' );
	body.append( '_wpnonce', config.saveNonce || '' );
	body.append( 'id', id );

	const stored = ( sections[ id ] && sections[ id ].stored ) || {};
	const fields = ( sections[ id ] && sections[ id ].fields ) || [];
	const merged = { ...stored };
	fields.forEach( ( field ) => {
		// A widget that cannot be switched here (a Pro widget without Pro, a
		// 3rd-party widget whose plugin is not active) keeps what is stored,
		// and is not written at all when nothing is stored, as the classic
		// form did.
		const editable = ! field.is_locked && field.plugin_active !== false;
		if ( editable && field.name in values ) {
			merged[ field.name ] = values[ field.name ] ? 'on' : 'off';
		}
	} );
	Object.keys( merged ).forEach( ( name ) => {
		body.append( `${ id }[${ name }]`, merged[ name ] );
	} );

	const response = await window.fetch( config.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body,
	} );

	let json = null;
	try {
		json = await response.json();
	} catch ( e ) {
		json = null;
	}

	if ( ! response.ok || ! json || ! json.success ) {
		throw new Error(
			__(
				'Settings could not be saved. Reload the page and try again.',
				'bdthemes-prime-slider-lite'
			)
		);
	}

	// Later saves of this section start from what is now stored.
	if ( sections[ id ] ) {
		sections[ id ].stored = merged;
		sections[ id ].fields.forEach( ( field ) => {
			field.enabled = merged[ field.name ] === 'on';
		} );
	}

	return merged;
}

/**
 * @param {string|string[]} id One section, or several shown as one list (the
 *                             Widgets tab lists core and 3rd-party widgets).
 *                             Each row saves back to the section it came from.
 */
export function useSection( id ) {
	const idsKey = Array.isArray( id ) ? id.join( ',' ) : String( id );
	const ids = useMemo( () => idsKey.split( ',' ), [ idsKey ] );
	const fields = useMemo( () => rowsFor( ids ), [ ids ] );

	const [ saved, setSaved ] = useState( () => initialState( fields ) );
	const [ draft, setDraft ] = useState( saved );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	const toggle = useCallback( ( key ) => {
		setDraft( ( current ) => ( { ...current, [ key ]: ! current[ key ] } ) );
	}, [] );

	const setAll = useCallback( ( value, scope ) => {
		setDraft( ( current ) => {
			const next = { ...current };
			scope.forEach( ( field ) => {
				if ( ! field.is_locked ) {
					next[ field.key ] = value;
				}
			} );
			return next;
		} );
	}, [] );

	const isDirty = useMemo(
		() => Object.keys( draft ).some( ( key ) => draft[ key ] !== saved[ key ] ),
		[ draft, saved ]
	);

	// Only widgets that can be switched here count: a Pro widget without Pro,
	// or a 3rd-party widget whose plugin is not active, never runs.
	const switchable = useMemo(
		() => fields.filter( ( field ) => ! field.is_locked && field.plugin_active !== false ),
		[ fields ]
	);

	const activeCount = useMemo(
		() => switchable.filter( ( field ) => draft[ field.key ] ).length,
		[ switchable, draft ]
	);

	const save = useCallback( async () => {
		setIsSaving( true );
		setError( null );
		try {
			// Write only the sections that changed, each with its own rows.
			for ( const section of ids ) {
				const rows = fields.filter( ( row ) => row.section === section );
				if ( ! rows.some( ( row ) => draft[ row.key ] !== saved[ row.key ] ) ) {
					continue;
				}
				const values = {};
				rows.forEach( ( row ) => {
					values[ row.name ] = !! draft[ row.key ];
				} );
				await saveSection( section, values );
			}
			setSaved( draft );
		} catch ( e ) {
			setError( e );
			throw e;
		} finally {
			setIsSaving( false );
		}
	}, [ ids, fields, draft, saved ] );

	const reset = useCallback( () => setDraft( saved ), [ saved ] );

	return { fields, switchable, draft, toggle, setAll, isDirty, activeCount, save, reset, isSaving, error };
}
