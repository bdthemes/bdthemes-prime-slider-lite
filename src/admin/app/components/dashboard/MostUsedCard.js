/**
 * Dashboard card: the most-used Prime Slider widgets on the site.
 */
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';

const BARS = [ '#fc6a2a', '#fe506c', '#8b5cf6', '#22a550', '#14b8a6' ];

export default function MostUsedCard( { widgets } ) {
	if ( ! widgets.length ) {
		return null;
	}

	const max = widgets[ 0 ].usage_count || 1;

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="flex items-center justify-between mb-[14px]">
				<div className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text">
					{ __( 'Most used widgets', 'bdthemes-prime-slider-lite' ) }
				</div>
				<Link to="/widgets" className="text-[12.5px] font-semibold text-brand">
					{ __( 'Manage', 'bdthemes-prime-slider-lite' ) }
				</Link>
			</div>
			<div className="flex flex-col gap-[14px]">
				{ widgets.map( ( widget, index ) => (
					<div key={ widget.name }>
						<div className="flex items-center justify-between text-[13px] font-semibold text-[#27272a] dark:text-ink-text">
							{ widget.label }
							<span className="text-[12px] font-bold text-[#71717a] dark:text-ink-muted">{ widget.usage_count }</span>
						</div>
						<div className="h-[7px] rounded-full bg-[#eef0f3] dark:bg-ink-subtle mt-[6px] overflow-hidden">
							<div
								className="h-full rounded-full"
								style={ { width: `${ Math.max( 6, Math.round( ( widget.usage_count / max ) * 100 ) ) }%`, background: BARS[ index % BARS.length ] } }
							/>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
