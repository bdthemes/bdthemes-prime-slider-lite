jQuery(document).ready(function ($) {
    // Delegate to capture dynamically injected biggopties as well
    $(document).on('click', '.prime-slider-biggopti.is-dismissible .bdt-biggopti-dismiss', function () {
        $this = $(this).parents('.prime-slider-biggopti');
        var $id = $this.attr('id') || '';
        var $time = $this.attr('dismissible-time') || '';
        var $meta = $this.attr('dismissible-meta') || '';
        $.ajax({
            url: (window.PrimeSliderBiggoptiConfig && PrimeSliderBiggoptiConfig.ajaxurl) ? PrimeSliderBiggoptiConfig.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : ''),
            type: 'POST',
            data: {
                action: 'prime_slider_biggopties',
                id: $id,
                meta: $meta,
                time: $time,
                _wpnonce: PrimeSliderBiggoptiConfig.nonce
            }
        });
    });
});

// Button Color
window.CSS.registerProperty({
    name: '--primaryColor',
    syntax: '<color>',
    inherits: false,
    initialValue: '#AA00FF',
});

window.CSS.registerProperty({
    name: '--secondaryColor',
    syntax: '<color>',
    inherits: false,
    initialValue: '#FF2661',
});