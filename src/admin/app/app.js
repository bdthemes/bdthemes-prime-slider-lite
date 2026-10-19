/**
 * Root component: hash routing (no server rewrite needed inside wp-admin),
 * the persistent layout (app bar + tab strip) and the toast provider.
 */
import { useEffect } from '@wordpress/element';
import {
	HashRouter,
	Routes,
	Route,
	Navigate,
	useLocation,
} from 'react-router-dom';
import AdminLayout from './layouts/AdminLayout';
import { ToastProvider } from './components/ui/Toast';
import { routes, routeForLegacyHash } from './routes';
import { useColorScheme } from './hooks/useColorScheme';

/**
 * Old links and the WordPress submenu use the classic dashboard's hashes
 * (#prime_slider_active_modules). The router sees those as unknown paths;
 * send them to the matching route, and anything else to the dashboard.
 */
function FallbackRoute() {
	const { pathname } = useLocation();
	const target = routeForLegacyHash( pathname.replace( /^\/+/, '' ) ) || '/';
	return <Navigate to={ target } replace />;
}

/**
 * Whether a submenu link opens this dashboard (page=prime_slider_options
 * exactly), not another page whose slug only starts the same way, such as
 * the deals entry (prime_slider_options_deals).
 */
function isDashboardLink( href ) {
	const query = href.split( '#' )[ 0 ].split( '?' )[ 1 ] || '';
	return new window.URLSearchParams( query ).get( 'page' ) === 'prime_slider_options';
}

/**
 * Point the WordPress submenu entries at the app's routes, so clicking one
 * while the dashboard is open navigates inside the app.
 */
function rewriteSubmenuLinks() {
	document
		.querySelectorAll( '#toplevel_page_prime_slider_options .wp-submenu a' )
		.forEach( ( link ) => {
			const href = link.getAttribute( 'href' ) || '';
			const at = href.indexOf( '#' );
			if ( ! isDashboardLink( href ) || at === -1 ) {
				return;
			}
			const route = routeForLegacyHash( href.slice( at + 1 ) );
			if ( route ) {
				link.setAttribute( 'href', `${ href.slice( 0, at ) }#${ route }` );
			}
		} );
}

/**
 * Highlight the matching WordPress submenu entry as the route changes;
 * WordPress cannot track hash routes on its own.
 */
function WpSubmenuSync() {
	const { pathname, search } = useLocation();
	// "3rd Party Widgets" points at /widgets?category=third_party.
	const current = pathname + ( new window.URLSearchParams( search ).get( 'category' ) === 'third_party' ? '?category=third_party' : '' );

	useEffect( rewriteSubmenuLinks, [] );

	useEffect( () => {
		const items = document.querySelectorAll(
			'#toplevel_page_prime_slider_options .wp-submenu li'
		);
		items.forEach( ( li ) => {
			const link = li.querySelector( 'a' );
			if ( ! link ) {
				return;
			}
			const href = link.getAttribute( 'href' ) || '';
			if ( ! isDashboardLink( href ) ) {
				return;
			}
			const at = href.indexOf( '#' );
			const hash = at === -1 ? '' : href.slice( at + 1 );
			const target = ! hash ? '/' : routeForLegacyHash( hash ) || hash;
			const active = target === current;
			li.classList.toggle( 'current', active );
			link.classList.toggle( 'current', active );
			if ( active ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	}, [ current ] );

	return null;
}

export default function App() {
	useColorScheme();

	return (
		<HashRouter>
			<WpSubmenuSync />
			<ToastProvider>
				<AdminLayout>
					<Routes>
						{ routes.map( ( route ) => (
							<Route
								key={ route.path }
								path={ route.path }
								element={ route.render() }
							/>
						) ) }
						<Route path="*" element={ <FallbackRoute /> } />
					</Routes>
				</AdminLayout>
			</ToastProvider>
		</HashRouter>
	);
}
