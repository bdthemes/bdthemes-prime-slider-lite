/**
 * A Pro feature's tab in the free plugin (White Label, Custom CSS & JS,
 * Rollback Version): the same tab as with Pro active, but the page describes
 * the feature and links to the upgrade, in the Get Pro page's style. Nothing
 * of the feature itself runs in the free plugin.
 */
import { __ } from '@wordpress/i18n';
import PageHeader from '../components/shell/PageHeader';
import IconTile from '../components/ui/IconTile';
import { ProBadge } from '../components/ui/Badge';
import { CheckIcon, ZapIcon, ExternalIcon } from '../components/ui/Icons';
import config from '../utils/config';

export default function ProFeaturePage( { icon, accent, title, subtitle, headline, description, points } ) {
	return (
		<div>
			<PageHeader title={ title } subtitle={ subtitle } />

			<div className="rounded-card p-8 max-[640px]:p-5 relative overflow-hidden text-white [background:linear-gradient(135deg,#1a0f14,#3b1420)]">
				<div className="absolute inset-0 pointer-events-none [background:radial-gradient(circle_at_85%_0%,rgba(252,106,42,.35),transparent_55%)]" />
				<div className="relative max-w-[640px]">
					<div className="flex items-center gap-2 text-[12px] font-bold tracking-[.12em] uppercase text-[#ffb08a]">
						{ __( 'Pro feature', 'bdthemes-prime-slider-lite' ) }
					</div>
					<div className="text-[26px] font-extrabold mt-2 tracking-[-.02em]">{ headline }</div>
					<p className="text-[14px] text-white/75 mt-3 leading-[1.6]">{ description }</p>
					<div className="flex flex-wrap gap-3 mt-6">
						<a href={ config.getProUrl } target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 bg-gradient-brand text-white py-3 px-6 rounded-full text-[14px] font-extrabold shadow-glow hover:-translate-y-px transition-all">
							<ZapIcon size={ 15 } />
							{ __( 'Get Premium at 30% OFF', 'bdthemes-prime-slider-lite' ) }
						</a>
						<a href={ config.pricingUrl } target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 bg-white/[.12] border border-white/[.2] text-white py-3 px-6 rounded-full text-[14px] font-bold hover:bg-white/20 transition-colors">
							{ __( 'Compare Free vs Pro', 'bdthemes-prime-slider-lite' ) }
							<ExternalIcon size={ 13 } />
						</a>
					</div>
				</div>
			</div>

			<div className="grid grid-cols-2 max-[640px]:grid-cols-1 gap-4 mt-4">
				{ points.map( ( point ) => (
					<div key={ point.title } className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5 flex gap-4">
						<IconTile icon={ icon } accent={ accent } size="xl" />
						<div className="min-w-0">
							<div className="text-[15px] font-extrabold text-[#0f1115] dark:text-ink-text flex items-center gap-2 flex-wrap">
								<CheckIcon size={ 14 } className="text-[#1a7d3d] dark:text-[#4ade80]" />
								{ point.title }
								<ProBadge />
							</div>
							<p className="text-[13px] text-[#6c6c75] dark:text-ink-muted mt-1 mb-0 leading-[1.55]">{ point.sub }</p>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
