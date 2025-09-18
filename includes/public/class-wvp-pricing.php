<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Pricing {

    public function __construct() {
        
    }

    public function modify_price_html($price_html, $product) {
        if (get_option('wvp_enable_vip_pricing') !== 'yes') {
            return $price_html;
        }

        if (!$product || $product->is_type('variable')) {
            return $price_html;
        }

        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        if ($vip_enabled !== 'yes') {
            return $price_html;
        }

        $vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
        if (!$vip_price) {
            return $price_html;
        }

        return $this->format_vip_price_html($product, $vip_price, $price_html);
    }

    public function force_vip_prices_in_cart($cart) {
        // Enable VIP pricing by default if user is VIP
        $is_vip = $this->is_user_vip();
        $session_vip = WC()->session && WC()->session->get('wvp_vip_pricing_active');
        
        if (($is_vip || $session_vip) && get_option('wvp_enable_vip_pricing') !== 'yes') {
            update_option('wvp_enable_vip_pricing', 'yes');
        }
        
        if (get_option('wvp_enable_vip_pricing') !== 'yes') {
            return;
        }
        
        error_log('WVP DEBUG: force_vip_prices_in_cart called (priority 10) - User is VIP: ' . ($is_vip ? 'YES' : 'NO') . ', Session VIP: ' . ($session_vip ? 'YES' : 'NO'));
        error_log('WVP DEBUG: Cart items count: ' . count($cart->get_cart()));
        
        // Force recalculation if user is VIP
        if ($is_vip || $session_vip) {
            $cart->set_session();
        }
        
        // Check if there are any packages in cart to apply package discounts
        $package_discounts = $this->get_package_discounts_from_cart($cart);
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Handle package items - set their correct package price
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                error_log('WVP DEBUG: force_vip_prices_in_cart - Processing package item, wvp_package_total: ' . $package_total);
                error_log('WVP DEBUG: force_vip_prices_in_cart - Package has wvp_is_vip_user: ' . (isset($cart_item['wvp_is_vip_user']) ? ($cart_item['wvp_is_vip_user'] ? 'YES' : 'NO') : 'NOT SET'));
                if ($package_total > 0) {
                    $cart_item['data']->set_price($package_total);
                    error_log('WVP DEBUG: force_vip_prices_in_cart - Set package price: ' . $package_total);
                }
                continue;
            }
            
            // Skip fixed price items - they have their own pricing logic
            if (isset($cart_item['wvp_fixed_price_applied']) && $cart_item['wvp_fixed_price_applied']) {
                error_log('WVP: force_vip_prices_in_cart - Skipping fixed price item');
                continue;
            }
            
            $product = $cart_item['data'];
            
            // Get current effective price (sale price if available, otherwise regular price)
            $current_price = $product->get_sale_price();
            if (!$current_price) {
                $current_price = $product->get_regular_price();
            }
            
            // REMOVED: Package discounts should NOT apply to individual products
            // Individual products should only get VIP pricing, not package discounts
            
            // Default VIP pricing logic for non-package items
            if (!$is_vip && !$session_vip) {
                continue;
            }
            
            // Auto-enable VIP pricing for all products
            update_post_meta($product->get_id(), '_wvp_enable_vip_pricing', 'yes');
            
            // Ensure VIP pricing is globally enabled
            update_option('wvp_enable_vip_pricing', 'yes');
            
            // Get stored VIP price first, fallback to dynamic calculation
            $stored_vip_price = (float) get_post_meta($product->get_id(), '_wvp_vip_price', true);
            
            error_log('WVP DEBUG: Product ' . $product->get_id() . ' - Current price: ' . $current_price . ', Stored VIP price: ' . $stored_vip_price);
            
            if ($stored_vip_price > 0) {
                // Use stored VIP price from database
                $product->set_price($stored_vip_price);
                error_log('WVP: force_vip_prices_in_cart - Set stored VIP price ' . $stored_vip_price . ' for product ' . $product->get_id());
            } else if (is_numeric($current_price) && $current_price > 0) {
                // Fallback to dynamic calculation (57% discount to match displayed price)
                $vip_price = round($current_price * 0.43, 2); // 57% discount = 43% of original
                $product->set_price($vip_price);
                error_log('WVP: force_vip_prices_in_cart - Set dynamic VIP price ' . $vip_price . ' for product ' . $product->get_id() . ' (57% discount from current price: ' . $current_price . ')');
            }
        }
    }

    public function force_vip_prices_in_cart_on_add() {
        if (WC()->cart) {
            $this->force_vip_prices_in_cart(WC()->cart);
        }
    }

    public function force_vip_prices_in_cart_wrapper() {
        if (WC()->cart) {
            $this->force_vip_prices_in_cart(WC()->cart);
        }
    }

    /**
     * Get package discounts from cart if any packages exist
     */
    private function get_package_discounts_from_cart($cart) {
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                $regular_discount = isset($cart_item['wvp_regular_discount_percent']) ? floatval($cart_item['wvp_regular_discount_percent']) : 0;
                $vip_discount = isset($cart_item['wvp_vip_discount_percent']) ? floatval($cart_item['wvp_vip_discount_percent']) : 0;
                
                return array(
                    'regular_discount' => $regular_discount,
                    'vip_discount' => $vip_discount
                );
            }
        }
        return false;
    }

    /**
     * Calculate package discount price for individual product
     */
    private function calculate_individual_package_price($current_price, $package_discounts, $is_vip) {
        $regular_discount = $package_discounts['regular_discount'];
        $vip_discount = $package_discounts['vip_discount'];
        
        if ($is_vip) {
            // VIP users get both regular and VIP discounts applied to current price (sale or regular)
            return $current_price * (1 - (($regular_discount + $vip_discount) / 100));
        } else {
            // Regular users get only regular package discount applied to current price (sale or regular)
            return $current_price * (1 - ($regular_discount / 100));
        }
    }

    public function get_vip_price($price, $product) {
        $global_vip_enabled = get_option('wvp_enable_vip_pricing', 'no');
        error_log('WVP: get_vip_price - Global VIP pricing enabled: ' . $global_vip_enabled);
        
        if ($global_vip_enabled !== 'yes') {
            return $price;
        }

        $is_vip = $this->is_user_vip();
        
        // Also check session flag for users who just became VIP
        $session_vip = WC()->session && WC()->session->get('wvp_vip_pricing_active');
        
        error_log('WVP: get_vip_price - User is VIP: ' . ($is_vip ? 'YES' : 'NO') . ', Session VIP: ' . ($session_vip ? 'YES' : 'NO'));
        
        if (!$is_vip && !$session_vip) {
            return $price;
        }

        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        error_log('WVP: get_vip_price - Product ' . $product->get_id() . ' VIP enabled: ' . $vip_enabled);
        
        if ($vip_enabled !== 'yes') {
            // Auto-enable VIP pricing for all products with default discount
            update_post_meta($product->get_id(), '_wvp_enable_vip_pricing', 'yes');
            $vip_enabled = 'yes';
            error_log('WVP: get_vip_price - Auto-enabled VIP pricing for product ' . $product->get_id());
        }

        // First try stored VIP price from database
        $stored_vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
        if ($stored_vip_price && is_numeric($stored_vip_price) && $stored_vip_price > 0) {
            error_log('WVP: get_vip_price - Using stored VIP price for product ' . $product->get_id() . ': ' . $stored_vip_price);
            return $stored_vip_price;
        }
        
        // Fallback to dynamic calculation (20% discount) if no stored VIP price
        $current_price = $product->get_sale_price();
        if (!$current_price) {
            $current_price = $product->get_regular_price();
        }
        
        if (is_numeric($current_price) && $current_price > 0) {
            $vip_price = round($current_price * 0.8, 2); // 20% discount on current price (sale or regular)
            error_log('WVP: get_vip_price - Calculated dynamic VIP price for product ' . $product->get_id() . ': ' . $vip_price . ' (based on current price: ' . $current_price . ')');
            return $vip_price;
        }

        return $price;
    }

    public function cart_item_price($price_html, $cart_item, $cart_item_key) {
        if (get_option('wvp_enable_vip_pricing') !== 'yes') {
            return $price_html;
        }

        $is_vip = $this->is_user_vip();
        $session_vip = WC()->session && WC()->session->get('wvp_vip_pricing_active');
        
        error_log('WVP: cart_item_price - User is VIP: ' . ($is_vip ? 'YES' : 'NO') . ', Session VIP: ' . ($session_vip ? 'YES' : 'NO'));
        
        if (!$is_vip && !$session_vip) {
            return $price_html;
        }

        $product = $cart_item['data'];
        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        
        if ($vip_enabled === 'yes') {
            // Calculate VIP price dynamically from current price
            $current_price = $product->get_sale_price();
            if (!$current_price) {
                $current_price = $product->get_regular_price();
            }
            
            if ($current_price && $current_price > 0) {
                $vip_price = round($current_price * 0.8, 2); // 20% discount on current price
                $vip_label = get_option('wvp_vip_price_label', 'VIP Cena');
                return wc_price($vip_price) . ' <small class="wvp-vip-label">(' . esc_html($vip_label) . ')</small>';
            }
        }

        return $price_html;
    }

    private function format_vip_price_html($product, $vip_price, $original_price_html) {
        $display_format = get_option('wvp_non_vip_display_format', 'both');
        $vip_label = get_option('wvp_vip_price_label', 'VIP Cena');

        if ($this->is_user_vip()) {
            return $this->format_active_vip_price($vip_price, $vip_label);
        } else {
            return $this->format_non_vip_price($product, $vip_price, $original_price_html, $display_format, $vip_label);
        }
    }

    private function format_active_vip_price($vip_price, $vip_label) {
        return '<span class="wvp-vip-price-active">' . 
               wc_price($vip_price) . 
               ' <small class="wvp-vip-label">' . esc_html($vip_label) . '</small>' .
               '</span>';
    }

    private function format_non_vip_price($product, $vip_price, $original_price_html, $display_format, $vip_label) {
        $regular_price = $product->get_regular_price();
        
        switch ($display_format) {
            case 'both':
                return $original_price_html . $this->get_vip_price_display($vip_price, $vip_label, false);
            
            case 'vip_teaser':
                if ($regular_price > $vip_price) {
                    $savings_percent = round((($regular_price - $vip_price) / $regular_price) * 100);
                    return $original_price_html . 
                           '<span class="wvp-savings-badge">' . 
                           sprintf(__('Save %d%% with VIP!', 'woocommerce-vip-paketi'), $savings_percent) . 
                           '</span>';
                }
                return $original_price_html;
            
            case 'regular_only':
            default:
                return $original_price_html;
        }
    }

    private function get_vip_price_display($vip_price, $vip_label, $is_active = true) {
        $class = $is_active ? 'wvp-vip-price-active' : 'wvp-vip-price-teaser';
        
        return '<br><span class="' . $class . '">' . 
               wc_price($vip_price) . 
               ' <small class="wvp-vip-label">' . esc_html($vip_label) . '</small>' .
               '</span>';
    }

    public function calculate_package_price($package_id, $selected_products, $package_size, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $packages_admin = new WVP_Admin_Packages();
        $package_data = $packages_admin->get_package_data($package_id);
        
        if (!$package_data) {
            return new WP_Error('invalid_package', __('Invalid package', 'woocommerce-vip-paketi'));
        }

        if (count($selected_products) !== $package_size) {
            return new WP_Error('invalid_selection', __('Invalid product selection', 'woocommerce-vip-paketi'));
        }

        $base_price = 0;
        foreach ($selected_products as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            if ($this->is_user_vip($user_id)) {
                $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
                if ($vip_enabled === 'yes') {
                    $vip_price = get_post_meta($product_id, '_wvp_vip_price', true);
                    if ($vip_price && $vip_price > 0) {
                        $base_price += $vip_price;
                    } else {
                        $base_price += $product->get_price();
                    }
                } else {
                    $base_price += $product->get_price();
                }
            } else {
                $base_price += $product->get_price();
            }
        }

        $regular_discount = isset($package_data['regular_discounts'][$package_size]) ? 
                          $package_data['regular_discounts'][$package_size] : 0;

        $vip_discount = 0;
        if ($this->is_user_vip($user_id)) {
            $vip_discount = isset($package_data['vip_discounts'][$package_size]) ? 
                           $package_data['vip_discounts'][$package_size] : 0;
        }

        $price_after_regular_discount = $base_price * (1 - ($regular_discount / 100));
        $final_price = $price_after_regular_discount * (1 - ($vip_discount / 100));

        return array(
            'base_price' => $base_price,
            'regular_discount' => $regular_discount,
            'vip_discount' => $vip_discount,
            'price_after_regular_discount' => $price_after_regular_discount,
            'final_price' => $final_price,
            'total_discount_percent' => round((($base_price - $final_price) / $base_price) * 100, 2),
            'total_savings' => $base_price - $final_price
        );
    }

    public function get_product_price_for_calculation($product_id, $user_id = null) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return 0;
        }

        if ($this->is_user_vip($user_id)) {
            $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
            if ($vip_enabled === 'yes') {
                // Calculate VIP price dynamically from current price
                $current_price = $product->get_sale_price();
                if (!$current_price) {
                    $current_price = $product->get_regular_price();
                }
                
                if ($current_price && $current_price > 0) {
                    return round($current_price * 0.8, 2); // 20% discount on current price
                }
            }
        }

        // Return current effective price (sale price if available, otherwise regular price)
        $current_price = $product->get_sale_price();
        if (!$current_price) {
            $current_price = $product->get_regular_price();
        }
        
        return $current_price ? $current_price : $product->get_price();
    }

    public function apply_cart_vip_pricing() {
        if (!$this->is_user_vip()) {
            return;
        }

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            
            $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
            if ($vip_enabled === 'yes') {
                // Calculate VIP price dynamically from current price
                $current_price = $product->get_sale_price();
                if (!$current_price) {
                    $current_price = $product->get_regular_price();
                }
                
                if ($current_price && $current_price > 0) {
                    $vip_price = round($current_price * 0.8, 2); // 20% discount on current price
                    $product->set_price($vip_price);
                }
            }
        }
    }

    public function save_vip_pricing_to_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_was_vip = $this->is_user_vip($order->get_user_id());
        update_post_meta($order_id, '_wvp_user_was_vip', $user_was_vip ? 'yes' : 'no');

        $vip_savings = 0;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
            
            if ($vip_enabled === 'yes' && $user_was_vip) {
                $regular_price = get_post_meta($product_id, '_regular_price', true);
                $vip_price = get_post_meta($product_id, '_wvp_vip_price', true);
                
                if ($regular_price && $vip_price && $regular_price > $vip_price) {
                    $savings_per_item = ($regular_price - $vip_price) * $item->get_quantity();
                    $vip_savings += $savings_per_item;
                    
                    $item->add_meta_data('_wvp_vip_price_used', 'yes');
                    $item->add_meta_data('_wvp_regular_price', $regular_price);
                    $item->add_meta_data('_wvp_vip_price', $vip_price);
                    $item->add_meta_data('_wvp_savings', $savings_per_item);
                }
            }
        }

        if ($vip_savings > 0) {
            update_post_meta($order_id, '_wvp_total_vip_savings', $vip_savings);
        }
    }

    public function display_vip_savings_in_order($order) {
        $vip_savings = get_post_meta($order->get_id(), '_wvp_total_vip_savings', true);
        
        if ($vip_savings && $vip_savings > 0) {
            echo '<tr class="wvp-vip-savings">';
            echo '<th scope="row">' . __('VIP Savings:', 'woocommerce-vip-paketi') . '</th>';
            echo '<td><span class="wvp-savings-amount">-' . wc_price($vip_savings) . '</span></td>';
            echo '</tr>';
        }
    }

    private function is_user_vip($user_id = null) {
        $core = new WVP_Core();
        return $core->is_user_vip($user_id);
    }

    public function get_vip_price_breakdown($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
        if ($vip_enabled !== 'yes') {
            return false;
        }

        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $vip_price = get_post_meta($product_id, '_wvp_vip_price', true);

        $current_price = $sale_price ? $sale_price : $regular_price;
        
        // If VIP price doesn't exist, create it based on current price
        if (!$vip_price && $current_price > 0) {
            $vip_price = round($current_price * 0.8, 2); // 20% discount on current price
            // Don't update meta here - this is just for calculation
        }
        
        if (!$vip_price) {
            return false;
        }
        
        $vip_savings = $current_price - $vip_price;
        $vip_savings_percent = $current_price > 0 ? round(($vip_savings / $current_price) * 100, 2) : 0;

        return array(
            'regular_price' => $regular_price,
            'sale_price' => $sale_price,
            'current_price' => $current_price,
            'vip_price' => $vip_price,
            'vip_savings' => $vip_savings,
            'vip_savings_percent' => $vip_savings_percent,
            'has_vip_savings' => $vip_savings > 0
        );
    }
}