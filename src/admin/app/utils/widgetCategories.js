/**
 * Widget categories from the `content_type` tokens of each field (the same
 * tokens the classic dashboard filtered by). A widget's category is its first
 * matching token; `new` is a badge, not a category.
 */
import { __ } from '@wordpress/i18n';

export const CATEGORIES = {
	static: { label: __( 'Static', 'bdthemes-prime-slider-lite' ), accent: 'blue' },
	custom: { label: __( 'Custom', 'bdthemes-prime-slider-lite' ), accent: 'violet' },
	carousel: { label: __( 'Carousel', 'bdthemes-prime-slider-lite' ), accent: 'teal' },
	swiper: { label: __( 'Swiper', 'bdthemes-prime-slider-lite' ), accent: 'sky' },
	post: { label: __( 'Post', 'bdthemes-prime-slider-lite' ), accent: 'amber' },
	ecommerce: { label: __( 'eCommerce', 'bdthemes-prime-slider-lite' ), accent: 'woo' },
	others: { label: __( 'Others', 'bdthemes-prime-slider-lite' ), accent: 'rose' },
	third_party: { label: __( '3rd Party', 'bdthemes-prime-slider-lite' ), accent: 'woo' },
};

export const THIRD_PARTY_SECTION = 'prime_slider_third_party_widget';

export function tokens( mod ) {
	return String( mod.content_type || '' ).toLowerCase().split( /\s+/ ).filter( Boolean );
}

export function isNew( mod ) {
	return tokens( mod ).includes( 'new' );
}

export function categoryKeyForModule( mod ) {
	// 3rd-party widgets share one chip, as on Element Pack's Widgets tab.
	if ( mod.section === THIRD_PARTY_SECTION ) {
		return 'third_party';
	}
	return tokens( mod ).find( ( token ) => CATEGORIES[ token ] && token !== 'third_party' ) || 'others';
}

export function categoryFor( mod ) {
	return CATEGORIES[ categoryKeyForModule( mod ) ];
}
