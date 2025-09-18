<?php
/**
 * Provide a admin area view for products configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo '<div class="notice notice-error"><p>' . __('WooCommerce je potreban da bi ovaj plugin radio.', 'woocommerce-vip-paketi') . '</p></div>';
    return;
}

// Helper functions - only define if not already defined
if (!function_exists('wvp_format_price')) {
    function wvp_format_price($price) {
        if (function_exists('wc_price') && $price) {
            return wc_price($price);
        }
        $currency = get_option('woocommerce_currency', 'EUR');
        $symbol = '€';
        if ($currency === 'USD') $symbol = '$';
        elseif ($currency === 'RSD') $symbol = 'РСД ';
        
        return $symbol . number_format((float)$price, 2);
    }
}

if (!function_exists('wvp_get_currency_symbol')) {
    function wvp_get_currency_symbol() {
        if (function_exists('get_woocommerce_currency_symbol')) {
            return get_woocommerce_currency_symbol();
        }
        $currency = get_option('woocommerce_currency', 'EUR');
        if ($currency === 'USD') return '$';
        elseif ($currency === 'RSD') return 'РСД ';
        return '€';
    }
}

// Get all products (simple and variable)
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);

$products_query = new WP_Query($args);
$products = $products_query->posts;

// Check if we have products
if (empty($products)) {
    ?>
    <div class="wrap">
        <h1><?php _e('Podešavanja Proizvoda', 'woocommerce-vip-paketi'); ?></h1>
        <div class="notice notice-info">
            <p><?php _e('Nema pronađenih proizvoda. Molimo kreiraj prvo neke WooCommerce proizvode.', 'woocommerce-vip-paketi'); ?></p>
            <p><a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary"><?php _e('Kreiraj Proizvod', 'woocommerce-vip-paketi'); ?></a></p>
        </div>
    </div>
    <?php
    return;
}

// Get current package-allowed products
$package_allowed_products = get_option('wvp_package_allowed_products', array());
if (!is_array($package_allowed_products)) {
    $package_allowed_products = array();
}

// Handle form submission
if (isset($_POST['wvp_update_products']) && wp_verify_nonce($_POST['wvp_products_nonce'], 'wvp_update_products')) {
    $new_allowed_products = isset($_POST['package_allowed_products']) ? array_map('intval', $_POST['package_allowed_products']) : array();
    
    update_option('wvp_package_allowed_products', $new_allowed_products);
    
    // Update individual product meta
    foreach ($products as $product) {
        $enable_vip = isset($_POST['vip_pricing'][$product->ID]) ? 'yes' : 'no';
        $vip_price = isset($_POST['vip_prices'][$product->ID]) ? floatval(str_replace(',', '.', $_POST['vip_prices'][$product->ID])) : '';
        $package_allowed = isset($_POST['package_allowed_products']) && in_array($product->ID, $_POST['package_allowed_products']) ? 'yes' : 'no';
        
        update_post_meta($product->ID, '_wvp_enable_vip_pricing', $enable_vip);
        update_post_meta($product->ID, '_wvp_package_allowed', $package_allowed);
        
        if ($enable_vip === 'yes' && $vip_price) {
            update_post_meta($product->ID, '_wvp_vip_price', $vip_price);
        }
    }
    
    echo '<div class="notice notice-success"><p>' . __('Podešavanja proizvoda su uspešno ažurirana.', 'woocommerce-vip-paketi') . '</p></div>';
    
    // Refresh package allowed products
    $package_allowed_products = $new_allowed_products;
}

// Get statistics
$total_products = count($products);
$vip_enabled_products = 0;
$package_allowed_count = count($package_allowed_products);

foreach ($products as $product) {
    if (get_post_meta($product->ID, '_wvp_enable_vip_pricing', true) === 'yes') {
        $vip_enabled_products++;
    }
}

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($current_page - 1) * $per_page;
$paged_products = array_slice($products, $offset, $per_page);
$total_pages = $total_products > 0 ? ceil($total_products / $per_page) : 1;
?>

<div class="wrap wvp-admin-page">
    <div class="wvp-admin-header">
        <h1><?php _e('Podešavanja Proizvoda', 'woocommerce-vip-paketi'); ?></h1>
        <p><?php _e('Podesi VIP cene i dostupnost paketa za svoje proizvode.', 'woocommerce-vip-paketi'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="wvp-stats-grid">
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($total_products); ?></div>
            <p class="wvp-stat-label"><?php _e('Ukupno Proizvoda', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($vip_enabled_products); ?></div>
            <p class="wvp-stat-label"><?php _e('VIP Cene Uključene', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($package_allowed_count); ?></div>
            <p class="wvp-stat-label"><?php _e('Paket Dozvoljen', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($total_products - $vip_enabled_products); ?></div>
            <p class="wvp-stat-label"><?php _e('Samo Redovne Cene', 'woocommerce-vip-paketi'); ?></p>
        </div>
    </div>

    <?php if (empty($products)): ?>
    <!-- Empty State -->
    <div class="wvp-empty-state" style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
        <div style="font-size: 48px; margin-bottom: 20px;">📋</div>
        <h2><?php _e('Nema jednostavnih proizvoda', 'woocommerce-vip-paketi'); ?></h2>
        <p><?php _e('Potrebni su ti jednostavni proizvodi u prodavnici da bi podesi VIP cene i pakete.', 'woocommerce-vip-paketi'); ?></p>
        <p>
            <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Dodaj Svoj Prvi Proizvod', 'woocommerce-vip-paketi'); ?>
            </a>
        </p>
        <p><?php _e('Napomena: Samo jednostavni proizvodi su podržani. Varijabilni proizvodi nisu kompatibilni sa ovim sistemom.', 'woocommerce-vip-paketi'); ?></p>
    </div>

    <?php else: ?>

    <form method="post" id="wvp-products-form">
        <?php wp_nonce_field('wvp_update_products', 'wvp_products_nonce'); ?>
        
        <!-- Bulk Actions -->
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector" class="screen-reader-text"><?php _e('Izaberi masovnu akciju', 'woocommerce-vip-paketi'); ?></label>
                <select name="bulk_action" id="bulk-action-selector">
                    <option value="-1"><?php _e('Masovne Akcije', 'woocommerce-vip-paketi'); ?></option>
                    <option value="enable_vip"><?php _e('Uključi VIP Cene', 'woocommerce-vip-paketi'); ?></option>
                    <option value="disable_vip"><?php _e('Isključi VIP Cene', 'woocommerce-vip-paketi'); ?></option>
                    <option value="enable_package"><?php _e('Dozvoli u Paketima', 'woocommerce-vip-paketi'); ?></option>
                    <option value="disable_package"><?php _e('Zabrani u Paketima', 'woocommerce-vip-paketi'); ?></option>
                </select>
                <button type="button" id="apply-bulk-action" class="button action"><?php _e('Primeni', 'woocommerce-vip-paketi'); ?></button>
            </div>
            
            <div class="alignright actions">
                <button type="button" id="wvp-select-all-products" class="button"><?php _e('Izaberi Sve na Stranici', 'woocommerce-vip-paketi'); ?></button>
                <button type="button" id="wvp-deselect-all-products" class="button"><?php _e('Poništi Izbor', 'woocommerce-vip-paketi'); ?></button>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="wvp-products-grid">
            <?php foreach ($paged_products as $product): 
                // Safely get product data
                $regular_price = get_post_meta($product->ID, '_regular_price', true);
                if (empty($regular_price)) {
                    $regular_price = get_post_meta($product->ID, '_price', true);
                }
                $vip_enabled = get_post_meta($product->ID, '_wvp_enable_vip_pricing', true) === 'yes';
                $vip_price = get_post_meta($product->ID, '_wvp_vip_price', true);
                $package_allowed = in_array($product->ID, $package_allowed_products);
                $thumbnail = get_the_post_thumbnail($product->ID, 'thumbnail');
            ?>
            <div class="wvp-product-card <?php echo $vip_enabled ? 'vip-enabled' : ''; ?> <?php echo $package_allowed ? 'package-enabled' : ''; ?>">
                <div class="product-header">
                    <input type="checkbox" name="selected_products[]" value="<?php echo esc_attr($product->ID); ?>" class="product-selector" />
                    <div class="product-thumbnail">
                        <?php if ($thumbnail): ?>
                            <?php echo $thumbnail; ?>
                        <?php else: ?>
                            <div class="no-thumbnail">📦</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-info">
                    <h3 class="wvp-product-title">
                        <a href="<?php echo get_edit_post_link($product->ID); ?>" target="_blank">
                            <?php echo esc_html($product->post_title); ?>
                        </a>
                    </h3>
                    
                    <div class="product-price">
                        <strong><?php _e('Regularna Cena:', 'woocommerce-vip-paketi'); ?></strong>
                        <?php echo wvp_format_price($regular_price); ?>
                    </div>
                    
                    <div class="vip-pricing-section">
                        <label class="vip-toggle">
                            <input type="checkbox" name="vip_pricing[<?php echo esc_attr($product->ID); ?>]" value="yes" <?php checked($vip_enabled, true); ?> />
                            <span class="toggle-label"><?php _e('Uključi VIP Cene', 'woocommerce-vip-paketi'); ?></span>
                        </label>
                        
                        <div class="vip-price-input" style="<?php echo $vip_enabled ? '' : 'display: none;'; ?>">
                            <label><?php _e('VIP Cena:', 'woocommerce-vip-paketi'); ?></label>
                            <div class="price-input-wrapper">
                                <span class="currency-symbol"><?php echo wvp_get_currency_symbol(); ?></span>
                                <input type="number" 
                                       name="vip_prices[<?php echo esc_attr($product->ID); ?>]" 
                                       value="<?php echo esc_attr($vip_price); ?>" 
                                       step="0.01" 
                                       min="0" 
                                       max="<?php echo esc_attr($regular_price); ?>"
                                       placeholder="<?php echo esc_attr($regular_price); ?>" />
                            </div>
                            <?php if ($vip_price && $regular_price && $vip_price < $regular_price): ?>
                            <div class="savings-info">
                                <?php 
                                $savings = $regular_price - $vip_price;
                                $savings_percent = round(($savings / $regular_price) * 100, 1);
                                ?>
                                <small style="color: #28a745;">
                                    <?php printf(__('Ušteda %s (%s%%)', 'woocommerce-vip-paketi'), wvp_format_price($savings), $savings_percent); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="package-section">
                        <label class="package-toggle">
                            <input type="checkbox" 
                                   name="package_allowed_products[]" 
                                   value="<?php echo esc_attr($product->ID); ?>" 
                                   <?php checked($package_allowed, true); ?> />
                            <span class="toggle-label"><?php _e('Dozvoli u Paketima', 'woocommerce-vip-paketi'); ?></span>
                        </label>
                    </div>
                </div>
                
                <div class="product-actions">
                    <a href="<?php echo get_edit_post_link($product->ID); ?>" class="button button-small" target="_blank">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Uredi Proizvod', 'woocommerce-vip-paketi'); ?>
                    </a>
                    <a href="<?php echo get_permalink($product->ID); ?>" class="button button-small" target="_blank">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Pogledaj', 'woocommerce-vip-paketi'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $big = 999999999;
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo; ' . __('Prethodna', 'woocommerce-vip-paketi'),
                    'next_text' => __('Sledeća', 'woocommerce-vip-paketi') . ' &raquo;'
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="submit-section" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px; margin-top: 20px; text-align: center;">
            <button type="submit" name="wvp_update_products" class="button button-primary button-hero">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Sačuvaj Sva Podešavanja Proizvoda', 'woocommerce-vip-paketi'); ?>
            </button>
            <p class="description"><?php _e('Ovo će ažurirati VIP cene i podešavanja paketa za sve proizvode na ovoj stranici.', 'woocommerce-vip-paketi'); ?></p>
        </div>
    </form>

    <?php endif; ?>

    <!-- Quick Actions Panel -->
    <div class="wvp-quick-actions" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-top: 20px;">
        <h3><?php _e('Brze Akcije', 'woocommerce-vip-paketi'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="quick-action">
                <h4><?php _e('Primeni VIP Procenat Popusta', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Automatski izračunaj VIP cene na osnovu procenta popusta.', 'woocommerce-vip-paketi'); ?></p>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="number" id="discount-percentage" min="1" max="50" step="0.1" placeholder="15" style="width: 80px;" />
                    <span>% <?php _e('popusta', 'woocommerce-vip-paketi'); ?></span>
                    <button type="button" id="apply-discount" class="button"><?php _e('Primeni na Izabrane', 'woocommerce-vip-paketi'); ?></button>
                </div>
            </div>
            
            <div class="quick-action">
                <h4><?php _e('Uvoz/Izvoz Podešavanja', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Pravi rezervnu kopiju ili masovni uvoz VIP podešavanja proizvoda preko CSV.', 'woocommerce-vip-paketi'); ?></p>
                <div style="margin-top: 10px;">
                    <button type="button" id="export-settings" class="button"><?php _e('Izvezi CSV', 'woocommerce-vip-paketi'); ?></button>
                    <button type="button" id="import-settings" class="button"><?php _e('Uvezi CSV', 'woocommerce-vip-paketi'); ?></button>
                </div>
            </div>
            
            <div class="quick-action">
                <h4><?php _e('Resetuj Sva Podešavanja', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Obriši sva VIP cene i podešavanja paketa za novi početak.', 'woocommerce-vip-paketi'); ?></p>
                <div style="margin-top: 10px;">
                    <button type="button" id="reset-vip-settings" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Ovo će ukloniti sva podešavanja VIP cena. Da li ste sigurni?', 'woocommerce-vip-paketi'); ?>')"><?php _e('Resetuj VIP Cene', 'woocommerce-vip-paketi'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // VIP pricing toggle
    $('input[name*="vip_pricing"]').on('change', function() {
        const $card = $(this).closest('.wvp-product-card');
        const $priceInput = $card.find('.vip-price-input');
        
        if ($(this).is(':checked')) {
            $priceInput.show();
            $card.addClass('vip-enabled');
        } else {
            $priceInput.hide();
            $card.removeClass('vip-enabled');
        }
    });
    
    // Package allowed toggle
    $('input[name*="package_allowed_products"]').on('change', function() {
        const $card = $(this).closest('.wvp-product-card');
        
        if ($(this).is(':checked')) {
            $card.addClass('package-enabled');
        } else {
            $card.removeClass('package-enabled');
        }
    });
    
    // VIP price calculation on input
    $('input[name*="vip_prices"]').on('input', function() {
        const $card = $(this).closest('.wvp-product-card');
        const regularPrice = parseFloat($card.find('.product-price').text().replace(/[^0-9.]/g, ''));
        const vipPrice = parseFloat($(this).val());
        const $savingsInfo = $card.find('.savings-info');
        
        if (vipPrice && regularPrice && vipPrice < regularPrice) {
            const savings = regularPrice - vipPrice;
            const savingsPercent = Math.round((savings / regularPrice) * 100 * 10) / 10;
            $savingsInfo.html('<small style="color: #28a745;">Saves ' + savings.toFixed(2) + ' (' + savingsPercent + '%)</small>').show();
        } else {
            $savingsInfo.hide();
        }
    });
    
    // Select/Deselect all
    $('#wvp-select-all-products').on('click', function() {
        $('.product-selector').prop('checked', true);
    });
    
    $('#wvp-deselect-all-products').on('click', function() {
        $('.product-selector').prop('checked', false);
    });
    
    // Bulk actions
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedProducts = $('.product-selector:checked');
        
        if (action === '-1') {
            alert('<?php esc_js(__('Molimo izaberite grupnu akciju.', 'woocommerce-vip-paketi')); ?>');
            return;
        }
        
        if (selectedProducts.length === 0) {
            alert('<?php esc_js(__('Molimo izaberite najmanje jedan proizvod.', 'woocommerce-vip-paketi')); ?>');
            return;
        }
        
        selectedProducts.each(function() {
            const productId = $(this).val();
            const $card = $(this).closest('.wvp-product-card');
            
            switch (action) {
                case 'enable_vip':
                    $card.find('input[name*="vip_pricing"]').prop('checked', true).trigger('change');
                    break;
                case 'disable_vip':
                    $card.find('input[name*="vip_pricing"]').prop('checked', false).trigger('change');
                    break;
                case 'enable_package':
                    $card.find('input[name*="package_allowed_products"]').prop('checked', true).trigger('change');
                    break;
                case 'disable_package':
                    $card.find('input[name*="package_allowed_products"]').prop('checked', false).trigger('change');
                    break;
            }
        });
    });
    
    // Apply discount percentage
    $('#apply-discount').on('click', function() {
        const discountPercent = parseFloat($('#discount-percentage').val());
        const selectedProducts = $('.product-selector:checked');
        
        if (!discountPercent || discountPercent <= 0 || discountPercent >= 100) {
            alert('<?php esc_js(__('Molimo unesite valjan procenat popusta između 1-99%.', 'woocommerce-vip-paketi')); ?>');
            return;
        }
        
        if (selectedProducts.length === 0) {
            alert('<?php esc_js(__('Molimo izaberite najmanje jedan proizvod.', 'woocommerce-vip-paketi')); ?>');
            return;
        }
        
        selectedProducts.each(function() {
            const $card = $(this).closest('.wvp-product-card');
            const regularPriceText = $card.find('.product-price').text();
            const regularPrice = parseFloat(regularPriceText.replace(/[^0-9.]/g, ''));
            
            if (regularPrice && regularPrice > 0) {
                const vipPrice = regularPrice * (1 - discountPercent / 100);
                $card.find('input[name*="vip_prices"]').val(vipPrice.toFixed(2)).trigger('input');
                $card.find('input[name*="vip_pricing"]').prop('checked', true).trigger('change');
            }
        });
    });
});
</script>

<style>
.wvp-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wvp-product-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    transition: all 0.3s ease;
}

.wvp-product-card.vip-enabled {
    border-left: 4px solid #d4a017;
}

.wvp-product-card.package-enabled {
    border-right: 4px solid #007cba;
}

.wvp-product-card.vip-enabled.package-enabled {
    border-left: 4px solid #d4a017;
    border-right: 4px solid #007cba;
}

.product-header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 15px;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    overflow: hidden;
    border-radius: 4px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
}

.product-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-thumbnail {
    font-size: 20px;
    color: #999;
}

.wvp-product-title {
    margin: 0 0 10px 0;
    font-size: 14px;
    line-height: 1.3;
}

.wvp-product-title a {
    text-decoration: none;
    color: #2271b1;
}

.wvp-product-title a:hover {
    color: #135e96;
}

.product-price {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f1;
    font-size: 13px;
}

.vip-pricing-section,
.package-section {
    margin-bottom: 15px;
}

.vip-toggle,
.package-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    font-weight: 500;
}

.toggle-label {
    cursor: pointer;
}

.vip-price-input {
    padding-left: 20px;
}

.vip-price-input label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 500;
}

.price-input-wrapper {
    display: flex;
    align-items: center;
    gap: 5px;
}

.currency-symbol {
    color: #666;
    font-weight: bold;
}

.price-input-wrapper input {
    width: 100px;
}

.savings-info {
    margin-top: 5px;
}

.product-actions {
    display: flex;
    gap: 8px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f1;
}

.product-actions .button {
    flex: 1;
    text-align: center;
    font-size: 11px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
}

.quick-action {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fafafa;
}

.quick-action h4 {
    margin-top: 0;
    color: #1d2327;
}

@media (max-width: 782px) {
    .wvp-products-grid {
        grid-template-columns: 1fr;
    }
    
    .product-actions {
        flex-direction: column;
    }
}
</style>