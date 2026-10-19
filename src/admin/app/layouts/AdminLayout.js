/**
 * App shell: full-bleed page background with a unified app-bar card (header +
 * attached tab strip) above the routed content, as in the Element Pack
 * dashboard.
 *
 * `.ps-root` carries the root-level utilities: Tailwind's `important` selector
 * scopes utilities under #ps-admin-app, so classes on the mount node itself
 * would not match.
 */
import AppBar from '../components/shell/AppBar';
import TabStrip from '../components/shell/TabStrip';
import MobileNav from '../components/shell/MobileNav';

export default function AdminLayout( { children } ) {
	return (
		<div className="ps-root min-h-[calc(100vh-32px)] bg-[#f0f0f1] dark:bg-ink-page text-[#1d2327] dark:text-[#e4e4e7] text-sm">
			<div className="mx-auto w-full max-w-[1400px] pt-[30px] px-12 pb-[60px] max-[1024px]:px-7 max-[1024px]:pt-6 max-[640px]:px-[14px] max-[640px]:pt-4 max-[900px]:pb-[110px]">
				<div className="bg-white dark:bg-ink-card border border-[#e6e6ea] dark:border-ink-border rounded-card">
					<AppBar />
					<TabStrip />
				</div>

				<main className="mt-[26px]" role="main">
					{ children }
				</main>
			</div>

			<MobileNav />
		</div>
	);
}
