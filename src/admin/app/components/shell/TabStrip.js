/**
 * Horizontal tab strip attached under the app bar (Element Pack layout).
 *
 * Primary routes render inline; secondary routes (System Status, Other
 * Plugins, Rollback, Get Pro) live in a trailing "More" dropdown. Tabs that
 * don't fit the available width collapse into "More" too (priority-plus
 * pattern): widths are measured from a hidden ghost row and recomputed when
 * the strip is resized.
 */
import {
	useState,
	useEffect,
	useLayoutEffect,
	useRef,
	useCallback,
} from '@wordpress/element';
import { useLocation, useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { routes } from '../../routes';
import { MoreIcon } from '../ui/Icons';

const TAB_BASE =
	'flex items-center gap-[0.45rem] py-[11px] px-[16px] rounded-full text-sm font-bold cursor-pointer whitespace-nowrap transition-all duration-200';
const TAB_ACTIVE =
	'bg-gradient-brand text-white shadow-[0_6px_16px_rgba(252,106,42,.32)]';
const TAB_IDLE =
	'bg-transparent text-[#52525b] dark:text-ink-muted hover:bg-[#f3f5f8] dark:hover:bg-[#1f242d] hover:text-[#18181b] dark:hover:text-ink-text';

// Horizontal gap between tabs (`gap-1` = 4px) — must match the row below.
const TAB_GAP = 4;

function Tab( { active, onClick, icon: TabIcon, label, expanded, controls } ) {
	return (
		<button
			type="button"
			onClick={ onClick }
			aria-current={ active ? 'page' : undefined }
			aria-expanded={ undefined === expanded ? undefined : expanded }
			aria-controls={ controls }
			aria-haspopup={ undefined === expanded ? undefined : 'true' }
			className={ `${ TAB_BASE } ${ active ? TAB_ACTIVE : TAB_IDLE }` }
		>
			<span className="w-[18px] h-[18px] grid place-items-center">
				<TabIcon size={ 17 } />
			</span>
			{ label }
		</button>
	);
}

export default function TabStrip() {
	const { pathname } = useLocation();
	const navigate = useNavigate();
	const [ moreOpen, setMoreOpen ] = useState( false );
	const [ moreLeft, setMoreLeft ] = useState( null );
	const moreRef = useRef( null );
	const moreBtnRef = useRef( null );

	const isActive = ( route ) =>
		route.end ? pathname === route.path : pathname.startsWith( route.path );

	const toItem = ( route ) => ( {
		key: route.path,
		icon: route.icon,
		label: route.label,
		path: route.path,
		active: isActive( route ),
	} );

	const inlineTabs = routes.filter( ( r ) => r.slot === 'primary' ).map( toItem );
	const secondaryItems = routes.filter( ( r ) => r.slot === 'more' ).map( toItem );

	const closeMore = useCallback( () => setMoreOpen( false ), [] );

	useEffect( () => {
		closeMore();
	}, [ pathname, closeMore ] );

	useEffect( () => {
		if ( ! moreOpen ) {
			return undefined;
		}
		const onDocClick = ( event ) => {
			if ( moreRef.current && ! moreRef.current.contains( event.target ) ) {
				closeMore();
			}
		};
		const onKey = ( event ) => {
			if ( event.key === 'Escape' ) {
				closeMore();
			}
		};
		document.addEventListener( 'mousedown', onDocClick );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDocClick );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ moreOpen, closeMore ] );

	// --- Responsive overflow measurement ------------------------------------
	const rowRef = useRef( null );
	const ghostRef = useRef( null );
	const itemWidthsRef = useRef( [] );
	const moreWidthRef = useRef( 0 );
	const [ visibleCount, setVisibleCount ] = useState( inlineTabs.length );

	const recompute = useCallback( () => {
		const row = rowRef.current;
		const widths = itemWidthsRef.current;
		if ( ! row || ! widths.length ) {
			return;
		}
		const styles = window.getComputedStyle( row );
		const available =
			row.clientWidth -
			parseFloat( styles.paddingLeft ) -
			parseFloat( styles.paddingRight );
		const total = widths.length;

		let allWidth = 0;
		widths.forEach( ( w, i ) => {
			allWidth += w + ( i > 0 ? TAB_GAP : 0 );
		} );

		if ( allWidth <= available && secondaryItems.length === 0 ) {
			setVisibleCount( ( prev ) => ( prev === total ? prev : total ) );
			return;
		}

		const budget = available - ( moreWidthRef.current || 0 ) - TAB_GAP;
		let used = 0;
		let count = 0;
		for ( let i = 0; i < total; i++ ) {
			const add = widths[ i ] + ( count > 0 ? TAB_GAP : 0 );
			if ( used + add > budget ) {
				break;
			}
			used += add;
			count += 1;
		}
		setVisibleCount( ( prev ) => ( prev === count ? prev : count ) );
	}, [ secondaryItems.length ] );

	useLayoutEffect( () => {
		const ghost = ghostRef.current;
		if ( ! ghost ) {
			return;
		}
		const kids = Array.from( ghost.children );
		const moreEl = kids.pop();
		itemWidthsRef.current = kids.map( ( el ) => el.getBoundingClientRect().width );
		moreWidthRef.current = moreEl ? moreEl.getBoundingClientRect().width : 0;
		recompute();
	} );

	useEffect( () => {
		const row = rowRef.current;
		if ( ! row || typeof window.ResizeObserver === 'undefined' ) {
			return undefined;
		}
		const observer = new window.ResizeObserver( () => recompute() );
		observer.observe( row );
		return () => observer.disconnect();
	}, [ recompute ] );

	const shownTabs = inlineTabs.slice( 0, visibleCount );
	const moreItems = [ ...inlineTabs.slice( visibleCount ), ...secondaryItems ];
	const moreActive = moreItems.some( ( item ) => item.active ) || moreOpen;

	return (
		<div
			className="hidden min-[901px]:block relative border-t border-[#eef0f3] dark:border-ink-border bg-[#fafbfc] dark:bg-ink-subtle rounded-b-card"
			ref={ moreRef }
		>
			<div aria-hidden="true" className="absolute inset-x-0 top-0 overflow-hidden pointer-events-none">
				<div ref={ ghostRef } className="flex w-max gap-1 py-2 px-4 invisible">
					{ inlineTabs.map( ( item ) => (
						<Tab key={ item.key } active={ false } onClick={ () => {} } icon={ item.icon } label={ item.label } />
					) ) }
					<Tab active={ false } onClick={ () => {} } icon={ MoreIcon } label={ __( 'More', 'bdthemes-prime-slider-lite' ) } />
				</div>
			</div>

			<div ref={ rowRef } className="flex flex-nowrap gap-1 py-2 px-4 overflow-hidden">
				{ shownTabs.map( ( item ) => (
					<Tab
						key={ item.key }
						active={ item.active }
						onClick={ () => navigate( item.path ) }
						icon={ item.icon }
						label={ item.label }
					/>
				) ) }

				{ moreItems.length > 0 ? (
					<span ref={ moreBtnRef } className="inline-flex">
						<Tab
							active={ moreActive }
							onClick={ () => {
								if ( ! moreOpen && moreBtnRef.current && moreRef.current ) {
									const btn = moreBtnRef.current.getBoundingClientRect();
									const wrap = moreRef.current.getBoundingClientRect();
									setMoreLeft( Math.max( 8, Math.min( btn.left - wrap.left, wrap.width - 228 ) ) );
								}
								setMoreOpen( ( value ) => ! value );
							} }
							icon={ MoreIcon }
							label={ __( 'More', 'bdthemes-prime-slider-lite' ) }
							expanded={ moreOpen }
							controls="ps-more-menu"
						/>
					</span>
				) : null }
			</div>

			{ moreOpen ? (
				<div
					id="ps-more-menu"
					style={ moreLeft !== null ? { left: moreLeft } : undefined }
					className={ `absolute top-full mt-1 min-w-[220px] bg-white dark:bg-ink-card border border-[#e6e6ea] dark:border-ink-border rounded-panel shadow-pop z-50 overflow-hidden animate-ps-pop py-1.5 ${
						moreLeft === null ? 'right-4' : ''
					}` }
				>
					{ moreItems.map( ( item ) => {
						const ItemIcon = item.icon;
						return (
							<button
								key={ item.key }
								type="button"
								onClick={ () => navigate( item.path ) }
								aria-current={ item.active ? 'page' : undefined }
								className={ `w-full flex items-center gap-[10px] py-[10px] px-[14px] text-left text-[13.5px] font-bold cursor-pointer transition-colors ${
									item.active
										? 'text-brand bg-[#fff4ee] dark:bg-[#2a1a12]'
										: 'text-[#3f3f46] dark:text-ink-text hover:bg-[#f3f5f8] dark:hover:bg-[#1f242d]'
								}` }
							>
								<span className={ `w-[18px] h-[18px] grid place-items-center ${ item.active ? 'text-brand' : 'text-[#6c6c75] dark:text-ink-muted' }` }>
									<ItemIcon size={ 16 } />
								</span>
								{ item.label }
							</button>
						);
					} ) }
				</div>
			) : null }
		</div>
	);
}
