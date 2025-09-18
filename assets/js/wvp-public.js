/**
 * WVP Public JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // VIP price display utilities
    const WVP_Public = {
        
        // Format price with currency
        formatPrice: function(price) {
            const symbol = wvp_public_ajax.currency_symbol;
            const position = wvp_public_ajax.currency_position;
            
            switch (position) {
                case 'left':
                    return symbol + price;
                case 'right':
                    return price + symbol;
                case 'left_space':
                    return symbol + ' ' + price;
                case 'right_space':
                    return price + ' ' + symbol;
                default:
                    return symbol + price;
            }
        },

        // Show loading state
        showLoading: function(element) {
            element.addClass('wvp-loading');
        },

        // Hide loading state
        hideLoading: function(element) {
            element.removeClass('wvp-loading');
        },

        // Show message
        showMessage: function(container, type, message) {
            const messageHtml = `<div class="wvp-message ${type}">${message}</div>`;
            container.html(messageHtml);
            
            if (type === 'success') {
                setTimeout(function() {
                    container.find('.wvp-message.success').fadeOut();
                }, 3000);
            }
        },

        // Add spinner to button
        addSpinner: function(button) {
            const spinner = '<span class="wvp-spinner"></span>';
            button.prepend(spinner).prop('disabled', true);
        },

        // Remove spinner from button
        removeSpinner: function(button) {
            button.find('.wvp-spinner').remove().prop('disabled', false);
        }
    };

    // VIP Code verification on checkout
    function initCheckoutVipCode() {
        const $verifyButton = $('#wvp_verify_code');
        const $codeInput = $('#wvp_code');
        const $messages = $('#wvp_code_messages');

        if (!$verifyButton.length) return;

        $verifyButton.on('click', function(e) {
            e.preventDefault();
            
            const code = $codeInput.val().trim();
            
            if (!code) {
                WVP_Public.showMessage($messages, 'error', wvp_public_ajax.strings.invalid_code);
                return;
            }

            WVP_Public.addSpinner($verifyButton);
            WVP_Public.showMessage($messages, 'loading', wvp_public_ajax.strings.loading);

            $.ajax({
                url: wvp_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wvp_verify_code',
                    code: code,
                    nonce: wvp_public_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WVP_Public.showMessage($messages, 'success', response.data.message);
                        
                        if (response.data.refresh_checkout) {
                            // Refresh checkout to apply VIP pricing
                            setTimeout(function() {
                                $('body').trigger('update_checkout');
                            }, 1000);
                        }
                        
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }
                        
                        // Hide the VIP code section after successful verification
                        setTimeout(function() {
                            $('#wvp-checkout-vip-section').slideUp();
                        }, 2000);
                        
                    } else {
                        WVP_Public.showMessage($messages, 'error', response.data.message || wvp_public_ajax.strings.error);
                    }
                },
                error: function() {
                    WVP_Public.showMessage($messages, 'error', wvp_public_ajax.strings.error);
                },
                complete: function() {
                    WVP_Public.removeSpinner($verifyButton);
                }
            });
        });

        // Allow Enter key to verify code
        $codeInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $verifyButton.click();
            }
        });

        // Clear messages on input change
        $codeInput.on('input', function() {
            $messages.empty();
        });
    }

    // Real-time VIP price updates
    function initVipPriceUpdates() {
        // Update prices when user VIP status changes
        $(document).on('wvp_vip_status_changed', function() {
            updateAllPrices();
        });

        // Update prices on AJAX complete (for dynamic content)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.indexOf('woocommerce') !== -1) {
                setTimeout(updateAllPrices, 100);
            }
        });

        function updateAllPrices() {
            $('.wvp-price-container').each(function() {
                const $container = $(this);
                const productId = $container.data('product-id');
                
                if (productId) {
                    updateProductPrice($container, productId);
                }
            });
        }

        function updateProductPrice($container, productId) {
            $.ajax({
                url: wvp_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wvp_get_product_price',
                    product_id: productId,
                    nonce: wvp_public_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.price_html);
                    }
                }
            });
        }
    }

    // Tooltip functionality
    function initTooltips() {
        $('.wvp-tooltip').on('mouseenter', function() {
            const title = $(this).attr('title') || $(this).data('tip');
            if (title) {
                const tooltip = $('<div class="wvp-tooltip-popup">' + title + '</div>');
                $('body').append(tooltip);
                
                const offset = $(this).offset();
                tooltip.css({
                    position: 'absolute',
                    top: offset.top - tooltip.outerHeight() - 5,
                    left: offset.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2),
                    zIndex: 9999
                });
            }
        });

        $('.wvp-tooltip').on('mouseleave', function() {
            $('.wvp-tooltip-popup').remove();
        });
    }

    // VIP status notices
    function initVipNotices() {
        // Show VIP welcome message for new VIP users
        if (sessionStorage.getItem('wvp_new_vip_user')) {
            showVipWelcomeMessage();
            sessionStorage.removeItem('wvp_new_vip_user');
        }

        function showVipWelcomeMessage() {
            const welcomeMessage = `
                <div class="wvp-welcome-notice">
                    <div class="woocommerce-message">
                        <strong>${wvp_public_ajax.strings.vip_welcome || 'Welcome to VIP!'}</strong><br>
                        ${wvp_public_ajax.strings.vip_welcome_message || 'You now have access to special VIP pricing and exclusive offers!'}
                    </div>
                </div>
            `;
            
            $('body').prepend(welcomeMessage);
            
            setTimeout(function() {
                $('.wvp-welcome-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Product card interactions
    function initProductCards() {
        // Add hover effects for VIP products
        $('.wvp-vip-pricing-enabled').on('mouseenter', function() {
            $(this).find('.wvp-vip-price-teaser').addClass('highlight');
        }).on('mouseleave', function() {
            $(this).find('.wvp-vip-price-teaser').removeClass('highlight');
        });

        // Click tracking for VIP elements
        $('.wvp-vip-badge, .wvp-savings-badge').on('click', function(e) {
            e.preventDefault();
            
            // Track VIP badge clicks for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'vip_badge_click', {
                    'event_category': 'VIP',
                    'event_label': 'Badge Click'
                });
            }

            // Could redirect to VIP info page or show modal
            showVipInfoModal();
        });

        function showVipInfoModal() {
            const modalHtml = `
                <div class="wvp-info-modal">
                    <div class="wvp-modal-backdrop"></div>
                    <div class="wvp-modal-content">
                        <h3>Pogodnosti VIP clanstva</h3>
                        <ul>
                            <li>Specijalne cene na dostupne proizvode</li>
                            <li>Pristup ekskluzivnim VIP paketima</li>
                            <li>Prioritetna korisnička podrška</li>
                            <li>Rani pristup novim proizvodima</li>
                        </ul>
                        <button class="wvp-modal-close">Zatvori</button>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('.wvp-info-modal').fadeIn();
        }

        // Close modal
        $(document).on('click', '.wvp-modal-close, .wvp-modal-backdrop', function() {
            $('.wvp-info-modal').fadeOut(function() {
                $(this).remove();
            });
        });
    }

    // Smooth animations
    function initAnimations() {
        // Animate VIP badges on scroll
        function animateOnScroll() {
            $('.wvp-vip-badge, .wvp-product-badge').each(function() {
                const elementTop = $(this).offset().top;
                const elementBottom = elementTop + $(this).outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();

                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $(this).addClass('animated');
                }
            });
        }

        $(window).on('scroll', throttle(animateOnScroll, 100));
        animateOnScroll(); // Initial check
    }

    // Performance optimization utilities
    function throttle(func, delay) {
        let timeoutId;
        let lastExecTime = 0;
        
        return function() {
            const currentTime = Date.now();
            
            if (currentTime - lastExecTime > delay) {
                func.apply(this, arguments);
                lastExecTime = currentTime;
            } else {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    func.apply(this, arguments);
                    lastExecTime = Date.now();
                }, delay - (currentTime - lastExecTime));
            }
        };
    }

    // Cart update handling
    function initCartUpdates() {
        $(document.body).on('updated_wc_div', function() {
            // Re-initialize components after cart update
            setTimeout(function() {
                initTooltips();
                updateVipNotices();
            }, 100);
        });

        $(document.body).on('added_to_cart', function() {
            // Show message when VIP product is added to cart
            if (wvp_public_ajax.user_is_vip) {
                showCartVipMessage();
            }
        });

        function showCartVipMessage() {
            const message = '<div class="woocommerce-message">VIP pricing has been applied to your cart!</div>';
            $('.woocommerce-notices-wrapper').first().append(message);
            
            setTimeout(function() {
                $('.woocommerce-message').last().fadeOut();
            }, 3000);
        }

        function updateVipNotices() {
            if (wvp_public_ajax.user_is_vip) {
                $('.wvp-vip-status-notice').show();
            } else {
                $('.wvp-vip-status-notice').hide();
            }
        }
    }

    // Accessibility improvements
    function initAccessibility() {
        // Add ARIA labels to VIP elements
        $('.wvp-vip-badge').attr('aria-label', 'VIP Member Pricing Active');
        $('.wvp-savings-badge').each(function() {
            const savings = $(this).text();
            $(this).attr('aria-label', `VIP Savings: ${savings}`);
        });

        // Keyboard navigation for interactive elements
        $('.wvp-vip-badge, .wvp-savings-badge').attr('tabindex', '0');
        
        $('.wvp-vip-badge, .wvp-savings-badge').on('keydown', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });

        // Screen reader announcements for price changes
        function announceVipPricing() {
            if (wvp_public_ajax.user_is_vip) {
                const announcement = $('<div class="sr-only" aria-live="polite">VIP pricing is active on this page</div>');
                $('body').append(announcement);
                
                setTimeout(function() {
                    announcement.remove();
                }, 3000);
            }
        }

        // Announce VIP pricing on page load
        setTimeout(announceVipPricing, 1000);
    }

    // Error handling and logging
    function initErrorHandling() {
        window.addEventListener('error', function(e) {
            if (e.filename && e.filename.includes('wvp-public')) {
                console.error('WVP Error:', e.message, e.filename, e.lineno);
            }
        });

        // AJAX error handling
        $(document).ajaxError(function(event, xhr, settings, error) {
            if (settings.data && settings.data.includes('wvp_')) {
                console.error('WVP AJAX Error:', error, settings.url);
            }
        });
    }

    // Initialize all functionality
    function init() {
        initCheckoutVipCode();
        initVipPriceUpdates();
        initTooltips();
        initVipNotices();
        initProductCards();
        initAnimations();
        initCartUpdates();
        initAccessibility();
        initErrorHandling();
    }

    // Start the application
    init();

    // Make utilities available globally
    window.WVP_Public = WVP_Public;

    // Custom events
    $(document).trigger('wvp_public_loaded');
});

// CSS animations for VIP elements
const wvpAnimations = `
<style>
.wvp-vip-badge.animated,
.wvp-product-badge.animated {
    animation: wvp-pulse 0.6s ease-in-out;
}

.wvp-vip-price-teaser.highlight {
    background: rgba(212, 160, 23, 0.1);
    transition: background-color 0.3s ease;
}

.wvp-tooltip-popup {
    background: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.wvp-welcome-notice {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    animation: wvp-slideIn 0.5s ease-out;
}

.wvp-info-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
}

.wvp-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.wvp-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    max-width: 400px;
    width: 90%;
}

.wvp-modal-content h3 {
    margin-top: 0;
}

.wvp-modal-content ul {
    padding-left: 20px;
}

.wvp-modal-close {
    background: #007cba;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    float: right;
}

@keyframes wvp-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes wvp-slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.sr-only {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}
</style>
`;

// Inject animations CSS
jQuery(document).ready(function() {
    jQuery('head').append(wvpAnimations);
});