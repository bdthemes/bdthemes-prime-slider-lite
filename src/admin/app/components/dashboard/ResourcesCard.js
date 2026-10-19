/**
 * Dashboard card: documentation, video tutorials, support, feature requests
 * and the setup wizard.
 */
import { __ } from '@wordpress/i18n';
import config from '../../utils/config';
import IconTile from '../ui/IconTile';
import { BookIcon, PlayIcon, HeadphonesIcon, SparklesIcon, RocketIcon, ChevronRightIcon, ExternalIcon } from '../ui/Icons';

export default function ResourcesCard() {
	if ( config.whiteLabel ) {
		return null;
	}

	const links = [
		{ title: __( 'Documentation', 'bdthemes-prime-slider-lite' ), sub: __( 'Guides & tutorials', 'bdthemes-prime-slider-lite' ), icon: BookIcon, accent: 'blue', url: config.docsUrl },
		{ title: __( 'Video tutorials', 'bdthemes-prime-slider-lite' ), sub: __( 'Learn on YouTube', 'bdthemes-prime-slider-lite' ), icon: PlayIcon, accent: 'rose', url: config.videosUrl },
		{ title: __( 'Support', 'bdthemes-prime-slider-lite' ), sub: __( 'Get help from the team', 'bdthemes-prime-slider-lite' ), icon: HeadphonesIcon, accent: 'violet', url: config.supportUrl },
		{ title: __( 'Request a feature', 'bdthemes-prime-slider-lite' ), sub: __( 'Tell us what to build next', 'bdthemes-prime-slider-lite' ), icon: SparklesIcon, accent: 'amber', url: config.featureUrl },
		{ title: __( 'Setup wizard', 'bdthemes-prime-slider-lite' ), sub: __( 'Guided configuration', 'bdthemes-prime-slider-lite' ), icon: RocketIcon, accent: 'green', url: config.setupWizardUrl, sameTab: true },
	].filter( ( link ) => !! link.url );

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text mb-[14px]">
				{ __( 'Resources & help', 'bdthemes-prime-slider-lite' ) }
			</div>
			<div className="flex flex-col gap-[9px]">
				{ links.map( ( link ) => (
					<a
						key={ link.title }
						href={ link.url }
						target={ link.sameTab ? undefined : '_blank' }
						rel={ link.sameTab ? undefined : 'noreferrer' }
						className="group flex items-center gap-3 p-[11px] border border-[#eef0f3] dark:border-ink-border rounded-[13px] cursor-pointer transition-all duration-150 hover:border-brand hover:bg-[#fff8f4] dark:hover:bg-[#2a1a12]"
					>
						<IconTile icon={ link.icon } accent={ link.accent } size="md" />
						<div className="flex-1 min-w-0">
							<div className="text-[13px] font-bold text-[#27272a] dark:text-ink-text transition-colors group-hover:text-brand">{ link.title }</div>
							<div className="text-[11.5px] text-[#a1a1aa] dark:text-ink-muted mt-px">{ link.sub }</div>
						</div>
						{ link.sameTab ? (
							<ChevronRightIcon size={ 16 } className="text-[#c4c4cc] flex-none transition-all duration-150 group-hover:text-brand group-hover:translate-x-[3px]" />
						) : (
							<ExternalIcon size={ 15 } className="text-[#c4c4cc] flex-none transition-colors duration-150 group-hover:text-brand" />
						) }
					</a>
				) ) }
			</div>
		</div>
	);
}
