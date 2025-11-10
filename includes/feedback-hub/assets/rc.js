(function ($) {
    // console.log('rc.js loaded');
    // Move biggopti below page title
    $(document).ready(function() {
        var $biggopties = $('.rc-global-biggopti');
        if ($biggopties.length > 0) {

            var $target = $('#wpbody-content .wrap').first();

            if (!$target.length) {
                $target = $('.wrap').first();
            }
            if (!$target.length) {
                $target = $('#wpbody-content');
            }
            
            // insert right after the <h1> if exists, otherwise at top
            if ($target.children('hr.wp-header-end').length) {
                $target.children('hr.wp-header-end').first().after($markup);
            } else if ($target.children('h1').length) {
                $target.children('h1').first().after($markup);
            } else {
                $target.prepend($markup);
            }
        }
    });

    $(document).on('click', '.rc-button-allow, .rc-button-skip, .rc-button-disallow', function () {
        let nonce = $(this).data('nonce'),
            rc_name = $(this).data('rc_name'),
            date_name = $(this).data('date_name'),
            allow_name = $(this).data('allow_name'),
            review_url = $(this).data('review_url');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rc_sdk_insights',
                button_val: this.value,
                nonce: nonce,
                rc_name: rc_name,
                date_name: date_name,
                allow_name: allow_name,
            },
            success: function (response) {
                if (response.status == 'success') {
                    if ('yes' == response.action) {
                        setTimeout(() => {
                            window.open(review_url, '_blank');
                        }, 500);
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.message);
                }
            },
        });
    });

    // Manually added dismiss button
    if (typeof wp !== 'undefined' && wp.a11y && window.jQuery) {
        /// $(document).trigger('wp-updates-biggopti-added');
        // Manually add dismiss button if not present
        var $markup = $('.rc-global-biggopti.is-dismissible');
        $markup.each(function () {
            var $el = $(this);
            if ($el.hasClass('is-dismissible') && !$el.find('.rc-biggopti-dismiss').length) {
                var $button = $('<button type="button" class="rc-biggopti-dismiss dashicons dashicons-dismiss"><span class="screen-reader-text">Dismiss this biggopti.</span></button>');
                $el.append($button);
                $button.on('click', function () {
                    $el.fadeTo(100, 0, function () {
                        $el.slideUp(100, function () {
                            $el.remove();
                        });
                    });
                });
            }
        });
    }

    $(document).on('click', '.rc-global-biggopti .rc-biggopti-dismiss', function () {
        let rc_name = $(this).closest('.rc-global-biggopti').find("[name='rc_name']").val(),
                nonce = $(this).closest('.rc-global-biggopti').find("[name='nonce']").val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rc_sdk_dismiss_biggopti',
                nonce: nonce,
                rc_name: rc_name,
            },
        });
    });

    // Show only the first RC biggopti
    var $biggopties = $('.rc-global-biggopti');
    if ($biggopties.length > 0) {
        $biggopties.first().show();
    }
    $(document).on('click', '.rc-global-biggopti .rc-biggopti-dismiss', function() {
        var $currentBiggopti = $(this).closest('.rc-global-biggopti');
        var $nextBiggopti = $currentBiggopti.nextAll('.rc-global-biggopti:first');

        if ($nextBiggopti.length) {
            $nextBiggopti.show();
        }
    });
    $('.rc-global-biggopti button').on('click', function() {
        var $biggopti = $(this).closest('.rc-global-biggopti');
        var $nextBiggopti = $biggopti.nextAll('.rc-global-biggopti:first');

        $biggopti.fadeOut(300, function() {
            if ($nextBiggopti.length) {
                $nextBiggopti.fadeIn();
            }
        });
    });

})(jQuery);