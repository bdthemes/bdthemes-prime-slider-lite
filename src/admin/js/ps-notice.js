jQuery(document).ready(function ($) {
    // Delegate to capture dynamically injected notices as well
    $(document).on('click', '.prime-slider-notice.is-dismissible .notice-dismiss', function () {
        $this = $(this).parents('.prime-slider-notice');
        var $id = $this.attr('id') || '';
        var $time = $this.attr('dismissible-time') || '';
        var $meta = $this.attr('dismissible-meta') || '';
        $.ajax({
            url: (window.PrimeSliderNoticeConfig && PrimeSliderNoticeConfig.ajaxurl) ? PrimeSliderNoticeConfig.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : ''),
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

    /* ===================================
       Admin Store API NOTICE
       =================================== */
    
    /**
     * Initialize countdown timers for API notices
     * This function finds all countdown elements and starts the countdown timer
     */
    function initAPINoticeCountdown() {
        // Find all countdown elements on the page
        jQuery('.bdt-notice-countdown').each(function() {
            var $countdown = jQuery(this);
            var $timer = $countdown.find('.countdown-timer');
            var endDate = $countdown.data('end-date');
            var timezone = $countdown.data('timezone');
            
            // Skip if no end date or timer element found
            if (!endDate || !$timer.length) {
                return;
            }
            
            /**
             * Update the countdown display
             * Calculates time remaining and formats it for display
             */
            function updateCountdown() {
                var endTime = new Date(endDate + ' ' + timezone).getTime();
                var now = new Date().getTime();
                var distance = endTime - now;
                
                // If countdown has expired, hide the countdown
                if (distance < 0) {
                    $countdown.hide();
                    return;
                }
                
                // Calculate time units
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Add leading zeros
                days = days < 10 ? "0" + days : days;
                hours = hours < 10 ? "0" + hours : hours; 
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                
                // Build countdown text with wrapped numbers and labels
                var countdownText = "";
                if (days > 0) {
                    countdownText += '<div class="countdown-item"><span class="number">' + days + '</span><span class="label">days</span></div><span class="separator"></span>';
                }
                // Always show hours (even if 00) for consistent layout
                countdownText += '<div class="countdown-item"><span class="number">' + hours + '</span><span class="label">hrs</span></div><span class="separator"></span>';
                
                countdownText += '<div class="countdown-item"><span class="number">' + minutes + '</span><span class="label">min</span></div><span class="separator"></span>';
                
                countdownText += '<div class="countdown-item"><span class="number">' + seconds + '</span><span class="label">sec</span></div>';
                
                // Update the timer display
                $timer.html(countdownText);
            }
            
            // Initial update to show countdown immediately
            updateCountdown();
            
            // Set up interval to update countdown every second
            setInterval(updateCountdown, 1000);
        });
    }
    
    // Initialize countdown on page load
    initAPINoticeCountdown();
    
    // Re-initialize countdown when new notices are added (for dynamic content)
    // This ensures countdown works even if notices are loaded after page load
    jQuery(document).on('DOMNodeInserted', '.bdt-notice-countdown', function() {
        initAPINoticeCountdown();
    });

    // Fetch API notices after full page load, with try/catch
    $(window).on('load', function () {
        // Add delay to let Element Pack load first and avoid race conditions
        setTimeout(function() {
            try {
                $.ajax({
                url: (window.PrimeSliderNoticeConfig && PrimeSliderNoticeConfig.ajaxurl) ? PrimeSliderNoticeConfig.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : ''),
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ps_fetch_api_notices',
                    _wpnonce: PrimeSliderNoticeConfig.nonce
                }
            })
            .done(function (res) {
                if (res && res.success && res.data && res.data.html) {
                    var $markup = $(res.data.html);
                    var $target = $('#wpbody-content .wrap').first();

                    if (!$target.length) {
                        $target = $('.wrap').first();
                    }
                    if (!$target.length) {
                        $target = $('#wpbody-content');
                    }

                   // Check for existing notices with same class to avoid duplicates
                     var shouldInsert = true;
                     $markup.each(function() {
                         var $notice = $(this);
                         var noticeId = $notice.attr('id');
                         
                         // Extract class pattern from notice ID (e.g., bdt-admin-notice-api-notice-class-xxxxx)
                         if (noticeId && noticeId.indexOf('bdt-admin-notice-api-notice-class-') !== -1) {
                             var classPattern = noticeId.substring(noticeId.indexOf('bdt-admin-notice-api-notice-class-'));
                             
                             // Check if any existing notice in DOM has similar class pattern from any plugin
                             var existingNotices = $('[id$="' + classPattern + '"]');
                             if (existingNotices.length > 0) {
                                 shouldInsert = false;
                                 return false; // break out of each loop
                             }
                         }
                     });

                    // Only insert if no duplicate class pattern found
                    if (shouldInsert) {
                        // insert right after the <h1> if exists, otherwise at top
                        if ($target.children('hr.wp-header-end').length) {
                            $target.children('hr.wp-header-end').first().after($markup);
                        } else if ($target.children('h1').length) {
                            $target.children('h1').first().after($markup);
                        } else {
                            $target.prepend($markup);
                        }
                    }

                    // Re-initialize WP dismiss buttons for dynamically added notices
                    if (typeof wp !== 'undefined' && wp.a11y && window.jQuery) {
                        $(document).trigger('wp-updates-notice-added');
                    } else {
                        // fallback: manually add close button + click handler
                        $markup.each(function () {
                            var $el = $(this);
                            if ($el.hasClass('is-dismissible') && !$el.find('.notice-dismiss').length) {
                                var $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
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


                    // Initialize countdowns in injected content
                    initAPINoticeCountdown();
                }
            })
            .fail(function () {
                // swallow errors silently
            });
            } catch (e) {
                // ignore
            }
        }, 300); // 300ms delay to let Element Pack load first
    });

    /* ===================================
       END Admin Store API NOTICE
       =================================== */

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