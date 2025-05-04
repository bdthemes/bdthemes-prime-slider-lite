(function ($) {
    // console.log('rc.js loaded');
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

    $(document).on('click', '.rc-global-notice .notice-dismiss', function () {
        let rc_name = $(this).closest('.rc-global-notice').find("[name='rc_name']").val(),
                nonce = $(this).closest('.rc-global-notice').find("[name='nonce']").val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rc_sdk_dismiss_notice',
                nonce: nonce,
                rc_name: rc_name,
            },
        });
    });

    // Show only the first RC notice
    var $notices = $('.rc-global-notice');
    if ($notices.length > 0) {
        $notices.first().show();
    }
    $(document).on('click', '.rc-global-notice .notice-dismiss', function() {
        var $currentNotice = $(this).closest('.rc-global-notice');
        var $nextNotice = $currentNotice.nextAll('.rc-global-notice:first');

        if ($nextNotice.length) {
            $nextNotice.show();
        }
    });
    $('.rc-global-notice button').on('click', function() {
        var $notice = $(this).closest('.rc-global-notice');
        var $nextNotice = $notice.nextAll('.rc-global-notice:first');

        $notice.fadeOut(300, function() {
            if ($nextNotice.length) {
                $nextNotice.fadeIn();
            }
        });
    });

})(jQuery);