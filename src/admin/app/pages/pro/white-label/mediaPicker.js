/**
 * Open the WordPress media modal and return the selected attachment
 * (port of Element Pack's utils/mediaPicker). The library is limited to the
 * formats the classic White Label view accepted: JPG, PNG and SVG.
 *
 * Needs wp.media, which Prime Slider Pro enqueues (wp_enqueue_media()) when it
 * hands this page its data.
 */
export function openMediaPicker( { title, buttonText, onSelect } ) {
	if ( ! window.wp || ! window.wp.media ) {
		return false;
	}

	const frame = window.wp.media( {
		title: title || 'Select image',
		button: { text: buttonText || 'Use image' },
		multiple: false,
		library: {
			type: [ 'image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml' ],
		},
	} );

	frame.on( 'select', () => {
		const attachment = frame.state().get( 'selection' ).first().toJSON();
		onSelect( attachment );
	} );

	frame.open();
	return true;
}
