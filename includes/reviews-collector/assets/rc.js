(function ($) {
    // console.log("rc.js loaded");
    $(document).on("click", ".rc-button-allow, .rc-button-skip", function () {
        $.ajax({
            url: RC_SETTINGS.ajax_url,
            type: "POST",
            data: {
                action: "rc_sdk_insights",
                button_val: this.value,
                nonce: RC_SETTINGS.nonce,
            },
            success: function (response) {
                if (response.status == "success") {
                    if ('yes' == response.action) {
                        setTimeout(() => {
                            window.open(RC_SETTINGS.review_url, "_blank");
                        }, 500);
                    }
                    setTimeout(() => {
                        // location.reload();
                    }, 1500);
                } else {
                    alert(response.message);
                }
            },
        });
    });

    $(document).on("click", ".rc-global-notice .notice-dismiss", function () {
        $.ajax({
            url: RC_SETTINGS.ajax_url,
            type: 'POST',
            data: {
                action: 'rc_sdk_dismiss_notice',
                nonce: RC_SETTINGS.nonce,
            },
        });
    });

})(jQuery);