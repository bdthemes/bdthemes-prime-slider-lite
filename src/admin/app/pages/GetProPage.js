/**
 * Get Pro (free plugin only): what Prime Slider Pro adds, and where to get it.
 */
import { __ } from '@wordpress/i18n';
import PageHeader from '../components/shell/PageHeader';
import IconTile from '../components/ui/IconTile';
import { CheckIcon, ZapIcon, GridIcon, SparklesIcon, TagIcon, CodeIcon, ClockIcon, ExternalIcon } from '../components/ui/Icons';
import config, { sectionFields } from '../utils/config';

export default function GetProPage() {
	const proWidgets = [
		...sectionFields( 'prime_slider_active_modules' ),
		...sectionFields( 'prime_slider_third_party_widget' ),
		...sectionFields( 'prime_slider_elementor_extend' ),
	].filter( ( f ) => f.widget_type === 'pro' );

	const perks = [
		{ icon: GridIcon, accent: 'blue', title: __( 'Every Pro slider', 'bdthemes-prime-slider-lite' ), sub: __( 'Unlock all the widgets marked PRO in your widget list.', 'bdthemes-prime-slider-lite' ) },
		{ icon: SparklesIcon, accent: 'rose', title: __( 'Animations & Reveal Effects', 'bdthemes-prime-slider-lite' ), sub: __( 'Advanced Animation, Reveal Effects and the licensed swiper effects.', 'bdthemes-prime-slider-lite' ) },
		{ icon: ZapIcon, accent: 'amber', title: __( 'Dynamic sliders & integrations', 'bdthemes-prime-slider-lite' ), sub: __( 'Post, WooCommerce and event sources with Adaptive Background.', 'bdthemes-prime-slider-lite' ) },
		{ icon: CodeIcon, accent: 'violet', title: __( 'Custom CSS & JS', 'bdthemes-prime-slider-lite' ), sub: __( 'Inject site-wide code with page exclusions.', 'bdthemes-prime-slider-lite' ) },
		{ icon: TagIcon, accent: 'woo', title: __( 'White label', 'bdthemes-prime-slider-lite' ), sub: __( 'Rebrand the plugin for client sites.', 'bdthemes-prime-slider-lite' ) },
		{ icon: ClockIcon, accent: 'green', title: __( 'Rollback & premium support', 'bdthemes-prime-slider-lite' ), sub: __( 'Restore a previous version and get priority help.', 'bdthemes-prime-slider-lite' ) },
	];

	return (
		<div>
			<PageHeader
				title={ __( 'Prime Slider Pro', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __( 'Everything in the free version, plus the features below.', 'bdthemes-prime-slider-lite' ) }
			/>

			<div className="rounded-card p-8 max-[640px]:p-5 relative overflow-hidden text-white [background:linear-gradient(135deg,#1a0f14,#3b1420)]">
				<div className="absolute inset-0 pointer-events-none [background:radial-gradient(circle_at_85%_0%,rgba(252,106,42,.35),transparent_55%)]" />
				<div className="relative max-w-[640px]">
					<div className="text-[12px] font-bold tracking-[.12em] uppercase text-[#ffb08a]">
						{ __( 'Upgrade', 'bdthemes-prime-slider-lite' ) }
					</div>
					<div className="text-[26px] font-extrabold mt-2 tracking-[-.02em]">
						{ __( 'Create your sleek website with Prime Slider Pro', 'bdthemes-prime-slider-lite' ) }
					</div>
					<p className="text-[14px] text-white/75 mt-3 leading-[1.6]">
						{ proWidgets.length
							? `${ proWidgets.length } ${ __( 'Pro widgets and extensions, plus the tools that complex, dynamic slider sites need.', 'bdthemes-prime-slider-lite' ) }`
							: __( 'Pro widgets and extensions, plus the tools that complex, dynamic slider sites need.', 'bdthemes-prime-slider-lite' ) }
					</p>
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

			<div className="grid grid-cols-3 max-[1024px]:grid-cols-2 max-[640px]:grid-cols-1 gap-4 mt-4">
				{ perks.map( ( perk ) => (
					<div key={ perk.title } className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
						<IconTile icon={ perk.icon } accent={ perk.accent } size="xl" />
						<div className="text-[15px] font-extrabold text-[#0f1115] dark:text-ink-text mt-3 flex items-center gap-2">
							<CheckIcon size={ 14 } className="text-[#22a550]" />
							{ perk.title }
						</div>
						<p className="text-[13px] text-[#71717a] dark:text-ink-muted mt-1 leading-[1.55]">{ perk.sub }</p>
					</div>
				) ) }
			</div>
		</div>
	);
}
