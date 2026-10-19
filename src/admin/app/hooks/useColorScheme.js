/**
 * Dark-mode-ready color scheme hook.
 *
 * Follows the system/browser `prefers-color-scheme` by default — including
 * live OS theme changes. The header toggle stores an explicit override in
 * localStorage; toggling back to the value that matches the system clears
 * the override so the app returns to following the OS.
 *
 * Reflected as a data-attribute on the app root so the CSS can theme via
 * [data-ps-theme].
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

const STORAGE_KEY = 'primeSliderAdminTheme';

function getSystemTheme() {
	return window.matchMedia &&
		window.matchMedia( '(prefers-color-scheme: dark)' ).matches
		? 'dark'
		: 'light';
}

function getStoredOverride() {
	const stored = window.localStorage.getItem( STORAGE_KEY );
	return stored === 'light' || stored === 'dark' ? stored : null;
}

export function useColorScheme() {
	const [ override, setOverride ] = useState( getStoredOverride );
	const [ system, setSystem ] = useState( getSystemTheme );

	// Track live OS/browser theme changes while no explicit override is set.
	useEffect( () => {
		if ( ! window.matchMedia ) {
			return undefined;
		}
		const media = window.matchMedia( '(prefers-color-scheme: dark)' );
		const onChange = ( event ) =>
			setSystem( event.matches ? 'dark' : 'light' );
		if ( media.addEventListener ) {
			media.addEventListener( 'change', onChange );
			return () => media.removeEventListener( 'change', onChange );
		}
		// Older Safari fallback.
		media.addListener( onChange );
		return () => media.removeListener( onChange );
	}, [] );

	const theme = override || system;

	useEffect( () => {
		const root = document.getElementById( 'ps-admin-app' );
		if ( root ) {
			root.setAttribute( 'data-ps-theme', theme );
		}
		// Mirror onto <body> so the wp-admin canvas around/below the app
		// (visible past the page content) can be painted per theme too.
		document.body.classList.toggle( 'ps-theme-dark', theme === 'dark' );
	}, [ theme ] );

	const toggle = useCallback( () => {
		const next = theme === 'dark' ? 'light' : 'dark';
		if ( next === getSystemTheme() ) {
			// Matches the OS again — drop the override and follow the system.
			window.localStorage.removeItem( STORAGE_KEY );
			setOverride( null );
			setSystem( getSystemTheme() );
			return;
		}
		window.localStorage.setItem( STORAGE_KEY, next );
		setOverride( next );
	}, [ theme ] );

	return { theme, toggle };
}
