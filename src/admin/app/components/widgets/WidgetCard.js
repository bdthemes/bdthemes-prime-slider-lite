/**
 * Widget row card (Element Pack layout): icon tile, name, category · usage ·
 * Demo · Video, and a switch. Pro widgets without Pro show a lock; 3rd-party
 * widgets whose plugin is missing show an install/activate button instead.
 */
import { memo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Toggle from '../ui/Toggle';
import { ProBadge } from '../ui/Badge';
import { WidgetIcon, LockIcon, ExternalIcon, DownloadIcon, PlugIcon } from '../ui/Icons';
import config from '../../utils/config';
import { accentClass } from '../../utils/accents';
import { categoryFor, isNew } from '../../utils/widgetCategories';

function WidgetCard( { module: mod, checked, onToggle } ) {
	const category = categoryFor( mod );
	const pluginMissing = mod.plugin_active === false;

	return (
		<div
			className={ `relative grid grid-cols-[44px_minmax(0,1fr)_auto] items-center gap-[14px] border rounded-panel py-4 px-[16px] transition-all duration-200 hover:-translate-y-[1px] ${
				mod.is_locked
					? 'bg-[#fffafa] dark:bg-[#1d1719] border-[#f7e3e7] dark:border-[#3a262b]'
					: 'bg-white dark:bg-ink-card border-[#ececf0] dark:border-ink-border hover:border-[#fdd3bf] dark:hover:border-[#5a3524] hover:shadow-[0_8px_20px_-12px_rgba(252,106,42,.35)]'
			}` }
			title={
				mod.is_locked
					? __( 'This widget is provided by the separate Prime Slider Pro plugin.', 'bdthemes-prime-slider-lite' )
					: undefined
			}
		>
			<span className={ `w-11 h-11 rounded-[12px] flex-none grid place-items-center ${ accentClass( category.accent ) }` }>
				<WidgetIcon name={ mod.name } size={ 22 } />
			</span>

			<div className="min-w-0">
				<div className="text-[14px] font-bold text-[#1a1a1f] dark:text-ink-text tracking-[-.01em] whitespace-nowrap overflow-hidden text-ellipsis">
					{ mod.label }
				</div>
				<div className="flex flex-wrap items-center gap-x-[6px] gap-y-[2px] text-[11.5px] text-[#a1a1aa] mt-[3px] whitespace-nowrap">
					<span className="font-medium text-[#9a9aa3] dark:text-ink-muted">{ category.label }</span>
					{ mod.usage_count > 0 ? (
						<span className="inline-flex items-center gap-[6px]">
							<span className="text-[#d4d4d8]">·</span>
							<span className="text-[#15803d] dark:text-[#4ade80] font-bold">
								{ sprintf(
									/* translators: %d: number of times the widget is used */
									__( 'Used %d', 'bdthemes-prime-slider-lite' ),
									mod.usage_count
								) }
							</span>
						</span>
					) : null }
					{ mod.demo_url ? (
						<span className="inline-flex items-center gap-[6px]">
							<span className="text-[#d4d4d8]">·</span>
							<a href={ mod.demo_url } target="_blank" rel="noreferrer" className="inline-flex items-center gap-[3px] font-semibold text-[#9a9aa3] hover:text-brand">
								{ __( 'Demo', 'bdthemes-prime-slider-lite' ) }
								<ExternalIcon size={ 10 } />
							</a>
						</span>
					) : null }
					{ mod.video_url ? (
						<span className="inline-flex items-center gap-[6px]">
							<span className="text-[#d4d4d8]">·</span>
							<a href={ mod.video_url } target="_blank" rel="noreferrer" className="inline-flex items-center gap-[3px] font-semibold text-[#9a9aa3] hover:text-brand">
								{ __( 'Video', 'bdthemes-prime-slider-lite' ) }
								<ExternalIcon size={ 10 } />
							</a>
						</span>
					) : null }
				</div>
			</div>

			<div className="flex items-center gap-[10px] flex-none">
				{ isNew( mod ) ? (
					<span className="text-[8.5px] font-extrabold tracking-[.08em] uppercase text-[#e11d48] bg-[#fff1f2] dark:bg-[#2a0e14] dark:text-[#fda4af] py-[2px] px-[7px] rounded-full flex-none">
						{ __( 'New', 'bdthemes-prime-slider-lite' ) }
					</span>
				) : null }
				{ mod.widget_type === 'pro' ? <ProBadge /> : null }
				{ mod.is_locked ? (
					<a
						href={ config.pricingUrl }
						target="_blank"
						rel="noreferrer"
						className="text-[#a1a1aa] hover:text-brand"
						aria-label={ sprintf(
							/* translators: %s: widget name */
							__( '%s is available in Prime Slider Pro', 'bdthemes-prime-slider-lite' ),
							mod.label
						) }
					>
						<LockIcon size={ 18 } />
					</a>
				) : pluginMissing ? (
					mod.action_url ? (
						<a
							href={ mod.action_url }
							target={ mod.action === 'download' ? '_blank' : undefined }
							rel={ mod.action === 'download' ? 'noreferrer' : undefined }
							title={
								mod.action === 'activate'
									? __( 'Activate the plugin first, then you can enable this widget.', 'bdthemes-prime-slider-lite' )
									: __( 'Install the plugin first, then you can enable this widget.', 'bdthemes-prime-slider-lite' )
							}
							className="w-[30px] h-[30px] rounded-full flex-none grid place-items-center bg-[#fff1f2] text-[#e11d48] dark:bg-[#2a0e14] dark:text-[#fda4af] transition-colors hover:bg-[#e11d48] hover:text-white"
						>
							{ mod.action === 'activate' ? <PlugIcon size={ 15 } /> : <DownloadIcon size={ 15 } /> }
						</a>
					) : null
				) : (
					<Toggle size="sm" checked={ checked } onChange={ () => onToggle( mod.key || mod.name ) } label={ mod.label } />
				) }
			</div>
		</div>
	);
}

export default memo( WidgetCard );
