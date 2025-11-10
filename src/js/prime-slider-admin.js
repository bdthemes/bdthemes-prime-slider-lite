jQuery(document).ready(function ($) {

    jQuery('.prime-slider-biggopti.is-dismissible .rc-biggopti-dismiss').on('click', function () {
        $this = jQuery(this).parents('.prime-slider-biggopti');
        var $id = $this.attr('id') || '';
        var $time = $this.attr('dismissible-time') || '';
        var $meta = $this.attr('dismissible-meta') || '';

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'prime_slider_biggopties',
                id: $id,
                meta: $meta,
                time: $time,
            },
        });

    });

});