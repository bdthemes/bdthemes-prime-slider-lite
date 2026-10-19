/**
 * Quick actions grid — mockup Variant A (index.html 408–421). 3-col grid of
 * 14px-radius tiles: icon tile, label, sub; hover border-brand + tint + lift.
 */
import { __ } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import IconTile from '../ui/IconTile';

export default function QuickActions( { actions } ) {
	const navigate = useNavigate();

	if ( ! actions.length ) {
		return null;
	}

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5 shadow-resting">
			<div className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text mb-[14px]">
				{ __( 'Quick actions', 'bdthemes-prime-slider-lite' ) }
			</div>
			<div className="grid grid-cols-3 max-[640px]:grid-cols-1 gap-3">
				{ actions.map( ( action ) => (
					<button
						key={ action.to }
						type="button"
						onClick={ () => navigate( action.to ) }
						className="text-left border border-[#ededf1] dark:border-ink-border rounded-[14px] p-[15px] cursor-pointer transition-all duration-200 bg-white dark:bg-ink-card hover:border-brand hover:bg-[#fff8f4] dark:hover:bg-[#15233a] hover:-translate-y-[2px]"
					>
						<IconTile
							icon={ action.icon }
							accent={ action.accent }
							size="lg"
						/>
						<div className="text-[13.5px] font-semibold text-[#27272a] dark:text-ink-text mt-[11px]">
							{ action.label }
						</div>
						<div className="text-[11.5px] text-[#a1a1aa] dark:text-ink-muted mt-[2px]">
							{ action.sub }
						</div>
					</button>
				) ) }
			</div>
		</div>
	);
}
