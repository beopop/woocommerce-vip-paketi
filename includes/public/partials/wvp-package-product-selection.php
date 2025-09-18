<?php
/**
 * Product selection for packages template
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

// Get available products for this package
$product_query = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_wvp_package_allowed',
            'value' => 'yes',
            'compare' => '='
        )
    ),
    'post_status' => 'publish'
]);

$products = $product_query->posts;

// If no products specifically allowed for packages, get all products
if (empty($products)) {
    $product_query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 20, // Limit to avoid performance issues
        'post_status' => 'publish'
    ]);
    $products = $product_query->posts;
}
?>

<div class="wvp-product-selection" id="wvp-product-selection" style="display: none;">
    <h3><?php _e('Izaberi Svoje Proizvode', 'woocommerce-vip-paketi'); ?></h3>
    
    <div class="selection-progress">
        <div class="progress-info">
            <span class="selected-count">0</span> / <span class="required-count">0</span> <?php _e('proizvoda izabrano', 'woocommerce-vip-paketi'); ?>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: 0%"></div>
        </div>
    </div>

    <div class="product-filters">
        <div class="filter-group">
            <label for="product-search"><?php _e('Pretraži proizvode:', 'woocommerce-vip-paketi'); ?></label>
            <input type="text" id="product-search" placeholder="<?php esc_attr_e('Kucaj za pretragu...', 'woocommerce-vip-paketi'); ?>">
        </div>
        
        <div class="filter-group">
            <label for="category-filter"><?php _e('Filtriraj po kategoriji:', 'woocommerce-vip-paketi'); ?></label>
            <select id="category-filter">
                <option value=""><?php _e('Sve kategorije', 'woocommerce-vip-paketi'); ?></option>
                <?php
                $categories = get_terms([
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true
                ]);
                foreach ($categories as $category):
                ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="sort-filter"><?php _e('Sortiraj po:', 'woocommerce-vip-paketi'); ?></label>
            <select id="sort-filter">
                <option value="name"><?php _e('Naziv', 'woocommerce-vip-paketi'); ?></option>
                <option value="price"><?php _e('Cena', 'woocommerce-vip-paketi'); ?></option>
                <option value="popularity"><?php _e('Popularnost', 'woocommerce-vip-paketi'); ?></option>
            </select>
        </div>
    </div>

    <div class="products-grid" id="products-grid">
        <?php foreach ($products as $product_post): 
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $current_price = $sale_price ? $sale_price : $regular_price;
            $vip_price = get_post_meta($product_post->ID, '_wvp_vip_price', true);
            $vip_price = $vip_price ? $vip_price : $current_price;
            $categories = wp_get_post_terms($product_post->ID, 'product_cat', ['fields' => 'names']);
            $category_names = implode(', ', $categories);
            $category_ids = wp_get_post_terms($product_post->ID, 'product_cat', ['fields' => 'ids']);
        ?>
            <div class="product-item" 
                 data-product-id="<?php echo esc_attr($product_post->ID); ?>"
                 data-product-name="<?php echo esc_attr($product_post->post_title); ?>"
                 data-regular-price="<?php echo esc_attr($regular_price); ?>"
                 data-current-price="<?php echo esc_attr($current_price); ?>"
                 data-sale-price="<?php echo esc_attr($sale_price); ?>"
                 data-vip-price="<?php echo esc_attr($vip_price); ?>"
                 data-categories="<?php echo esc_attr(implode(',', $category_ids)); ?>"
                 data-category-names="<?php echo esc_attr($category_names); ?>">
                
                <div class="product-image">
                    <?php echo $product->get_image('medium'); ?>
                    <div class="selection-overlay">
                        <span class="selection-check">✓</span>
                    </div>
                </div>
                
                <div class="product-info">
                    <h4 class="product-title"><?php echo esc_html($product_post->post_title); ?></h4>
                    
                    <?php if ($category_names): ?>
                        <div class="product-categories">
                            <small><?php echo esc_html($category_names); ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-pricing">
                        <div class="current-pricing">
                            <span class="price-label"><?php _e('REGULARNA CENA BEZ POPUSTA:', 'woocommerce-vip-paketi'); ?></span>
                            <?php if ($sale_price && $sale_price < $regular_price): ?>
                                <span class="price">
                                    <del><?php echo wc_price($regular_price); ?></del>
                                    <ins><?php echo wc_price($current_price); ?></ins>
                                </span>
                            <?php else: ?>
                                <span class="price"><?php echo wc_price($current_price); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Package discount prices will be shown here dynamically via JavaScript -->
                        <div class="package-pricing-info" style="display: none;">
                            <div class="regular-package-price">
                                <span class="price-label wvp-package-size-label"><?php _e('CENA U PAKETU ZA <span class="package-size-number">2</span> PROIZVODA:', 'woocommerce-vip-paketi'); ?></span>
                                <span class="price" data-regular-package-price="0"></span>
                            </div>
                            
                            <div class="vip-package-price <?php if (!$is_vip): ?>regular-user-vip-preview<?php endif; ?>" <?php if (!$is_vip): ?>style="display: none;"<?php endif; ?>>
                                <span class="price-label wvp-vip-package-size-label"><?php _e('CENA U PAKETU ZA <span class="package-size-number-vip">2</span> PROIZVODA (VIP):', 'woocommerce-vip-paketi'); ?></span>
                                <span class="price vip-price" data-vip-package-price="0"></span>
                            </div>
                            <?php if (!$is_vip): ?>
                            <div class="vip-upgrade-message-independent">
                                <small>Postani VIP da dobiješ tu cenu</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
                <div class="product-actions">
                    <div class="quantity-controls" style="display: none;">
                        <button type="button" class="quantity-btn minus" data-action="decrease">-</button>
                        <input type="number" class="quantity-input" value="0" min="0" max="10" readonly>
                        <button type="button" class="quantity-btn plus" data-action="increase">+</button>
                    </div>
                    <button type="button" class="select-product-btn" data-action="select">
                        <?php _e('Izaberi', 'woocommerce-vip-paketi'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="selection-summary">
        <div class="summary-content">
            <div class="summary-info">
                <h4><?php _e('Rezime Izbora', 'woocommerce-vip-paketi'); ?></h4>
                <p class="summary-text">
                    <?php _e('Izaberi proizvode da vidiš ukupnu cenu paketa', 'woocommerce-vip-paketi'); ?>
                </p>
            </div>
            
            <div class="summary-actions">
                <button type="button" class="clear-selection-btn" disabled>
                    <?php _e('Obriši Sve', 'woocommerce-vip-paketi'); ?>
                </button>
                <button type="button" class="continue-btn" disabled>
                    <?php _e('Nastavi', 'woocommerce-vip-paketi'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let selectedProducts = [];
    let selectedSize = 0;
    let packageData = {};
    let isVip = <?php echo $is_vip ? 'true' : 'false'; ?>;
    
    // Listen for package size selection
    $(document).on('wvp_package_size_selected', function(e, data) {
        selectedSize = data.size;
        packageData = {
            size: data.size,
            regularDiscount: data.regularDiscount,
            vipDiscount: data.vipDiscount,
            isVip: data.isVip
        };
        
        // Show product selection
        $('#wvp-product-selection').slideDown();
        
        // Update required count
        $('.required-count').text(selectedSize);
        
        // Update package size labels dynamically
        $('.package-size-number').text(selectedSize);
        $('.package-size-number-vip').text(selectedSize);
        
        // Reset selection
        selectedProducts = [];
        updateUI();
        
        // Clear previous selections
        $('.product-item').removeClass('selected');
        $('.select-product-btn').show();
        $('.deselect-product-btn').hide();
        
        // Calculate and show package discount prices
        updatePackageDiscountPrices();
    });
    
    // Quantity controls
    $(document).on('click', '.quantity-btn', function(e) {
        e.stopPropagation();
        const $btn = $(this);
        const $item = $btn.closest('.product-item');
        const $input = $item.find('.quantity-input');
        const productId = $item.data('product-id');
        const action = $btn.data('action');
        let currentQty = parseInt($input.val()) || 0;
        
        if (action === 'increase') {
            // Check total items limit
            if (getTotalSelectedItems() >= selectedSize) {
                showMessage('Dostigao si maksimalan broj proizvoda za ovaj paket.', 'warning');
                return;
            }
            currentQty++;
        } else if (action === 'decrease' && currentQty > 0) {
            currentQty--;
        }
        
        $input.val(currentQty);
        updateProductSelection(productId, currentQty, $item);
    });
    
    // Select product button - starts with quantity 1
    $(document).on('click', '.select-product-btn', function(e) {
        e.stopPropagation();
        const $item = $(this).closest('.product-item');
        const productId = $item.data('product-id');
        
        // Check total items limit
        if (getTotalSelectedItems() >= selectedSize) {
            showMessage('Dostigao si maksimalan broj proizvoda za ovaj paket.', 'warning');
            return;
        }
        
        // Show quantity controls and set to 1
        $(this).hide();
        $item.find('.quantity-controls').show();
        $item.find('.quantity-input').val(1);
        $item.addClass('selected');
        
        updateProductSelection(productId, 1, $item);
    });
    
    // Clear selection
    $('.clear-selection-btn').on('click', function() {
        selectedProducts = [];
        $('.product-item').removeClass('selected');
        $('.select-product-btn').show();
        $('.quantity-controls').hide();
        $('.quantity-input').val(0);
        updateUI();
    });
    
    // Continue button
    $('.continue-btn').on('click', function() {
        const totalItems = getTotalSelectedItems();
        
        if (totalItems === 0) {
            showMessage('Molimo izaberite proizvode za paket.', 'error');
            return;
        }
        
        if (totalItems !== selectedSize) {
            showMessage(`Molimo izaberite tačno ${selectedSize} proizvoda za ovaj paket. Trenutno imate ${totalItems}.`, 'error');
            return;
        }
        
        // Trigger event for next section
        console.log('DEBUG - Continue button clicked, packageData:', packageData);
        console.log('DEBUG - Selected products:', selectedProducts);
        $(document).trigger('wvp_products_selected', {
            products: selectedProducts,
            packageData: packageData
        });
        
        // Scroll to package total section
        const $totalSection = $('#wvp-package-total');
        if ($totalSection.length) {
            $('html, body').animate({
                scrollTop: $totalSection.offset().top - 100
            }, 800);
        }
    });
    
    // Search functionality
    $('#product-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterProducts();
    });
    
    // Category filter
    $('#category-filter').on('change', function() {
        filterProducts();
    });
    
    // Sort filter
    $('#sort-filter').on('change', function() {
        sortProducts($(this).val());
    });
    
    function selectProduct(productId, $item) {
        const productData = {
            id: productId,
            name: $item.data('product-name'),
            regular_price: parseFloat($item.data('current-price')),
            vip_price: parseFloat($item.data('vip-price')) || parseFloat($item.data('current-price'))
        };
        
        selectedProducts.push(productData);
        $item.addClass('selected');
        $item.find('.select-product-btn').hide();
        $item.find('.deselect-product-btn').show();
        
        updateUI();
    }
    
    function deselectProduct(productId, $item) {
        selectedProducts = selectedProducts.filter(p => p.id != productId);
        $item.removeClass('selected');
        $item.find('.select-product-btn').show();
        $item.find('.deselect-product-btn').hide();
        
        updateUI();
    }
    
    function updateUI() {
        const selectedCount = selectedProducts.length;
        
        // Update progress
        $('.selected-count').text(selectedCount);
        const progressPercent = selectedSize > 0 ? (selectedCount / selectedSize) * 100 : 0;
        $('.progress-fill').css('width', progressPercent + '%');
        
        // Update buttons
        $('.clear-selection-btn').prop('disabled', selectedCount === 0);
        $('.continue-btn').prop('disabled', selectedCount === 0);
        
        // Update summary text
        if (selectedCount === 0) {
            $('.summary-text').text(__('Select products to see your package total', 'woocommerce-vip-paketi'));
        } else {
            const remaining = selectedSize - selectedCount;
            if (remaining > 0) {
                $('.summary-text').text(
                    sprintf(__('Select %d more products to complete your package', 'woocommerce-vip-paketi'), remaining)
                );
            } else {
                $('.summary-text').text(__('Package complete! Click continue to proceed.', 'woocommerce-vip-paketi'));
            }
        }
    }
    
    function filterProducts() {
        const searchTerm = $('#product-search').val().toLowerCase();
        const categoryFilter = $('#category-filter').val();
        
        $('.product-item').each(function() {
            const $item = $(this);
            const productName = $item.data('product-name').toLowerCase();
            const categoryNames = $item.data('category-names').toLowerCase();
            const categories = $item.data('categories').toString().split(',');
            
            let showItem = true;
            
            // Search filter
            if (searchTerm && !productName.includes(searchTerm) && !categoryNames.includes(searchTerm)) {
                showItem = false;
            }
            
            // Category filter
            if (categoryFilter && !categories.includes(categoryFilter)) {
                showItem = false;
            }
            
            $item.toggle(showItem);
        });
    }
    
    function sortProducts(sortBy) {
        const $grid = $('#products-grid');
        const $items = $grid.children('.product-item');
        
        $items.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);
            
            switch(sortBy) {
                case 'name':
                    return $a.data('product-name').localeCompare($b.data('product-name'));
                case 'price':
                    const priceA = isVip ? parseFloat($a.data('vip-price')) || parseFloat($a.data('regular-price')) : parseFloat($a.data('regular-price'));
                    const priceB = isVip ? parseFloat($b.data('vip-price')) || parseFloat($b.data('regular-price')) : parseFloat($b.data('regular-price'));
                    return priceA - priceB;
                default:
                    return 0;
            }
        });
        
        $grid.append($items);
    }
    
    // Update product selection with quantity
    function updateProductSelection(productId, quantity, $item) {
        const productData = {
            id: productId,
            name: $item.data('product-name'),
            regular_price: parseFloat($item.data('current-price')),
            vip_price: parseFloat($item.data('vip-price')) || parseFloat($item.data('current-price')),
            quantity: quantity
        };
        
        // Remove existing entry
        selectedProducts = selectedProducts.filter(p => p.id !== productId);
        
        // Add new entry if quantity > 0
        if (quantity > 0) {
            selectedProducts.push(productData);
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
            $item.find('.select-product-btn').show();
            $item.find('.quantity-controls').hide();
        }
        
        updateUI();
    }
    
    // Get total selected items count (sum of all quantities)
    function getTotalSelectedItems() {
        return selectedProducts.reduce((total, product) => total + product.quantity, 0);
    }
    
    // Update UI elements
    function updateUI() {
        const totalItems = getTotalSelectedItems();
        const progress = selectedSize > 0 ? (totalItems / selectedSize) * 100 : 0;
        
        // Update progress
        $('.selected-count').text(totalItems);
        $('.progress-fill').css('width', Math.min(progress, 100) + '%');
        
        // Update buttons
        const hasSelection = selectedProducts.length > 0;
        $('.clear-selection-btn').prop('disabled', !hasSelection);
        $('.continue-btn').prop('disabled', totalItems !== selectedSize);
        
        // Update progress color based on completion
        if (totalItems === selectedSize) {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #28a745 0%, #20c997 100%)');
        } else if (totalItems > selectedSize) {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #dc3545 0%, #fd7e14 100%)');
        } else {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #007cba 0%, #00a0d2 100%)');
        }
        
        // Update summary text
        if (totalItems === 0) {
            $('.summary-text').text('Izaberi proizvode da vidiš ukupnu cenu paketa');
        } else if (totalItems < selectedSize) {
            const remaining = selectedSize - totalItems;
            $('.summary-text').text(`Izaberi još ${remaining} proizvoda da završiš svoj paket`);
        } else if (totalItems === selectedSize) {
            $('.summary-text').text('Paket završen! Klikni nastavi da nastaviš.');
        } else {
            $('.summary-text').text('Dostigao si maksimalan broj proizvoda za ovaj paket.');
        }
    }
    
    // Function to calculate and display package discount prices
    function updatePackageDiscountPrices() {
        if (!packageData || !selectedSize) return;
        
        // Get package discounts from packageData (regular and VIP)
        const regularDiscount = parseFloat(packageData.regularDiscount) || 0;
        const vipDiscount = parseFloat(packageData.vipDiscount) || 0;
        
        $('.product-item').each(function() {
            const $item = $(this);
            const currentPrice = parseFloat($item.data('current-price'));
            
            // Calculate discounted prices
            const regularPackagePrice = currentPrice * (1 - (regularDiscount / 100));
            const vipPackagePrice = currentPrice * (1 - ((regularDiscount + vipDiscount) / 100));
            
            // Update package price displays
            const $packagePricing = $item.find('.package-pricing-info');
            const $regularPackagePrice = $item.find('[data-regular-package-price]');
            const $vipPackagePrice = $item.find('[data-vip-package-price]');
            
            // Format prices using WordPress currency format
            $regularPackagePrice.attr('data-regular-package-price', regularPackagePrice.toFixed(2));
            $regularPackagePrice.html(formatPrice(regularPackagePrice));
            
            // Always show VIP package price for both VIP and regular users
            if ($vipPackagePrice.length) {
                $vipPackagePrice.attr('data-vip-package-price', vipPackagePrice.toFixed(2));
                $vipPackagePrice.html(formatPrice(vipPackagePrice));
                
                // Show VIP price section for regular users as well
                if (!isVip) {
                    $vipPackagePrice.closest('.vip-package-price').show();
                    // Also show the independent upgrade message
                    $vipPackagePrice.closest('.package-pricing-info').find('.vip-upgrade-message-independent').show();
                }
            }
            
            
            // Show package pricing info
            $packagePricing.slideDown();
        });
    }
    
    // Format price helper function
    function formatPrice(price) {
        // Simple price formatting - in real implementation would use WooCommerce formatting
        return price.toLocaleString('sr-RS', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }) + ' рсд';
    }
    
    function showMessage(message, type) {
        // Simple message display - could be enhanced with a proper notification system
        alert(message);
    }
    
    // Internationalization helper
    function __(text, domain) {
        return text; // Simplified - in real implementation would use wp.i18n
    }
    
    function sprintf(format, ...args) {
        return format.replace(/%d/g, () => args.shift());
    }
});
</script>

<style>
.wvp-product-selection {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wvp-product-selection h3 {
    margin-top: 0;
    color: #1d2327;
    font-size: 24px;
    margin-bottom: 25px;
}

.selection-progress {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 25px;
}

.progress-info {
    text-align: center;
    margin-bottom: 10px;
    font-weight: 600;
    color: #1d2327;
}

.progress-bar {
    height: 8px;
    background: #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    transition: width 0.3s ease;
}

.product-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.product-item {
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.product-item:hover {
    border-color: #adb5bd;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-item.selected {
    border-color: #28a745;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
}

.product-image {
    position: relative;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: auto;
    object-fit: contain;
    max-height: 250px;
}

.selection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(40, 167, 69, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-item.selected .selection-overlay {
    opacity: 1;
}

.selection-check {
    color: #fff;
    font-size: 48px;
    font-weight: bold;
}

.product-info {
    padding: 15px;
}

.product-title {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1.3;
}

.product-categories {
    margin-bottom: 12px;
}

.product-categories small {
    color: #646970;
    font-style: italic;
}

.product-pricing {
    margin-bottom: 10px;
}

.current-pricing {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
}

.package-pricing-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 10px;
    border-radius: 6px;
    margin-top: 8px;
}

.regular-package-price,
.vip-package-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.regular-package-price:last-child,
.vip-package-price:last-child {
    margin-bottom: 0;
}

.vip-package-price {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    padding: 6px 8px;
    border-radius: 4px;
    margin: 6px -8px 0 -8px;
}

.vip-package-price .price {
    color: #856404;
    font-weight: bold;
}

.price-calculation {
    margin-top: 4px;
    text-align: center;
}

.price-calculation small {
    color: #6c757d;
    font-style: italic;
    font-size: 11px;
    line-height: 1.2;
}

.regular-pricing,
.vip-pricing {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.price-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 600;
}

.price {
    font-weight: bold;
    color: #1d2327;
}

.vip-pricing {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    padding: 6px 8px;
    border-radius: 4px;
    margin: 0 -8px 8px -8px;
}

.vip-pricing.inactive {
    opacity: 0.7;
    filter: blur(1px);
}

.savings {
    font-size: 11px;
    color: #28a745;
    font-weight: bold;
    margin-left: 5px;
}

.vip-upgrade-hint {
    font-style: italic;
    color: #856404;
    text-align: center;
    padding: 5px;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 3px;
}

/* VIP Price Preview for Regular Users */
.regular-user-vip-preview {
    filter: blur(1px);
    opacity: 0.8;
    position: relative;
}

.regular-user-vip-preview:hover {
    filter: blur(0.5px);
    opacity: 0.9;
    transition: all 0.3s ease;
}

/* Independent VIP upgrade message - outside blur effect */
.vip-upgrade-message-independent {
    text-align: center;
    margin-top: 8px;
    padding: 0;
    background: none;
}

.vip-upgrade-message-independent small {
    color: #d4a017;
    font-weight: 600;
    font-style: italic;
    font-size: 11px;
    text-transform: none;
    letter-spacing: 0.3px;
}

.product-actions {
    padding: 15px;
    border-top: 1px solid #dee2e6;
}

.select-product-btn,
.deselect-product-btn {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.select-product-btn {
    background: #28a745;
    color: #fff;
}

.select-product-btn:hover {
    background: #218838;
}

.deselect-product-btn {
    background: #dc3545;
    color: #fff;
}

.deselect-product-btn:hover {
    background: #c82333;
}

.selection-summary {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 1px solid #2196f3;
    border-radius: 6px;
    padding: 20px;
}

.summary-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.summary-info h4 {
    margin: 0 0 5px 0;
    color: #0d47a1;
}

.summary-text {
    margin: 0;
    color: #1565c0;
}

.summary-actions {
    display: flex;
    gap: 10px;
}

.clear-selection-btn,
.continue-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.clear-selection-btn {
    background: #6c757d;
    color: #fff;
}

.clear-selection-btn:hover:not(:disabled) {
    background: #545b62;
}

.continue-btn {
    background: #28a745;
    color: #fff;
}

.continue-btn:hover:not(:disabled) {
    background: #218838;
}

.clear-selection-btn:disabled,
.continue-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Quantity Controls */
.quantity-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 2px solid #28a745;
    background: #fff;
    color: #28a745;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
    transition: all 0.2s ease;
}

.quantity-btn:hover {
    background: #28a745;
    color: #fff;
    transform: scale(1.1);
}

.quantity-btn:active {
    transform: scale(0.95);
}

.quantity-input {
    width: 50px;
    height: 35px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    background: #f8f9fa;
    color: #1d2327;
}

.product-item.selected {
    border-color: #28a745;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.product-item.selected .selection-overlay {
    opacity: 1;
    visibility: visible;
}

.product-item.selected .quantity-controls {
    border: 2px solid #28a745;
    border-radius: 6px;
    padding: 8px;
    background: rgba(255, 255, 255, 0.9);
}

.product-item.selected .quantity-btn {
    border-color: #28a745;
    color: #28a745;
}

.product-item.selected .quantity-btn:hover {
    background: #28a745;
    color: #fff;
}

/* Responsive grid adjustments */
@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .wvp-product-selection {
        padding: 20px;
    }
    
    .product-filters {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .summary-content {
        flex-direction: column;
        text-align: center;
    }
    
    .summary-actions {
        width: 100%;
        justify-content: center;
    }
}

/* Ensure proper grid on larger screens */
@media (min-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>