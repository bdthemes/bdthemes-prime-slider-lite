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

    /* ===================================
       Admin Store API BIGGOPTI
       =================================== */
    
    /**
     * Initialize countdown timers for API biggopties
     * This function finds all countdown elements and starts the countdown timer
     */
    function initAPIBiggoptiCountdown() {
        // Find all countdown elements on the page
        jQuery('.bdt-biggopti-countdown').each(function() {
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
    initAPIBiggoptiCountdown();
    
    // Re-initialize countdown when new biggopties are added (for dynamic content)
    // This ensures countdown works even if biggopties are loaded after page load
    jQuery(document).on('DOMNodeInserted', '.bdt-biggopti-countdown', function() {
        initAPIBiggoptiCountdown();
    });

    // Fetch API biggopties after full page load, with try/catch
    $(window).on('load', function () {
        // Add delay to let Element Pack load first and avoid race conditions
        setTimeout(function() {
            try {
                $.ajax({
                url: (window.PrimeSliderBiggoptiConfig && PrimeSliderBiggoptiConfig.ajaxurl) ? PrimeSliderBiggoptiConfig.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : ''),
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ps_fetch_api_biggopties',
                    _wpnonce: PrimeSliderBiggoptiConfig.nonce
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

                   // Check for existing biggopties with same class to avoid duplicates
                     var shouldInsert = true;
                     $markup.each(function() {
                         var $biggopti = $(this);
                         var biggoptiId = $biggopti.attr('id');
                         
                         // Extract class pattern from biggopti ID (e.g., bdt-admin-biggopti-api-biggopti-class-xxxxx)
                         if (biggoptiId && biggoptiId.indexOf('bdt-admin-biggopti-api-biggopti-class-') !== -1) {
                             var classPattern = biggoptiId.substring(biggoptiId.indexOf('bdt-admin-biggopti-api-biggopti-class-'));
                             
                             // Check if any existing biggopti in DOM has similar class pattern from any plugin
                             var existingBiggopties = $('[id$="' + classPattern + '"]');
                             if (existingBiggopties.length > 0) {
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

                    // Re-initialize WP dismiss buttons for dynamically added biggopties
                    if (typeof wp !== 'undefined' && wp.a11y && window.jQuery) {
                       // fallback: manually add close button + click handler
                        $markup.each(function () {
                            var $el = $(this);
                            if ($el.hasClass('is-dismissible') && !$el.find('.bdt-biggopti-dismiss').length) {
                                var $button = $('<button type="button" class="bdt-biggopti-dismiss dashicons dashicons-dismiss"><span class="screen-reader-text">Dismiss this biggopti.</span></button>');
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
                    initAPIBiggoptiCountdown();
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
       END Admin Store API BIGGOPTI
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