/**
 * Sticky dark save bar shown while a draft is dirty (mockup lines 1106–1119).
 * While it shows, leaving the page asks first so the draft is not lost.
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CheckIcon } from './Icons';

/**
 * Ask before a navigation would drop unsaved changes.
 *
 * - Reload, close, or a link to another wp-admin screen: `beforeunload`.
 * - Tabs, menus, command palette: every route change goes through
 *   `history.pushState` (HashRouter), so that is wrapped.
 * - The wp-admin sidebar entries are plain hash links into this screen,
 *   which change the route without calling pushState.
 *
 * @param {boolean} active Whether there are unsaved changes.
 */
function useLeaveGuard( active ) {
	useEffect( () => {
		if ( ! active ) {
			return undefined;
		}

		const confirmLeave = () =>
			// eslint-disable-next-line no-alert
			window.confirm(
				__(
					'You have unsaved changes. Leave without saving?',
					'bdthemes-prime-slider-lite'
				)
			);

		const onBeforeUnload = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};

		const { history } = window;
		const pushState = history.pushState;
		history.pushState = function ( ...args ) {
			if ( ! confirmLeave() ) {
				return undefined;
			}
			return pushState.apply( this, args );
		};

		const onClick = ( event ) => {
			const link =
				event.target.closest && event.target.closest( 'a[href*="#/"]' );
			// Links inside the app route through pushState above.
			if ( ! link || link.closest( '#ps-admin-app' ) ) {
				return;
			}
			const url = new URL( link.href, window.location.href );
			if (
				url.pathname !== window.location.pathname ||
				url.search !== window.location.search ||
				url.hash === window.location.hash
			) {
				return;
			}
			if ( ! confirmLeave() ) {
				event.preventDefault();
				event.stopPropagation();
			}
		};

		window.addEventListener( 'beforeunload', onBeforeUnload );
		document.addEventListener( 'click', onClick, true );

		return () => {
			window.removeEventListener( 'beforeunload', onBeforeUnload );
			document.removeEventListener( 'click', onClick, true );
			history.pushState = pushState;
		};
	}, [ active ] );
}

export default function SaveBar( {
	visible = false,
	saving = false,
	onSave,
	onReset,
	message,
	saveLabel,
} ) {
	useLeaveGuard( visible );

	if ( ! visible ) {
		return null;
	}

	// At ≤900px the fixed MobileNav (73px + safe area) covers the bottom of the
	// viewport, so the bar sticks above it instead of underneath.
	return (
		<div className="sticky bottom-5 max-[900px]:bottom-[calc(84px+env(safe-area-inset-bottom))] mt-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 bg-[#0f1115] text-white rounded-[14px] py-[14px] px-[18px] shadow-[0_18px_40px_-12px_rgba(0,0,0,.5)] z-30 animate-ps-fade-up">
			<div className="flex items-center gap-[11px] text-[13.5px] font-semibold">
				<span className="w-[9px] h-[9px] rounded-full bg-[#f59e0b] shadow-[0_0_0_4px_rgba(245,158,11,.25)]" />
				{ message ||
					__( 'You have unsaved changes', 'bdthemes-prime-slider-lite' ) }
			</div>
			<div className="flex gap-[10px]">
				{ onReset ? (
					<button
						type="button"
						onClick={ onReset }
						disabled={ saving }
						className="bg-[rgba(255,255,255,.1)] border border-[rgba(255,255,255,.18)] text-white py-[10px] px-[18px] rounded-full text-[13px] font-bold cursor-pointer disabled:opacity-50"
					>
						{ __( 'Reset', 'bdthemes-prime-slider-lite' ) }
					</button>
				) : null }
				<button
					type="button"
					onClick={ onSave }
					disabled={ saving }
					className="flex items-center gap-[7px] bg-gradient-brand text-white py-[10px] px-[22px] rounded-full text-[13px] font-extrabold cursor-pointer shadow-[0_5px_16px_rgba(252,106,42,.4)] disabled:opacity-60"
				>
					<CheckIcon size={ 14 } strokeWidth={ 2.4 } />
					{ saving
						? __( 'Saving…', 'bdthemes-prime-slider-lite' )
						: saveLabel ||
						  __( 'Save changes', 'bdthemes-prime-slider-lite' ) }
				</button>
			</div>
		</div>
	);
}
