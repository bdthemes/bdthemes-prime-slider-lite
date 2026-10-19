/**
 * Inline stroke icons copied from the approved dashboard mockup
 * (Element Pack dashboard) for pixel parity — Lucide-style,
 * 24 viewbox, stroke-width 2.
 */

function base( { size = 18, strokeWidth = 2, children, ...rest } ) {
	return (
		<svg
			width={ size }
			height={ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth={ strokeWidth }
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			{ ...rest }
		>
			{ children }
		</svg>
	);
}

export const DashboardIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="3" y="3" width="7" height="9" rx="1.5" />
				<rect x="14" y="3" width="7" height="5" rx="1.5" />
				<rect x="14" y="12" width="7" height="9" rx="1.5" />
				<rect x="3" y="16" width="7" height="5" rx="1.5" />
			</>
		),
	} );

export const GridIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="3" y="3" width="7" height="7" rx="1.5" />
				<rect x="14" y="3" width="7" height="7" rx="1.5" />
				<rect x="14" y="14" width="7" height="7" rx="1.5" />
				<rect x="3" y="14" width="7" height="7" rx="1.5" />
			</>
		),
	} );


export const PuzzleIcon = ( props ) =>
	base( {
		...props,
		children: (
			<path d="M19.5 12.5a2 2 0 1 0-3-2.6V7a1 1 0 0 0-1-1h-2.9a2 2 0 1 0-3.6 0H6a1 1 0 0 0-1 1v2.9a2 2 0 1 0 0 3.6V18a1 1 0 0 0 1 1h2.9a2 2 0 1 1 3.6 0h2.9a1 1 0 0 0 1-1v-2.9a2 2 0 0 0 2.6-2.1Z" />
		),
	} );

export const PackageIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
				<path d="m3.3 7 8.7 5 8.7-5" />
				<path d="M12 22V12" />
			</>
		),
	} );

export const SlidersIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<line x1="4" y1="21" x2="4" y2="14" />
				<line x1="4" y1="10" x2="4" y2="3" />
				<line x1="12" y1="21" x2="12" y2="12" />
				<line x1="12" y1="8" x2="12" y2="3" />
				<line x1="20" y1="21" x2="20" y2="16" />
				<line x1="20" y1="12" x2="20" y2="3" />
				<line x1="1.5" y1="14" x2="6.5" y2="14" />
				<line x1="9.5" y1="8" x2="14.5" y2="8" />
				<line x1="17.5" y1="16" x2="22.5" y2="16" />
			</>
		),
	} );

export const KeyIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="m21 2-9.6 9.6" />
				<circle cx="7.5" cy="15.5" r="5.5" />
				<path d="m15.5 7.5 3 3" />
			</>
		),
	} );

export const TagIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="m20.6 13.4-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z" />
				<circle cx="7" cy="7" r="1.4" fill="currentColor" />
			</>
		),
	} );

export const SearchIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle cx="11" cy="11" r="8" />
				<line x1="21" y1="21" x2="16.65" y2="16.65" />
			</>
		),
	} );

export const SunIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle cx="12" cy="12" r="4.5" />
				<line x1="12" y1="2" x2="12" y2="5" />
				<line x1="12" y1="19" x2="12" y2="22" />
				<line x1="4.2" y1="4.2" x2="6.3" y2="6.3" />
				<line x1="17.7" y1="17.7" x2="19.8" y2="19.8" />
				<line x1="2" y1="12" x2="5" y2="12" />
				<line x1="19" y1="12" x2="22" y2="12" />
				<line x1="4.2" y1="19.8" x2="6.3" y2="17.7" />
				<line x1="17.7" y1="6.3" x2="19.8" y2="4.2" />
			</>
		),
	} );

export const MoonIcon = ( props ) =>
	base( {
		...props,
		children: <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />,
	} );

export const BookIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
				<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
			</>
		),
	} );

export const ZapIcon = ( props ) =>
	base( {
		...props,
		strokeWidth: props?.strokeWidth || 2.2,
		children: <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />,
	} );

export const CheckIcon = ( props ) =>
	base( {
		...props,
		strokeWidth: props?.strokeWidth || 3,
		children: <polyline points="20 6 9 17 4 12" />,
	} );

export const XIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<line x1="18" y1="6" x2="6" y2="18" />
				<line x1="6" y1="6" x2="18" y2="18" />
			</>
		),
	} );

export const ChevronDownIcon = ( props ) =>
	base( {
		...props,
		children: <polyline points="6 9 12 15 18 9" />,
	} );

export const ChevronRightIcon = ( props ) =>
	base( {
		...props,
		children: <polyline points="9 18 15 12 9 6" />,
	} );

export const ExternalIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
				<polyline points="15 3 21 3 21 9" />
				<line x1="10" y1="14" x2="21" y2="3" />
			</>
		),
	} );

export const GaugeIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M12 14a2 2 0 1 0 .01-.01" />
				<path d="M3.34 19a10 10 0 1 1 17.32 0" />
				<line x1="12" y1="12" x2="16" y2="8" />
			</>
		),
	} );

export const ShieldIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
				<polyline points="9 12 11 14 15 10" />
			</>
		),
	} );

export const LockIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="3" y="11" width="18" height="11" rx="2" />
				<path d="M7 11V7a5 5 0 0 1 10 0v4" />
			</>
		),
	} );

export const DownloadIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
				<polyline points="7 10 12 15 17 10" />
				<line x1="12" y1="15" x2="12" y2="3" />
			</>
		),
	} );

export const UsersIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
				<circle cx="9" cy="7" r="4" />
				<path d="M22 21v-2a4 4 0 0 0-3-3.87" />
				<path d="M16 3.13a4 4 0 0 1 0 7.75" />
			</>
		),
	} );

export const CodeIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<polyline points="16 18 22 12 16 6" />
				<polyline points="8 6 2 12 8 18" />
			</>
		),
	} );

export const ClockIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle cx="12" cy="12" r="9" />
				<polyline points="12 7 12 12 15 14" />
			</>
		),
	} );

export const WarnIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
				<line x1="12" y1="9" x2="12" y2="13" />
				<line x1="12" y1="17" x2="12.01" y2="17" />
			</>
		),
	} );

export const RefreshIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M21 12a9 9 0 1 1-2.64-6.36" />
				<polyline points="21 3 21 9 15 9" />
			</>
		),
	} );

export const StoreIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="m2 7 2-4h16l2 4" />
				<path d="M4 7v13h16V7" />
				<path d="M9 20v-6h6v6" />
				<path d="M2 7h20a3 3 0 0 1-3 3 3 3 0 0 1-3-3 3 3 0 0 1-3 3 3 3 0 0 1-3-3 3 3 0 0 1-3 3 3 3 0 0 1-3-3Z" />
			</>
		),
	} );

export const MoreIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle
					cx="5"
					cy="12"
					r="1.6"
					fill="currentColor"
					stroke="none"
				/>
				<circle
					cx="12"
					cy="12"
					r="1.6"
					fill="currentColor"
					stroke="none"
				/>
				<circle
					cx="19"
					cy="12"
					r="1.6"
					fill="currentColor"
					stroke="none"
				/>
			</>
		),
	} );

export const LayoutIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="3" y="3" width="18" height="18" rx="2" />
				<line x1="3" y1="9" x2="21" y2="9" />
				<line x1="9" y1="21" x2="9" y2="9" />
			</>
		),
	} );

export const BrushIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="m15 4 1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2Z" />
				<path d="M9 11 3 17a1.4 1.4 0 0 0 2 2l6-6" />
				<path d="m12.5 8.5 3 3" />
			</>
		),
	} );

export const PlusIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<line x1="12" y1="5" x2="12" y2="19" />
				<line x1="5" y1="12" x2="19" y2="12" />
			</>
		),
	} );

export const ServerIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="2" y="2" width="20" height="8" rx="2" />
				<rect x="2" y="14" width="20" height="8" rx="2" />
				<line x1="6" y1="6" x2="6.01" y2="6" />
				<line x1="6" y1="18" x2="6.01" y2="18" />
			</>
		),
	} );

export const SparklesIcon = ( props ) =>
	base( {
		...props,
		children: (
			<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
		),
	} );

export const GlobeIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle cx="12" cy="12" r="10" />
				<line x1="2" y1="12" x2="22" y2="12" />
				<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z" />
			</>
		),
	} );

export const HeadphonesIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M3 14a9 9 0 0 1 18 0" />
				<path d="M21 14v3a2 2 0 0 1-2 2h-1a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h3Z" />
				<path d="M3 14v3a2 2 0 0 0 2 2h1a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1H3Z" />
			</>
		),
	} );

export const RocketIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09Z" />
				<path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2Z" />
			</>
		),
	} );

export const PlugIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M9 2v6" />
				<path d="M15 2v6" />
				<path d="M6 8h12v3a6 6 0 0 1-12 0V8Z" />
				<path d="M12 17v5" />
			</>
		),
	} );

export const ArrowRightIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<line x1="5" y1="12" x2="19" y2="12" />
				<polyline points="12 5 19 12 12 19" />
			</>
		),
	} );


/** Prime Slider icon-font glyph (ps-wi-<widget>), as in the Elementor panel. */
export const WidgetIcon = ( { name, size = 22, className = '' } ) => (
	<i
		className={ `ps-widget-glyph ps-wi-${ name } ${ className }` }
		style={ { fontSize: size } }
		aria-hidden="true"
	/>
);

export const CopyIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<rect x="8" y="8" width="14" height="14" rx="2" />
				<path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
			</>
		),
	} );

export const PlayIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<circle cx="12" cy="12" r="9" />
				<path d="M10 8.5v7l6-3.5z" />
			</>
		),
	} );
