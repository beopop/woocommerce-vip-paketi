<?php

/**
 * WVP Fixed Price functionality for vip-paket category products
 * Products in vip-paket category have a fixed price of 5,600 RSD per cart item regardless of quantity
 */

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Fixed_Price {

    /**
     * Fixed price for vip-paket category products (in RSD)
     * Change this value to modify the fixed price
     */
    const FIXED_PRICE = 5600;

    /**
     * Category slug for products with fixed pricing
     */
    const VIP_PACKAGE_CATEGORY = 'vip-paket';

    public function __construct() {
        // Hook into WooCommerce cart and order processes
        // Use priority 5 to run before VIP pricing (priority 20) and package pricing (priority 30)
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_fixed_pricing'), 5);
        // Also add a late hook to ensure our prices stick
        add_action('woocommerce_before_calculate_totals', array($this, 'enforce_fixed_pricing'), 35);
        add_filter('woocommerce_cart_item_price', array($this, 'display_cart_item_price'), 25, 3);
        add_filter('woocommerce_cart_item_subtotal', array($this, 'display_cart_item_subtotal'), 25, 3);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_meta'), 20, 4);
        
    }

    /**
     * Check if product is in vip-paket category
     */
    public function is_vip_package_product($product_id) {
        // For testing: Product ID 181 is treated as vip-paket product
        if ($product_id == 181) {
            return true;
        }
        
        return has_term(self::VIP_PACKAGE_CATEGORY, 'product_cat', $product_id);
    }

    /**
     * Apply fixed pricing to vip-paket category products in cart
     * Uses the same calculation logic as package page
     */
    public function apply_fixed_pricing($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Prevent infinite loops - allow a few more iterations since other plugins may trigger calculations
        if (did_action('woocommerce_before_calculate_totals') >= 5) {
            return;
        }

        $cart_items = $cart->get_cart();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $quantity = $cart_item['quantity'];

            // Skip if not a vip-package product
            if (!$this->is_vip_package_product($product_id)) {
                continue;
            }

            // Skip if already processed
            if (isset($cart_item['wvp_fixed_price_applied'])) {
                continue;
            }

            // Get WooCommerce product to access real prices (same as package page)
            $wc_product = wc_get_product($product_id);
            if (!$wc_product) continue;
            
            // Get actual WooCommerce prices (same as package page logic)
            $regular_price = floatval($wc_product->get_regular_price());
            $sale_price = floatval($wc_product->get_sale_price());
            $current_price = $sale_price ? $sale_price : $regular_price;
            
            // Get VIP price from product meta (same as package page)
            $vip_price = floatval(get_post_meta($product_id, '_wvp_vip_price', true));
            $vip_price = $vip_price ? $vip_price : $current_price;
            
            // Check if user is VIP (same as package page)
            $is_vip = $this->is_user_vip();
            
            // Calculate display price (same as package page logic)
            $display_price = $is_vip ? $vip_price : $current_price;
            $subtotal = $display_price * $quantity;
            
            // Apply the same discount logic as package page
            // For fixed price items, we reverse-engineer the discounts needed to get 5600 final price
            $target_price = self::FIXED_PRICE;
            
            // Calculate required discount to reach target price
            $total_discount_needed = $subtotal - $target_price;
            $discount_percentage = $subtotal > 0 ? ($total_discount_needed / $subtotal) * 100 : 0;
            
            // Set price per unit to achieve the target total
            $price_per_unit = $target_price / $quantity;
            $product->set_price($price_per_unit);
            
            // Store the same data structure as package page for consistency
            $cart->cart_contents[$cart_item_key]['wvp_fixed_price_applied'] = true;
            $cart->cart_contents[$cart_item_key]['wvp_is_package'] = true; // Mark as package for other systems
            $cart->cart_contents[$cart_item_key]['wvp_original_price'] = $regular_price;
            $cart->cart_contents[$cart_item_key]['wvp_original_quantity'] = $quantity;
            $cart->cart_contents[$cart_item_key]['wvp_fixed_total'] = self::FIXED_PRICE;
            $cart->cart_contents[$cart_item_key]['wvp_price_per_unit'] = $price_per_unit;
            $cart->cart_contents[$cart_item_key]['wvp_display_price'] = $display_price;
            $cart->cart_contents[$cart_item_key]['wvp_subtotal'] = $subtotal;
            $cart->cart_contents[$cart_item_key]['wvp_current_price'] = $current_price;
            $cart->cart_contents[$cart_item_key]['wvp_vip_price'] = $vip_price;
            $cart->cart_contents[$cart_item_key]['wvp_discount_percentage'] = $discount_percentage;

        }
    }
    
    /**
     * Enforce fixed pricing after other plugins have run (priority 35)
     */
    public function enforce_fixed_pricing($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Allow more iterations for enforcement
        if (did_action('woocommerce_before_calculate_totals') >= 10) {
            return;
        }

        $cart_items = $cart->get_cart();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            // Only process items that were marked as fixed price
            if (!isset($cart_item['wvp_fixed_price_applied']) || !isset($cart_item['wvp_price_per_unit'])) {
                continue;
            }

            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $stored_price = $cart_item['wvp_price_per_unit'];
            $current_price = $product->get_price();
            
            // If price was changed by another plugin, restore it
            if (abs($current_price - $stored_price) > 0.01) {
                $product->set_price($stored_price);
            }
        }
    }
    
    /**
     * Check if user is VIP (same logic as package page)
     */
    private function is_user_vip() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        return in_array('wvp_vip_member', $user->roles) || current_user_can('manage_options');
    }

    /**
     * Display custom price in cart
     */
    public function display_cart_item_price($price_html, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        if (!$this->is_vip_package_product($product_id)) {
            return $price_html;
        }

        // For fixed price packages, show the fixed price
        return wc_price(self::FIXED_PRICE);
    }

    /**
     * Display custom subtotal in cart with savings (uses same logic as package page)
     */
    public function display_cart_item_subtotal($subtotal_html, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        if (!$this->is_vip_package_product($product_id)) {
            return $subtotal_html;
        }

        // Use stored calculation data (same as package page)
        $original_quantity = isset($cart_item['wvp_original_quantity']) ? $cart_item['wvp_original_quantity'] : $cart_item['quantity'];
        $display_price = isset($cart_item['wvp_display_price']) ? $cart_item['wvp_display_price'] : $product->get_price();
        $subtotal_before_discount = isset($cart_item['wvp_subtotal']) ? $cart_item['wvp_subtotal'] : ($display_price * $original_quantity);
        $fixed_total = self::FIXED_PRICE;
        $savings = $subtotal_before_discount - $fixed_total;

        // Build the display HTML (same format as package page)
        $html = '';
        
        if ($savings > 0) {
            // Show crossed-out original subtotal and savings (like package page)
            $html .= '<del>' . wc_price($subtotal_before_discount) . '</del> ';
            $html .= wc_price($fixed_total);
            $html .= '<br><small class="wvp-savings" style="color: #28a745;">';
            $html .= sprintf(__('Ušteda: %s', 'woocommerce-vip-paketi'), wc_price($savings));
            $html .= '</small>';
        } else {
            $html = wc_price($fixed_total);
        }

        return $html;
    }

    /**
     * Save order item meta data (consistent with package page data)
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order) {
        $product_id = $values['product_id'];

        if (!$this->is_vip_package_product($product_id)) {
            return;
        }

        // Add meta note about fixed pricing (consistent with package system)
        $note = sprintf(__('VIP paket – fiksna cena %s', 'woocommerce-vip-paketi'), wc_price(self::FIXED_PRICE));
        $item->add_meta_data('_wvp_fixed_price_note', $note, true);
        $item->add_meta_data('_wvp_fixed_price_amount', self::FIXED_PRICE, true);
        $item->add_meta_data('_wvp_is_package', true, true); // Mark as package for consistency
        
        // Store all calculated data for consistency with package system
        if (isset($values['wvp_display_price'])) {
            $item->add_meta_data('_wvp_display_price', $values['wvp_display_price'], true);
        }
        if (isset($values['wvp_subtotal'])) {
            $item->add_meta_data('_wvp_subtotal_before_discount', $values['wvp_subtotal'], true);
        }
        if (isset($values['wvp_discount_percentage'])) {
            $item->add_meta_data('_wvp_discount_percentage', $values['wvp_discount_percentage'], true);
        }
        
        // Calculate and store savings using the same logic as package page
        if (isset($values['wvp_subtotal']) && isset($values['wvp_original_quantity'])) {
            $subtotal_before_discount = $values['wvp_subtotal'];
            $savings = $subtotal_before_discount - self::FIXED_PRICE;
            if ($savings > 0) {
                $item->add_meta_data('_wvp_original_quantity', $values['wvp_original_quantity'], true);
                $item->add_meta_data('_wvp_savings_amount', $savings, true);
            }
        }
    }

    /**
     * Get the fixed price constant (for external access)
     */
    public static function get_fixed_price() {
        return self::FIXED_PRICE;
    }

    /**
     * Get the VIP package category slug (for external access)
     */
    public static function get_vip_package_category() {
        return self::VIP_PACKAGE_CATEGORY;
    }
}

// Initialize the class
new WVP_Fixed_Price();