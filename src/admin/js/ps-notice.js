jQuery(document).ready(function ($) {
    $('.prime-slider-notice.is-dismissible .notice-dismiss').on('click', function () {
        $this = $(this).parents('.prime-slider-notice');
        var $id = $this.attr('id') || '';
        var $time = $this.attr('dismissible-time') || '';
        var $meta = $this.attr('dismissible-meta') || '';
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'prime-slider-notices',
                id: $id,
                meta: $meta,
                time: $time,
                _wpnonce: PrimeSliderNoticeConfig.nonce
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