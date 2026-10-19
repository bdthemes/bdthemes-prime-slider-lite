/**
 * Bottom navigation for narrow screens (≤900px), where the tab strip is
 * hidden: the first four primary tabs plus a "More" sheet with the rest.
 */
import { useState, useEffect } from '@wordpress/element';
import { useLocation, useNavigate } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { routes } from '../../routes';
import { MoreIcon, XIcon } from '../ui/Icons';

export default function MobileNav() {
	const { pathname } = useLocation();
	const navigate = useNavigate();
	const [ open, setOpen ] = useState( false );

	useEffect( () => setOpen( false ), [ pathname ] );

	const primary = routes.filter( ( r ) => r.slot === 'primary' );
	const bar = primary.slice( 0, 4 );
	const rest = [ ...primary.slice( 4 ), ...routes.filter( ( r ) => r.slot === 'more' ) ];
	const isActive = ( route ) => pathname === route.path;

	const item = ( route ) => {
		const Icon = route.icon;
		const active = isActive( route );
		return (
			<button
				key={ route.path }
				type="button"
				onClick={ () => navigate( route.path ) }
				className={ `flex-1 flex flex-col items-center gap-1 py-2 text-[11px] font-bold cursor-pointer ${
					active ? 'text-brand' : 'text-[#71717a] dark:text-ink-muted'
				}` }
			>
				<Icon size={ 20 } />
				{ route.label }
			</button>
		);
	};

	return (
		<div className="hidden max-[900px]:block">
			{ open ? (
				<div className="fixed inset-x-3 bottom-[calc(84px+env(safe-area-inset-bottom))] z-50 bg-white dark:bg-ink-card border border-[#e6e6ea] dark:border-ink-border rounded-panel shadow-pop py-1.5 animate-ps-pop">
					{ rest.map( ( route ) => {
						const Icon = route.icon;
						return (
							<button
								key={ route.path }
								type="button"
								onClick={ () => navigate( route.path ) }
								className={ `w-full flex items-center gap-[10px] py-[11px] px-[16px] text-left text-[14px] font-bold cursor-pointer ${
									isActive( route ) ? 'text-brand' : 'text-[#3f3f46] dark:text-ink-text'
								}` }
							>
								<Icon size={ 17 } />
								{ route.label }
							</button>
						);
					} ) }
				</div>
			) : null }
			<nav className="fixed left-0 right-0 bottom-0 z-40 flex bg-white dark:bg-ink-card border-t border-[#e6e6ea] dark:border-ink-border pb-[env(safe-area-inset-bottom)] px-2">
				{ bar.map( item ) }
				{ rest.length ? (
					<button
						type="button"
						onClick={ () => setOpen( ( v ) => ! v ) }
						className={ `flex-1 flex flex-col items-center gap-1 py-2 text-[11px] font-bold cursor-pointer ${
							open || rest.some( isActive ) ? 'text-brand' : 'text-[#71717a] dark:text-ink-muted'
						}` }
					>
						{ open ? <XIcon size={ 20 } /> : <MoreIcon size={ 20 } /> }
						{ __( 'More', 'bdthemes-prime-slider-lite' ) }
					</button>
				) : null }
			</nav>
		</div>
	);
}
