/**
 * Responsive grid of WidgetCard items: 3 columns → 2 → 1.
 */
import WidgetCard from './WidgetCard';

export default function WidgetGrid( { modules, draft, onToggle } ) {
	return (
		<div className="grid grid-cols-3 max-[1150px]:grid-cols-2 max-[700px]:grid-cols-1 gap-3">
			{ modules.map( ( mod ) => (
				<WidgetCard
					key={ mod.key || mod.name }
					module={ mod }
					checked={ !! draft[ mod.key || mod.name ] }
					onToggle={ onToggle }
				/>
			) ) }
		</div>
	);
}
