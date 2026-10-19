/**
 * Rounded tinted icon square from the mockup (stat cards, health strip,
 * settings group headers, resource rows). Sizes map to the mockup's
 * 30/34/36/38/40px tiles with their matching radii.
 */
import { accentClass } from '../../utils/accents';

const SIZES = {
	sm: 'w-[30px] h-[30px] rounded-[9px]',
	md: 'w-[34px] h-[34px] rounded-[10px]',
	lg: 'w-9 h-9 rounded-[10px]',
	xl: 'w-[38px] h-[38px] rounded-tile',
	'2xl': 'w-10 h-10 rounded-[12px]',
};

const ICON_SIZES = { sm: 16, md: 17, lg: 18, xl: 20, '2xl': 20 };

export default function IconTile( {
	icon: Icon,
	accent = 'blue',
	size = '2xl',
	iconSize,
	className = '',
} ) {
	return (
		<span
			className={ `${ SIZES[ size ] || SIZES[ '2xl' ] } flex-none grid place-items-center ${ accentClass(
				accent
			) } ${ className }` }
		>
			<Icon size={ iconSize || ICON_SIZES[ size ] || 20 } />
		</span>
	);
}
