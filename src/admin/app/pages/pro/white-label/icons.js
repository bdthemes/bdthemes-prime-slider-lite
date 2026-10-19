/**
 * Icons the White Label page needs that the shared icon set lacks. Same
 * Lucide-style 24 viewbox / stroke 2 as components/ui/Icons.js.
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

export const TrashIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<polyline points="3 6 5 6 21 6" />
				<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
			</>
		),
	} );

export const UploadIcon = ( props ) =>
	base( {
		...props,
		children: (
			<>
				<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
				<polyline points="17 8 12 3 7 8" />
				<line x1="12" y1="3" x2="12" y2="15" />
			</>
		),
	} );

/**
 * Prime Slider's menu mark (the bars of its wp-admin menu icon) as a solid
 * currentColor glyph, for the live preview's default icon.
 */
export const PrimeSliderMark = ( { size = 12, ...rest } ) => (
	<svg
		width={ size }
		height={ size }
		viewBox="0 0 230.7 254.8"
		fill="currentColor"
		aria-hidden="true"
		{ ...rest }
	>
		<rect x="22.7" y="25.6" width="185" height="43.6" rx="5.5" />
		<rect x="22.7" y="105.6" width="185" height="43.6" rx="5.5" />
		<rect x="22.7" y="185.6" width="104.5" height="43.6" rx="5.5" />
		<rect x="22.7" y="40.2" width="43.6" height="92.3" rx="5.5" />
		<rect x="164.1" y="105.6" width="43.6" height="123.6" rx="5.5" />
	</svg>
);
