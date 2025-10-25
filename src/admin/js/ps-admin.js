jQuery(document).ready(function ($) {

    if (jQuery('.wrap').hasClass('prime-slider-dashboard')) {

        // total activate
        function total_widget_status() {
            var total_widget_active_status = [];

            var totalActivatedWidgets = [];
            jQuery('#prime_slider_active_modules_page input:checked').each(function () {
                totalActivatedWidgets.push(jQuery(this).attr('name'));
            });

            total_widget_active_status.push(totalActivatedWidgets.length);

            var totalActivatedExtensions = [];
            jQuery('#prime_slider_elementor_extend_page input:checked').each(function () {
                totalActivatedExtensions.push(jQuery(this).attr('name'));
            });

            total_widget_active_status.push(totalActivatedExtensions.length);


            jQuery('#bdt-total-widgets-status').attr('data-value', total_widget_active_status);
            jQuery('#bdt-total-widgets-status-core').text(total_widget_active_status[0]);
            jQuery('#bdt-total-widgets-status-extensions').text(total_widget_active_status[1]);

            jQuery('#bdt-total-widgets-status-heading').text(total_widget_active_status[0] + total_widget_active_status[1]);

        }

        total_widget_status();

        jQuery('.prime-slider-settings-save-btn').on('click', function () {
            setTimeout(function () {
                total_widget_status();
            }, 2000);
        });

        // end total active



        // modules
        var moduleUsedWidget = jQuery('#prime_slider_active_modules_page').find('.ps-used-widget');
        var moduleUsedWidgetCount = jQuery('#prime_slider_active_modules_page').find('.ps-options .ps-used').length;

        moduleUsedWidget.text(moduleUsedWidgetCount);
        var moduleUnusedWidget = jQuery('#prime_slider_active_modules_page').find('.ps-unused-widget');
        var moduleUnusedWidgetCount = jQuery('#prime_slider_active_modules_page').find('.ps-options .ps-unused').length;
        moduleUnusedWidget.text(moduleUnusedWidgetCount);

        // total widgets

        var dashboardChatItems = ['#bdt-db-total-status', '#bdt-total-widgets-status'];

        dashboardChatItems.forEach(function ($el) {

            const ctx = jQuery($el);

            var $value = ctx.data('value');
            $value = $value.split(',');

            var $labels = ctx.data('labels');
            $labels = $labels.split(',');

            var $bg = ctx.data('bg');
            $bg = $bg.split(',');

            // var $bgHover = ctx.data('bg-hover');
            // $bgHover = $bgHover.split(',');


            const data = {
                // labels: $labels,
                datasets: [{
                    data: $value,
                    backgroundColor: $bg,
                    // hoverBackgroundColor: false, //$bgHover,
                    borderWidth: 0,
                }],

            };

            const config = {
                type: 'doughnut',
                data: data,
                options: {
                    animation: {
                        duration: 3000,
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
                    title: {
                        display: false,
                        text: ctx.data('label'),
                        fontSize: 16,
                        fontColor: '#333',
                    },
                    hover: {
                        mode: null
                    },

                }
            };

            if (window.myChart instanceof Chart) {
                window.myChart.destroy();
            }

            var myChart = new Chart(ctx, config);
            // if (x != 'init'){
            //     // myChart.destroy();

            //     // var myChart = new Chart(ctx, config);
            //      myChart.update();
            // }


        });
    }

    jQuery('.prime-slider-notice.notice-error img').css({
        'margin-right': '8px',
        'vertical-align': 'middle'
    });
});