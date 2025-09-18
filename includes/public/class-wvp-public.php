<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, WVP_PLUGIN_URL . 'assets/css/wvp-public.css', array(), $this->version, 'all');

        if (class_exists('Woodmart_Theme')) {
            wp_enqueue_style($this->plugin_name . '-woodmart', WVP_PLUGIN_URL . 'assets/css/wvp-woodmart.css', array($this->plugin_name), $this->version, 'all');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, WVP_PLUGIN_URL . 'assets/js/wvp-public.js', array('jquery'), $this->version, false);

        wp_localize_script($this->plugin_name, 'wvp_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wvp_public_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'woocommerce-vip-paketi'),
                'error' => __('An error occurred. Please try again.', 'woocommerce-vip-paketi'),
                'success' => __('Success!', 'woocommerce-vip-paketi'),
                'vip_code_placeholder' => __('Enter VIP code', 'woocommerce-vip-paketi'),
                'verify_code' => __('Verify Code', 'woocommerce-vip-paketi'),
                'code_verified' => __('VIP code verified successfully!', 'woocommerce-vip-paketi'),
                'invalid_code' => __('Invalid or expired VIP code', 'woocommerce-vip-paketi'),
                'select_package_size' => __('Please select a package size', 'woocommerce-vip-paketi'),
                'select_products' => __('Please select products for your package', 'woocommerce-vip-paketi'),
                'max_products_reached' => __('Maximum number of products selected', 'woocommerce-vip-paketi'),
                'min_products_required' => __('Minimum {min} products required', 'woocommerce-vip-paketi')
            ),
            'user_is_vip' => $this->is_current_user_vip(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'currency_position' => get_option('woocommerce_currency_pos')
        ));

        if (is_checkout()) {
            wp_enqueue_script($this->plugin_name . '-checkout', WVP_PLUGIN_URL . 'assets/js/wvp-checkout.js', array('jquery', $this->plugin_name), $this->version, false);
            wp_enqueue_script($this->plugin_name . '-test-popup', WVP_PLUGIN_URL . 'assets/js/wvp-test-popup.js', array('jquery'), $this->version, false);
            
            // Localize script specifically for checkout
            wp_localize_script($this->plugin_name . '-checkout', 'wvp_public_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wvp_public_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'woocommerce-vip-paketi'),
                    'error' => __('An error occurred. Please try again.', 'woocommerce-vip-paketi'),
                    'success' => __('Success!', 'woocommerce-vip-paketi'),
                    'vip_code_placeholder' => __('Enter VIP code', 'woocommerce-vip-paketi'),
                    'verify_code' => __('Verify Code', 'woocommerce-vip-paketi'),
                    'code_verified' => __('VIP code verified successfully!', 'woocommerce-vip-paketi'),
                    'invalid_code' => __('Invalid or expired VIP code', 'woocommerce-vip-paketi')
                ),
                'user_is_vip' => $this->is_current_user_vip()
            ));
        }

        if (is_singular('wvp_package')) {
            wp_enqueue_script($this->plugin_name . '-package', WVP_PLUGIN_URL . 'assets/js/wvp-package.js', array('jquery', $this->plugin_name), $this->version, false);
        }
    }

    private function is_current_user_vip() {
        $core = new WVP_Core();
        return $core->is_user_vip();
    }

    public function add_vip_body_class($classes) {
        if ($this->is_current_user_vip()) {
            $classes[] = 'wvp-user-vip';
        } else {
            $classes[] = 'wvp-user-regular';
        }

        return $classes;
    }

    public function add_product_vip_class($classes, $product_id = null) {
        if (!$product_id) {
            global $product;
            if ($product) {
                $product_id = $product->get_id();
            }
        }

        if ($product_id) {
            $vip_enabled = get_post_meta($product_id, '_wvp_enable_vip_pricing', true);
            if ($vip_enabled === 'yes') {
                $classes[] = 'wvp-vip-pricing-enabled';
            }
        }

        return $classes;
    }

    public function maybe_redirect_non_vip_users() {
        if (is_singular('wvp_package')) {
            $package_id = get_the_ID();
            $packages_admin = new WVP_Admin_Packages();
            
            if (!$packages_admin->is_package_available_for_user($package_id)) {
                if (!is_user_logged_in()) {
                    $redirect_url = wp_login_url(get_permalink());
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }

    public function add_vip_status_notice() {
        if (is_user_logged_in() && $this->is_current_user_vip()) {
            echo '<div class="wvp-vip-status-notice">';
            echo '<span class="wvp-vip-badge">' . __('VIP Member', 'woocommerce-vip-paketi') . '</span>';
            echo '<span class="wvp-vip-message">' . __('You are enjoying VIP pricing!', 'woocommerce-vip-paketi') . '</span>';
            echo '</div>';
        }
    }

    public function customize_shop_display() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }

        $display_format = get_option('wvp_non_vip_display_format', 'both');
        
        if ($display_format === 'vip_teaser' && !$this->is_current_user_vip()) {
            add_action('woocommerce_after_shop_loop_item_title', array($this, 'add_vip_teaser_message'), 15);
        }
    }

    public function add_vip_teaser_message() {
        if (!$this->is_current_user_vip()) {
            global $product;
            
            $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
            if ($vip_enabled === 'yes') {
                $vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
                if ($vip_price && $vip_price < $product->get_price()) {
                    $savings = $product->get_price() - $vip_price;
                    $savings_percent = round(($savings / $product->get_price()) * 100);
                    
                    echo '<div class="wvp-vip-teaser">';
                    echo '<span class="wvp-teaser-text">' . sprintf(__('Save %d%% with VIP membership!', 'woocommerce-vip-paketi'), $savings_percent) . '</span>';
                    echo '</div>';
                }
            }
        }
    }

    public function add_single_product_vip_info() {
        if (!is_product()) {
            return;
        }

        global $product;
        $vip_enabled = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true);
        
        if ($vip_enabled !== 'yes') {
            return;
        }

        if ($this->is_current_user_vip()) {
            echo '<div class="wvp-single-vip-notice vip-active">';
            echo '<span class="wvp-vip-badge">' . __('VIP Price Active', 'woocommerce-vip-paketi') . '</span>';
            echo '</div>';
        } else {
            $vip_price = get_post_meta($product->get_id(), '_wvp_vip_price', true);
            if ($vip_price && $vip_price < $product->get_price()) {
                $savings = $product->get_price() - $vip_price;
                $savings_percent = round(($savings / $product->get_price()) * 100);
                
                echo '<div class="wvp-single-vip-notice vip-teaser">';
                echo '<h4>' . __('VIP Member Benefits', 'woocommerce-vip-paketi') . '</h4>';
                echo '<p>' . sprintf(__('VIP members save %d%% on this product!', 'woocommerce-vip-paketi'), $savings_percent) . '</p>';
                echo '<p class="wvp-vip-price-preview">' . sprintf(__('VIP Price: %s', 'woocommerce-vip-paketi'), wc_price($vip_price)) . '</p>';
                
                if (!is_user_logged_in()) {
                    echo '<a href="' . wp_login_url(get_permalink()) . '" class="button wvp-login-button">' . __('Login for VIP Access', 'woocommerce-vip-paketi') . '</a>';
                } else {
                    echo '<p class="wvp-upgrade-message">' . __('Upgrade to VIP membership to unlock special pricing!', 'woocommerce-vip-paketi') . '</p>';
                }
                echo '</div>';
            }
        }
    }

    public function format_price_html($price_html, $product) {
        if (get_option('wvp_enable_vip_pricing') !== 'yes') {
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

        $display_format = get_option('wvp_non_vip_display_format', 'both');
        $vip_label = get_option('wvp_vip_price_label', 'VIP Cena');

        if ($this->is_current_user_vip()) {
            return '<span class="wvp-vip-price-active">' . wc_price($vip_price) . ' <small class="wvp-vip-label">' . esc_html($vip_label) . '</small></span>';
        } else {
            switch ($display_format) {
                case 'both':
                    return $price_html . '<br><span class="wvp-vip-price-teaser">' . wc_price($vip_price) . ' <small class="wvp-vip-label">' . esc_html($vip_label) . '</small></span>';
                
                case 'vip_teaser':
                    return $price_html . '<span class="wvp-savings-badge">' . __('VIP Savings Available!', 'woocommerce-vip-paketi') . '</span>';
                
                case 'regular_only':
                default:
                    return $price_html;
            }
        }
    }


    public function load_package_template($template) {
        if (is_singular('wvp_package')) {
            // Check theme first, then plugin
            $theme_template = locate_template(array('single-wvp_package.php'));
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = WVP_PLUGIN_DIR . 'includes/public/single-wvp_package.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function package_template_redirect() {
        if (is_singular('wvp_package')) {
            global $wp_query;
            
            // Force WordPress to recognize this as a valid page
            $wp_query->is_404 = false;
            status_header(200);
        }
    }


}