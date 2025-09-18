<?php
/**
 * Package add to cart functionality template
 */

if (!defined('ABSPATH')) {
    exit;
}

$package_id = get_the_ID();
$packages_admin = new WVP_Admin_Packages();
$package_data = $packages_admin->get_package_data($package_id);
$core = new WVP_Core();
$is_vip = $core->is_user_vip();

if (!$package_data) {
    return;
}
?>

<div class="wvp-add-to-cart-section" id="wvp-add-to-cart" style="display: none;">
    <div class="cart-success-state" id="cart-success" style="display: none;">
        <div class="success-content">
            <div class="success-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="success-message">
                <h3><?php _e('Package Added to Cart!', 'woocommerce-vip-paketi'); ?></h3>
                <p><?php _e('Your custom package has been successfully added to your cart.', 'woocommerce-vip-paketi'); ?></p>
            </div>
        </div>
        
        <div class="success-actions">
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="view-cart-btn">
                <span class="dashicons dashicons-cart"></span>
                <?php _e('View Cart', 'woocommerce-vip-paketi'); ?>
            </a>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-btn">
                <?php _e('Proceed to Checkout', 'woocommerce-vip-paketi'); ?>
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </a>
            <button type="button" class="continue-shopping-btn" id="continue-shopping">
                <?php _e('Continue Shopping', 'woocommerce-vip-paketi'); ?>
            </button>
        </div>
    </div>
    
    <div class="cart-loading-state" id="cart-loading" style="display: none;">
        <div class="loading-content">
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
            <div class="loading-message">
                <h3><?php _e('Adding Package to Cart...', 'woocommerce-vip-paketi'); ?></h3>
                <p><?php _e('Please wait while we process your package selection.', 'woocommerce-vip-paketi'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="cart-error-state" id="cart-error" style="display: none;">
        <div class="error-content">
            <div class="error-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="error-message">
                <h3><?php _e('Error Adding Package', 'woocommerce-vip-paketi'); ?></h3>
                <p class="error-details" id="error-details">
                    <?php _e('There was an error adding your package to the cart. Please try again.', 'woocommerce-vip-paketi'); ?>
                </p>
            </div>
        </div>
        
        <div class="error-actions">
            <button type="button" class="retry-btn" id="retry-add-to-cart">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Try Again', 'woocommerce-vip-paketi'); ?>
            </button>
            <button type="button" class="back-to-edit-btn" id="back-to-edit">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Edit Package', 'woocommerce-vip-paketi'); ?>
            </button>
        </div>
    </div>
</div>

<div class="wvp-cart-mini-summary" id="cart-mini-summary" style="display: none;">
    <div class="mini-summary-content">
        <div class="summary-header">
            <h4><?php _e('Cart Summary', 'woocommerce-vip-paketi'); ?></h4>
            <button type="button" class="close-summary" id="close-mini-summary">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <div class="summary-body" id="mini-summary-body">
            <!-- Cart contents will be loaded here -->
        </div>
        
        <div class="summary-footer">
            <div class="cart-total">
                <span class="total-label"><?php _e('Cart Total:', 'woocommerce-vip-paketi'); ?></span>
                <span class="total-amount" id="cart-total-amount">€0.00</span>
            </div>
            <div class="summary-actions">
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="mini-view-cart">
                    <?php _e('View Cart', 'woocommerce-vip-paketi'); ?>
                </a>
                <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="mini-checkout">
                    <?php _e('Checkout', 'woocommerce-vip-paketi'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentPackageData = null;
    let isProcessing = false;
    
    // Listen for add to cart trigger
    $(document).on('click', '#add-package-to-cart', function() {
        if (isProcessing) return;
        
        // Get package data from previous sections
        const selectedProducts = getSelectedProducts();
        const packageData = getPackageData();
        
        if (!selectedProducts || selectedProducts.length === 0) {
            showError(__('No products selected. Please go back and select products.', 'woocommerce-vip-paketi'));
            return;
        }
        
        currentPackageData = {
            package_id: <?php echo $package_id; ?>,
            products: selectedProducts,
            package_data: packageData
        };
        
        addPackageToCart();
    });
    
    // Retry functionality
    $('#retry-add-to-cart').on('click', function() {
        if (currentPackageData) {
            addPackageToCart();
        }
    });
    
    // Continue shopping
    $('#continue-shopping').on('click', function() {
        // Reset the package configurator
        resetPackageConfigurator();
    });
    
    // Back to edit
    $('#back-to-edit').on('click', function() {
        // Scroll back to product selection
        $('html, body').animate({
            scrollTop: $('#wvp-product-selection').offset().top - 100
        }, 500);
        
        // Hide states
        hideAllStates();
    });
    
    // Close mini summary
    $('#close-mini-summary').on('click', function() {
        $('#cart-mini-summary').slideUp();
    });
    
    function addPackageToCart() {
        if (isProcessing) return;
        
        isProcessing = true;
        showLoadingState();
        
        const ajaxData = {
            action: 'wvp_add_package_to_cart',
            nonce: wvp_ajax.nonce,
            ...currentPackageData
        };
        
        $.ajax({
            url: wvp_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            timeout: 30000, // 30 second timeout
            success: function(response) {
                if (response.success) {
                    showSuccessState(response.data);
                    updateCartSummary();
                    
                    // Track conversion event
                    trackPackageAddToCart(currentPackageData);
                } else {
                    showError(response.data || __('Unknown error occurred.', 'woocommerce-vip-paketi'));
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = __('Network error occurred. Please check your connection and try again.', 'woocommerce-vip-paketi');
                
                if (status === 'timeout') {
                    errorMessage = __('Request timed out. Please try again.', 'woocommerce-vip-paketi');
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showError(errorMessage);
            },
            complete: function() {
                isProcessing = false;
            }
        });
    }
    
    function showLoadingState() {
        hideAllStates();
        $('#wvp-add-to-cart').show();
        $('#cart-loading').show();
    }
    
    function showSuccessState(data) {
        hideAllStates();
        $('#wvp-add-to-cart').show();
        $('#cart-success').show();
        
        // Add some celebration effect
        setTimeout(function() {
            $('.success-icon').addClass('animate-success');
        }, 300);
        
        // Auto-scroll to success message
        $('html, body').animate({
            scrollTop: $('#cart-success').offset().top - 100
        }, 500);
    }
    
    function showError(message) {
        hideAllStates();
        $('#wvp-add-to-cart').show();
        $('#cart-error').show();
        $('#error-details').text(message);
        
        // Auto-scroll to error message
        $('html, body').animate({
            scrollTop: $('#cart-error').offset().top - 100
        }, 500);
    }
    
    function hideAllStates() {
        $('#cart-success, #cart-loading, #cart-error').hide();
    }
    
    function updateCartSummary() {
        // Update WooCommerce cart fragments
        $(document.body).trigger('wc_fragment_refresh');
        
        // Load mini cart summary
        $.ajax({
            url: wvp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wvp_get_cart_summary',
                nonce: wvp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mini-summary-body').html(response.data.content);
                    $('#cart-total-amount').text(response.data.total);
                    $('#cart-mini-summary').slideDown();
                }
            }
        });
    }
    
    function getSelectedProducts() {
        // This would typically be stored in a global variable or retrieved from DOM
        // For this example, we'll assume it's available globally
        return window.wvpSelectedProducts || [];
    }
    
    function getPackageData() {
        // This would typically be stored in a global variable or retrieved from DOM
        return window.wvpPackageData || {};
    }
    
    function resetPackageConfigurator() {
        // Reset all sections to initial state
        $('.wvp-package-size').removeClass('selected');
        $('.selected-size-info').hide();
        $('#wvp-product-selection').hide();
        $('#wvp-package-total').hide();
        hideAllStates();
        
        // Clear global data
        window.wvpSelectedProducts = [];
        window.wvpPackageData = {};
        
        // Scroll to top of package configurator
        $('html, body').animate({
            scrollTop: $('#wvp-package-config').offset().top - 100
        }, 500);
    }
    
    function trackPackageAddToCart(packageData) {
        // Track analytics event
        if (typeof gtag !== 'undefined') {
            gtag('event', 'add_to_cart', {
                currency: 'EUR',
                value: packageData.total || 0,
                items: packageData.products.map(function(product) {
                    return {
                        item_id: product.id,
                        item_name: product.name,
                        category: 'Package',
                        quantity: 1,
                        price: product.vip_price || product.regular_price
                    };
                })
            });
        }
        
        // Facebook Pixel tracking
        if (typeof fbq !== 'undefined') {
            fbq('track', 'AddToCart', {
                value: packageData.total || 0,
                currency: 'EUR',
                content_type: 'product_group',
                contents: packageData.products.map(function(product) {
                    return {
                        id: product.id,
                        quantity: 1
                    };
                })
            });
        }
    }
    
    function __(text, domain) {
        return text; // Simplified - in real implementation would use wp.i18n
    }
});
</script>

<style>
.wvp-add-to-cart-section {
    background: #fff;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cart-success-state,
.cart-loading-state,
.cart-error-state {
    padding: 40px 30px;
    text-align: center;
}

.success-content,
.loading-content,
.error-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.success-icon,
.loading-spinner,
.error-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
}

.success-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
    transform: scale(0);
    animation: bounceIn 0.5s ease forwards;
}

.success-icon.animate-success {
    animation: successPulse 0.6s ease;
}

.loading-spinner {
    background: #f8f9fa;
    border: 3px solid #dee2e6;
    position: relative;
}

.spinner {
    width: 30px;
    height: 30px;
    border: 3px solid transparent;
    border-top-color: #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.error-icon {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: #fff;
}

.success-message h3,
.loading-message h3,
.error-message h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 24px;
}

.success-message p,
.loading-message p,
.error-message p {
    margin: 0;
    color: #646970;
    font-size: 16px;
    line-height: 1.5;
}

.success-actions,
.error-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.view-cart-btn,
.checkout-btn,
.continue-shopping-btn,
.retry-btn,
.back-to-edit-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.view-cart-btn {
    background: #007cba;
    color: #fff;
}

.view-cart-btn:hover {
    background: #005a87;
    color: #fff;
}

.checkout-btn {
    background: #28a745;
    color: #fff;
}

.checkout-btn:hover {
    background: #218838;
    color: #fff;
}

.continue-shopping-btn {
    background: #6c757d;
    color: #fff;
}

.continue-shopping-btn:hover {
    background: #545b62;
}

.retry-btn {
    background: #ffc107;
    color: #856404;
}

.retry-btn:hover {
    background: #e0a800;
}

.back-to-edit-btn {
    background: #17a2b8;
    color: #fff;
}

.back-to-edit-btn:hover {
    background: #138496;
}

.wvp-cart-mini-summary {
    position: fixed;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    width: 320px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
    max-height: 80vh;
    overflow: hidden;
}

.mini-summary-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.summary-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.summary-header h4 {
    margin: 0;
    color: #1d2327;
    font-size: 16px;
}

.close-summary {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #999;
    padding: 0;
}

.summary-body {
    flex: 1;
    overflow-y: auto;
    padding: 15px 20px;
    max-height: 300px;
}

.summary-footer {
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-weight: bold;
    font-size: 16px;
}

.total-label {
    color: #1d2327;
}

.total-amount {
    color: #28a745;
}

.summary-actions {
    display: flex;
    gap: 10px;
}

.mini-view-cart,
.mini-checkout {
    flex: 1;
    padding: 8px 12px;
    text-align: center;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 12px;
}

.mini-view-cart {
    background: #6c757d;
    color: #fff;
}

.mini-view-cart:hover {
    background: #545b62;
    color: #fff;
}

.mini-checkout {
    background: #28a745;
    color: #fff;
}

.mini-checkout:hover {
    background: #218838;
    color: #fff;
}

@keyframes bounceIn {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes successPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    .cart-success-state,
    .cart-loading-state,
    .cart-error-state {
        padding: 30px 20px;
    }
    
    .success-actions,
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .view-cart-btn,
    .checkout-btn,
    .continue-shopping-btn,
    .retry-btn,
    .back-to-edit-btn {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
    
    .wvp-cart-mini-summary {
        position: fixed;
        top: auto;
        bottom: 0;
        right: 0;
        left: 0;
        width: auto;
        transform: none;
        border-radius: 8px 8px 0 0;
    }
    
    .summary-body {
        max-height: 200px;
    }
}
</style>