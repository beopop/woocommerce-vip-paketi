<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Woodmart {

    public function __construct() {
        add_action('init', array($this, 'init_woodmart_integration'));
    }

    public function init_woodmart_integration() {
        if (!class_exists('Woodmart_Theme')) {
            return;
        }

        if (get_option('wvp_woodmart_integration') !== 'yes') {
            return;
        }

        add_filter('woodmart_product_price', array($this, 'woodmart_price'), 10, 2);
        add_filter('woodmart_quick_view_price', array($this, 'woodmart_quick_view_price'), 10, 2);
        add_action('woodmart_after_product_title', array($this, 'add_vip_badge'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_woodmart_styles'));
        
        add_filter('woodmart_ajax_add_to_cart_fragments', array($this, 'update_cart_fragments'));
        add_action('wp_ajax_woodmart_ajax_search', array($this, 'modify_ajax_search_results'), 5);
        add_action('wp_ajax_nopriv_woodmart_ajax_search', array($this, 'modify_ajax_search_results'), 5);
    }

    public function woodmart_price($price_html, $product) {
        if (!$product) {
            return $price_html;
        }

        $pricing = new WVP_Pricing();
        return $pricing->modify_price_html($price_html, $product);
    }

    public function woodmart_quick_view_price($price_html, $product) {
        if (!$product) {
            return $price_html;
        }

        $pricing = new WVP_Pricing();
        $modified_price = $pricing->modify_price_html($price_html, $product);

        if ($this->is_user_vip()) {
            $modified_price .= '<span class="wvp-quickview-vip-badge">' . __('VIP', 'woocommerce-vip-paketi') . '</span>';
        }

        return $modified_price;
    }

    public function add_vip_badge() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }

        global $product;
        
        if (!$product) {
            return;
        }

        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        if ($vip_enabled === 'yes') {
            $vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
            $regular_price = $product->get_regular_price();
            
            if ($vip_price && $regular_price && $vip_price < $regular_price) {
                $savings_percent = round((($regular_price - $vip_price) / $regular_price) * 100);
                
                if ($this->is_user_vip()) {
                    echo '<span class="wvp-product-badge wvp-vip-active">' . __('VIP Active', 'woocommerce-vip-paketi') . '</span>';
                } else {
                    echo '<span class="wvp-product-badge wvp-vip-available">' . sprintf(__('VIP -%d%%', 'woocommerce-vip-paketi'), $savings_percent) . '</span>';
                }
            }
        }
    }

    public function enqueue_woodmart_styles() {
        if (!class_exists('Woodmart_Theme')) {
            return;
        }

        wp_enqueue_style('wvp-woodmart-integration', WVP_PLUGIN_URL . 'assets/css/wvp-woodmart.css', array(), WVP_VERSION);
        
        $custom_css = $this->get_woodmart_custom_css();
        if ($custom_css) {
            wp_add_inline_style('wvp-woodmart-integration', $custom_css);
        }
    }

    private function get_woodmart_custom_css() {
        $css = '';

        $vip_color = get_option('wvp_woodmart_vip_color', '#gold');
        $badge_position = get_option('wvp_woodmart_badge_position', 'top-right');

        $css .= "
        .wvp-product-badge {
            background: {$vip_color};
            color: #fff;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            z-index: 10;
        }
        ";

        switch ($badge_position) {
            case 'top-left':
                $css .= ".wvp-product-badge { top: 10px; left: 10px; }";
                break;
            case 'top-right':
                $css .= ".wvp-product-badge { top: 10px; right: 10px; }";
                break;
            case 'bottom-left':
                $css .= ".wvp-product-badge { bottom: 10px; left: 10px; }";
                break;
            case 'bottom-right':
                $css .= ".wvp-product-badge { bottom: 10px; right: 10px; }";
                break;
        }

        $css .= "
        .wvp-vip-price-active {
            color: {$vip_color};
            font-weight: bold;
        }
        
        .wvp-vip-price-teaser {
            color: #999;
            text-decoration: line-through;
        }
        
        .wvp-savings-badge {
            background: {$vip_color};
            color: #fff;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .wvp-quickview-vip-badge {
            background: {$vip_color};
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 8px;
            vertical-align: top;
        }
        ";

        return $css;
    }

    public function update_cart_fragments($fragments) {
        if (!$this->is_user_vip()) {
            return $fragments;
        }

        ob_start();
        $this->display_vip_cart_notice();
        $vip_notice = ob_get_clean();

        if ($vip_notice) {
            $fragments['.wvp-cart-vip-notice'] = $vip_notice;
        }

        return $fragments;
    }

    private function display_vip_cart_notice() {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $vip_items = 0;
        $total_savings = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
            
            if ($vip_enabled === 'yes') {
                $regular_price = get_post_meta($product->get_id(), '_regular_price', true);
                $vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
                
                if ($regular_price && $vip_price && $regular_price > $vip_price) {
                    $vip_items++;
                    $savings = ($regular_price - $vip_price) * $cart_item['quantity'];
                    $total_savings += $savings;
                }
            }
        }

        if ($vip_items > 0) {
            echo '<div class="wvp-cart-vip-notice">';
            echo '<span class="wvp-vip-badge">' . __('VIP', 'woocommerce-vip-paketi') . '</span> ';
            echo sprintf(
                __('You are saving %s on %d VIP items!', 'woocommerce-vip-paketi'),
                wc_price($total_savings),
                $vip_items
            );
            echo '</div>';
        }
    }

    public function modify_ajax_search_results() {
        if (!isset($_REQUEST['query'])) {
            return;
        }

        add_filter('posts_search', array($this, 'enhance_search_with_vip_info'), 10, 2);
    }

    public function enhance_search_with_vip_info($search, $query) {
        if (!$query->is_main_query() || !is_admin() || !$query->is_search()) {
            return $search;
        }

        if ($this->is_user_vip()) {
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => '_wvp_enable_vip_pricing',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'key' => '_wvp_enable_vip_pricing',
                    'compare' => 'NOT EXISTS'
                )
            ));
        }

        return $search;
    }

    public function add_woodmart_checkout_compatibility() {
        if (!is_checkout()) {
            return;
        }

        add_action('woodmart_before_checkout_form', array($this, 'add_vip_checkout_notice'));
        add_filter('woodmart_checkout_fields', array($this, 'modify_checkout_fields'));
    }

    public function add_vip_checkout_notice() {
        if ($this->is_user_vip()) {
            echo '<div class="wvp-woodmart-checkout-notice">';
            echo '<div class="woocommerce-message">';
            echo '<strong>' . __('VIP Member Benefits Active', 'woocommerce-vip-paketi') . '</strong><br>';
            echo __('You are enjoying special VIP pricing on eligible items in your cart.', 'woocommerce-vip-paketi');
            echo '</div>';
            echo '</div>';
        }
    }

    public function modify_checkout_fields($fields) {
        if ($this->is_user_vip()) {
            if (isset($fields['billing']['billing_phone'])) {
                $fields['billing']['billing_phone']['priority'] = 25;
                $fields['billing']['billing_phone']['placeholder'] = __('VIP Priority Support Line', 'woocommerce-vip-paketi');
            }
        }

        return $fields;
    }

    public function add_single_product_woodmart_hooks() {
        if (!is_product()) {
            return;
        }

        add_action('woodmart_single_product_after_price', array($this, 'add_vip_single_product_info'), 5);
        add_action('woodmart_before_add_to_cart_btn', array($this, 'add_vip_add_to_cart_notice'), 5);
    }

    public function add_vip_single_product_info() {
        global $product;
        
        if (!$product) {
            return;
        }

        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        if ($vip_enabled !== 'yes') {
            return;
        }

        $pricing = new WVP_Pricing();
        $price_breakdown = $pricing->get_vip_price_breakdown($product->get_id());
        
        if (!$price_breakdown || !$price_breakdown['has_vip_savings']) {
            return;
        }

        echo '<div class="wvp-woodmart-single-vip-info">';
        
        if ($this->is_user_vip()) {
            echo '<div class="wvp-active-savings">';
            echo '<span class="wvp-savings-icon">💰</span>';
            echo sprintf(
                __('You are saving %s (%s%%) with VIP pricing!', 'woocommerce-vip-paketi'),
                wc_price($price_breakdown['vip_savings']),
                $price_breakdown['vip_savings_percent']
            );
            echo '</div>';
        } else {
            echo '<div class="wvp-potential-savings">';
            echo '<span class="wvp-vip-icon">⭐</span>';
            echo sprintf(
                __('VIP members save %s (%s%%) on this product', 'woocommerce-vip-paketi'),
                wc_price($price_breakdown['vip_savings']),
                $price_breakdown['vip_savings_percent']
            );
            echo '</div>';
        }
        
        echo '</div>';
    }

    public function add_vip_add_to_cart_notice() {
        if (!$this->is_user_vip()) {
            return;
        }

        global $product;
        
        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        if ($vip_enabled === 'yes') {
            echo '<div class="wvp-vip-add-to-cart-notice">';
            echo '<small><span class="wvp-vip-badge">VIP</span> ' . __('VIP pricing will be applied', 'woocommerce-vip-paketi') . '</small>';
            echo '</div>';
        }
    }

    private function is_user_vip() {
        $core = new WVP_Core();
        return $core->is_user_vip();
    }

    public function get_woodmart_compatibility_status() {
        return array(
            'theme_active' => class_exists('Woodmart_Theme'),
            'integration_enabled' => get_option('wvp_woodmart_integration') === 'yes',
            'version_compatible' => $this->check_woodmart_version_compatibility(),
            'hooks_loaded' => did_action('woodmart_theme_loaded') > 0
        );
    }

    private function check_woodmart_version_compatibility() {
        if (!class_exists('Woodmart_Theme')) {
            return false;
        }

        $theme = wp_get_theme();
        $version = $theme->get('Version');
        
        return version_compare($version, '6.0', '>=');
    }

    public function add_woodmart_admin_settings() {
        add_settings_section(
            'wvp_woodmart_section',
            __('Woodmart Integration', 'woocommerce-vip-paketi'),
            array($this, 'woodmart_section_callback'),
            'wvp_settings_woodmart'
        );

        add_settings_field(
            'wvp_woodmart_vip_color',
            __('VIP Color', 'woocommerce-vip-paketi'),
            array($this, 'color_field_callback'),
            'wvp_settings_woodmart',
            'wvp_woodmart_section',
            array('field' => 'wvp_woodmart_vip_color', 'default' => '#gold')
        );

        add_settings_field(
            'wvp_woodmart_badge_position',
            __('Badge Position', 'woocommerce-vip-paketi'),
            array($this, 'select_field_callback'),
            'wvp_settings_woodmart',
            'wvp_woodmart_section',
            array(
                'field' => 'wvp_woodmart_badge_position',
                'options' => array(
                    'top-left' => __('Top Left', 'woocommerce-vip-paketi'),
                    'top-right' => __('Top Right', 'woocommerce-vip-paketi'),
                    'bottom-left' => __('Bottom Left', 'woocommerce-vip-paketi'),
                    'bottom-right' => __('Bottom Right', 'woocommerce-vip-paketi')
                )
            )
        );
    }

    public function woodmart_section_callback() {
        echo '<p>' . __('Configure Woodmart theme specific integration settings.', 'woocommerce-vip-paketi') . '</p>';
        
        $status = $this->get_woodmart_compatibility_status();
        
        if (!$status['theme_active']) {
            echo '<div class="notice notice-warning"><p>' . __('Woodmart theme is not active. These settings will have no effect.', 'woocommerce-vip-paketi') . '</p></div>';
        } elseif (!$status['version_compatible']) {
            echo '<div class="notice notice-warning"><p>' . __('Your Woodmart version may not be fully compatible. Please update to the latest version.', 'woocommerce-vip-paketi') . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . __('Woodmart integration is ready and compatible.', 'woocommerce-vip-paketi') . '</p></div>';
        }
    }
}