/**
 * Dashboard suggestions (Element Pack's Suggestions card): switch off
 * widgets no page uses, and turn on Asset Optimization.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import IconTile from '../ui/IconTile';
import { SparklesIcon, GaugeIcon, ShieldIcon, ArrowRightIcon } from '../ui/Icons';
import config, { sectionFields } from '../../utils/config';

export default function SuggestionsCard() {
	const navigate = useNavigate();
	const widgets = [
		...sectionFields( 'prime_slider_active_modules' ),
		...sectionFields( 'prime_slider_third_party_widget' ),
	];
	const tracking = widgets.some( ( w ) => w.has_usage );
	// Enabled and switchable, but not used on any page.
	const unused = tracking
		? widgets.filter( ( w ) => w.enabled && ! w.is_locked && w.plugin_active !== false && ! ( w.usage_count > 0 ) ).length
		: 0;
	const assetsOn = !! ( config.assets && config.assets.enabled );

	const insights = [
		unused > 0 && {
			icon: GaugeIcon,
			accent: 'blue',
			title: __( 'Trim unused widgets', 'bdthemes-prime-slider-lite' ),
			text: sprintf(
				/* translators: %d: unused widget count */
				__( '%d enabled widgets aren’t used on any page. Disabling them makes your pages lighter.', 'bdthemes-prime-slider-lite' ),
				unused
			),
			cta: __( 'Review widgets', 'bdthemes-prime-slider-lite' ),
			to: '/widgets',
		},
		! assetsOn && {
			icon: ShieldIcon,
			accent: 'teal',
			title: __( 'Optimize asset loading', 'bdthemes-prime-slider-lite' ),
			text: __( 'Turn on Asset Optimization so each page loads its widgets’ CSS and JS as one file each.', 'bdthemes-prime-slider-lite' ),
			cta: __( 'Open performance', 'bdthemes-prime-slider-lite' ),
			to: '/performance',
		},
	].filter( Boolean );

	if ( ! insights.length ) {
		return null;
	}

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="flex items-center justify-between mb-[14px]">
				<div className="flex items-center gap-[9px]">
					<span className="w-[30px] h-[30px] rounded-[9px] grid place-items-center [background:linear-gradient(135deg,#fff1ea,#ffe7ee)] dark:[background:#12161c]">
						<SparklesIcon size={ 17 } className="text-brand" />
					</span>
					<span className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text">
						{ __( 'Suggestions', 'bdthemes-prime-slider-lite' ) }
					</span>
				</div>
				<span className="text-[10px] font-extrabold tracking-[.05em] text-brand bg-[#fff1ea] dark:bg-[#2a1a12] py-[3px] px-2 rounded-full">
					{ sprintf(
						/* translators: %d: suggestion count */
						__( '%d NEW', 'bdthemes-prime-slider-lite' ),
						insights.length
					) }
				</span>
			</div>
			<div className="flex flex-col gap-[10px]">
				{ insights.map( ( insight ) => (
					<div
						key={ insight.title }
						className="flex gap-[11px] p-3 border border-[#eef0f3] dark:border-ink-border rounded-[13px] bg-[#fbfbfd] dark:bg-ink-subtle transition-colors hover:border-[#fdd3bf]"
					>
						<IconTile icon={ insight.icon } accent={ insight.accent } size="sm" />
						<div className="flex-1 min-w-0">
							<div className="text-[13px] font-semibold text-[#27272a] dark:text-ink-text">{ insight.title }</div>
							<div className="text-[11.5px] text-[#71717a] dark:text-ink-muted mt-[2px] leading-[1.45]">{ insight.text }</div>
							<button
								type="button"
								onClick={ () => navigate( insight.to ) }
								className="mt-2 inline-flex items-center gap-[5px] text-brand text-[11.5px] font-bold cursor-pointer"
							>
								{ insight.cta }
								<ArrowRightIcon size={ 12 } strokeWidth={ 2.4 } />
							</button>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
