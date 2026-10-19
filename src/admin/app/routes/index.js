/**
 * Route table. The tab strip, the WordPress submenu sync and the legacy-hash
 * redirects are all derived from it.
 *
 * `legacy` is the hash the classic dashboard used for the same screen
 * (admin.php?page=prime_slider_options#prime_slider_active_modules); old
 * links and the WordPress submenu entries still use those.
 */
import { __ } from '@wordpress/i18n';
import config, { sectionFields } from '../utils/config';
import {
	DashboardIcon,
	GridIcon,
	PuzzleIcon,
	SlidersIcon,
	ServerIcon,
	PackageIcon,
	ClockIcon,
	TagIcon,
	CodeIcon,
	KeyIcon,
	RocketIcon,
	GaugeIcon,
} from '../components/ui/Icons';
import Dashboard from '../pages/Dashboard';
import SectionPage from '../pages/SectionPage';
import FeaturesPage from '../pages/FeaturesPage';
import SystemStatusPage from '../pages/SystemStatusPage';
import PanelPage from '../pages/PanelPage';
import GetProPage from '../pages/GetProPage';
import OtherPluginsPage from '../pages/OtherPluginsPage';
import LicensePage from '../pages/pro/LicensePage';
import WhiteLabelPage from '../pages/pro/WhiteLabelPage';
import CustomCodePage from '../pages/pro/CustomCodePage';
import RollbackPage from '../pages/pro/RollbackPage';
import PerformancePage, { ASSET_FIELD } from '../pages/PerformancePage';

/**
 * Native pages for add-on panels. When the add-on hands over the page data
 * (panel.data, from the `prime_slider/admin/react_pages` filter), the app
 * renders that page; otherwise the panel's PHP markup is shown.
 */
const NATIVE_PAGES = {
	prime_slider_license_settings: LicensePage,
	prime_slider_extra_options: WhiteLabelPage,
	prime_slider_custom_code: CustomCodePage,
	prime_slider_rollback_version: RollbackPage,
};

/** Known PHP panels: route path, icon and where they sit in the strip. */
const PANEL_ROUTES = {
	prime_slider_rollback_version: { path: '/rollback', icon: ClockIcon, slot: 'more' },
	prime_slider_extra_options: { path: '/white-label', icon: TagIcon, slot: 'primary' },
	prime_slider_custom_code: { path: '/custom-code', icon: CodeIcon, slot: 'primary' },
	prime_slider_license_settings: { path: '/license', icon: KeyIcon, slot: 'primary' },
};

function sectionRoute( path, legacy, section, label, icon, title, subtitle ) {
	return {
		path,
		legacy,
		label,
		icon,
		slot: 'primary',
		end: true,
		render: () => (
			<SectionPage
				section={ section }
				title={ title || label }
				subtitle={ subtitle }
			/>
		),
	};
}

export function buildRoutes() {
	const list = [
		{
			path: '/',
			legacy: 'prime_slider_welcome',
			label: __( 'Dashboard', 'bdthemes-prime-slider-lite' ),
			icon: DashboardIcon,
			slot: 'primary',
			end: true,
			render: () => <Dashboard />,
		},
		{
			path: '/widgets',
			legacy: 'prime_slider_active_modules',
			label: __( 'Widgets', 'bdthemes-prime-slider-lite' ),
			icon: GridIcon,
			slot: 'primary',
			end: true,
			// Core and 3rd-party widgets in one catalog, as on Element Pack's
			// Widgets tab; 3rd-party rows have their own category chip.
			render: () => (
				<SectionPage
					section={ [ 'prime_slider_active_modules', 'prime_slider_third_party_widget' ] }
					title={ __( 'Widgets', 'bdthemes-prime-slider-lite' ) }
				/>
			),
		},
	];

	if ( sectionFields( 'prime_slider_elementor_extend' ).length ) {
		list.push(
			sectionRoute(
				'/extensions',
				'prime_slider_elementor_extend',
				'prime_slider_elementor_extend',
				__( 'Extensions', 'bdthemes-prime-slider-lite' ),
				PuzzleIcon
			)
		);
	}

	list.push(
		{
			path: '/features',
			legacy: 'prime_slider_other_settings',
			label: __( 'Features', 'bdthemes-prime-slider-lite' ),
			icon: SlidersIcon,
			slot: 'primary',
			end: true,
			render: () => <FeaturesPage />,
		},
	);

	if ( sectionFields( 'prime_slider_other_settings' ).some( ( f ) => f.name === ASSET_FIELD ) ) {
		list.push( {
			path: '/performance',
			legacy: 'prime_slider_performance',
			label: __( 'Performance', 'bdthemes-prime-slider-lite' ),
			icon: GaugeIcon,
			// Right after Features in the tab strip.
			slot: 'primary',
			end: true,
			render: () => <PerformancePage />,
		} );
	}

	list.push(
		{
			path: '/system-status',
			legacy: 'prime_slider_analytics_system_req',
			label: __( 'System Status', 'bdthemes-prime-slider-lite' ),
			icon: ServerIcon,
			slot: 'more',
			end: true,
			render: () => <SystemStatusPage />,
		}
	);

	list.push( {
		path: '/other-plugins',
		legacy: 'prime_slider_other_plugins',
		label: __( 'Other Plugins', 'bdthemes-prime-slider-lite' ),
		icon: PackageIcon,
		slot: 'more',
		end: true,
		render: () => <OtherPluginsPage />,
	} );

	( config.panels || [] ).forEach( ( panel ) => {
		const known = PANEL_ROUTES[ panel.id ] || {
			path: `/${ panel.id.replace( /^prime_slider_/, '' ).replace( /_/g, '-' ) }`,
			icon: PuzzleIcon,
			slot: 'more',
		};
		list.push( {
			path: known.path,
			legacy: panel.id,
			label: panel.title,
			icon: known.icon,
			slot: known.slot,
			end: true,
			render: () => {
				const Native = panel.data ? NATIVE_PAGES[ panel.id ] : null;
				return Native ? <Native panel={ panel } data={ panel.data } /> : <PanelPage panel={ panel } />;
			},
		} );
	} );

	if ( ! config.isPro ) {
		list.push( {
			path: '/get-pro',
			legacy: 'prime_slider_get_pro',
			label: __( 'Get Pro', 'bdthemes-prime-slider-lite' ),
			icon: RocketIcon,
			slot: 'more',
			end: true,
			render: () => <GetProPage />,
		} );
	}

	return list;
}

export const routes = buildRoutes();

/** Route for a classic-dashboard hash such as `prime_slider_active_modules`. */
export function routeForLegacyHash( hash ) {
	const id = String( hash || '' ).replace( /^#/, '' );
	const match = routes.find( ( route ) => route.legacy === id );
	if ( match ) {
		return match.path;
	}
	// 3rd Party Widgets is now a category of the Widgets tab.
	if ( id === 'prime_slider_third_party_widget' ) {
		return '/widgets?category=third_party';
	}
	// Without Pro, the classic dashboard's last tab (#prime_slider_license_settings) was "Get Pro".
	if ( id === 'prime_slider_license_settings' && ! config.isPro ) {
		return '/get-pro';
	}
	return null;
}
