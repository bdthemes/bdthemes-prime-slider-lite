/**
 * App bar: logo, PRO pill, version, Pro status, theme toggle, Docs and a
 * primary call to action (Get Pro on the free plugin, Create page with Pro).
 */
import { __ } from '@wordpress/i18n';
import config from '../../utils/config';
import { useColorScheme } from '../../hooks/useColorScheme';
import { SunIcon, MoonIcon, BookIcon, ZapIcon, PlusIcon } from '../ui/Icons';
import logoDarkUrl from '../../assets/logo-dark.svg';

function ProPill() {
	if ( config.whiteLabel ) {
		return null;
	}

	if ( config.isPro ) {
		return (
			<div className="hidden lg:flex items-center gap-[7px] bg-[#ecfdf3] border border-[#c6f0d4] text-[#15803d] dark:bg-[#0f2318] dark:border-[#1d4030] dark:text-[#4ade80] py-[9px] px-[15px] rounded-full text-[13px] font-bold">
				<span className="w-2 h-2 rounded-full bg-[#22c55e] shadow-[0_0_0_3px_rgba(34,197,94,.2)]" />
				{ __( 'Pro active', 'bdthemes-prime-slider-lite' ) }
			</div>
		);
	}

	return (
		<div className="hidden lg:flex items-center gap-[7px] bg-[#f4f5f7] border border-[#e6e6ea] text-[#52525b] dark:bg-ink-subtle dark:border-[#2a313c] dark:text-[#cdd0d6] py-[9px] px-[15px] rounded-full text-[13px] font-bold">
			<span className="w-2 h-2 rounded-full bg-[#a1a1aa]" />
			{ __( 'Free version', 'bdthemes-prime-slider-lite' ) }
		</div>
	);
}

export default function AppBar() {
	const { theme, toggle } = useColorScheme();
	const isDark = theme === 'dark';
	const iconBtn =
		'w-[42px] h-[42px] rounded-full bg-white border border-[#e6e6ea] text-[#52525b] dark:bg-ink-card dark:border-[#2a313c] dark:text-[#cdd0d6] cursor-pointer grid place-items-center transition-colors hover:border-brand hover:text-brand';

	return (
		<div className="min-h-[80px] py-[18px] px-[26px] flex items-center justify-between gap-5 flex-wrap max-[900px]:py-[13px] max-[900px]:px-[15px] max-[900px]:gap-3">
			<div className="flex items-center gap-4">
				{ config.whiteLabel ? (
					<span className="text-[19px] font-extrabold tracking-[-.02em] text-[#0f1115] dark:text-ink-text">
						{ config.title }
					</span>
				) : (
					<img
						src={ isDark ? logoDarkUrl : config.logoUrl }
						alt={ config.title || 'Prime Slider' }
						className="h-8 w-auto block"
					/>
				) }
				{ config.isPro ? (
					<span className="text-[11px] font-extrabold tracking-[.06em] text-white bg-gradient-brand py-[5px] px-[11px] rounded-full shadow-[0_3px_10px_rgba(252,106,42,.3)]">
						{ __( 'PRO', 'bdthemes-prime-slider-lite' ) }
					</span>
				) : null }
				<span className="text-[12.5px] text-[#6c6c75] dark:text-ink-muted font-bold">
					v{ config.version }
				</span>
			</div>

			<div className="flex items-center gap-[10px]">
				<ProPill />

				<button
					type="button"
					onClick={ toggle }
					className={ iconBtn }
					aria-label={ __( 'Toggle theme', 'bdthemes-prime-slider-lite' ) }
				>
					{ isDark ? <SunIcon size={ 18 } /> : <MoonIcon size={ 18 } /> }
				</button>

				{ ! config.whiteLabel ? (
					<a
						href={ config.docsUrl }
						target="_blank"
						rel="noreferrer"
						className="hidden lg:flex items-center gap-[6px] bg-white border-[1.5px] border-[#e2e2e6] text-[#27272a] dark:bg-ink-card dark:border-[#2a313c] dark:text-[#cdd0d6] py-[10px] px-[17px] rounded-full text-[13.5px] font-bold cursor-pointer transition-colors hover:border-brand hover:text-brand"
					>
						<BookIcon size={ 14 } />
						{ __( 'Docs', 'bdthemes-prime-slider-lite' ) }
					</a>
				) : null }

				{ config.isPro || config.whiteLabel ? (
					<a
						href={ config.newPageUrl }
						className="flex items-center gap-[7px] bg-gradient-brand text-white py-[11px] px-[22px] rounded-full text-[13.5px] font-extrabold cursor-pointer shadow-glow transition-all duration-200 hover:-translate-y-px hover:shadow-glow-lg max-[640px]:px-[15px]"
					>
						<PlusIcon size={ 14 } />
						{ __( 'Create page', 'bdthemes-prime-slider-lite' ) }
					</a>
				) : (
					<a
						href={ config.getProUrl }
						target="_blank"
						rel="noreferrer"
						className="flex items-center gap-[7px] bg-gradient-brand text-white py-[11px] px-[22px] rounded-full text-[13.5px] font-extrabold cursor-pointer shadow-glow transition-all duration-200 hover:-translate-y-px hover:shadow-glow-lg max-[640px]:px-[15px]"
					>
						<ZapIcon size={ 14 } />
						{ __( 'Get Pro', 'bdthemes-prime-slider-lite' ) }
					</a>
				) }
			</div>
		</div>
	);
}
