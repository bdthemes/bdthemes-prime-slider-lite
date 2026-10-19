/**
 * White Label (Prime Slider Pro), laid out like Element Pack's White Label
 * page. `data` comes from Pro through the `prime_slider/admin/react_pages`
 * filter; without it the route shows the PHP panel instead.
 */
import { __ } from '@wordpress/i18n';
import PageHeader from '../../components/shell/PageHeader';
import WhiteLabelPanel from './white-label/WhiteLabelPanel';

export default function WhiteLabelPage( { data } ) {
	return (
		<div>
			<PageHeader
				title={ __( 'White Label', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __(
					'Rebrand Prime Slider across the admin for your clients.',
					'bdthemes-prime-slider-lite'
				) }
			/>
			<WhiteLabelPanel data={ data || {} } />
		</div>
	);
}
