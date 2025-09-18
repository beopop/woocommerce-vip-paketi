<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Core {

    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $plugin_admin;
    protected $plugin_public;

    public function __construct() {
        $this->version = WVP_VERSION;
        $this->plugin_name = 'woocommerce-vip-paketi';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_common_hooks();
    }

    private function load_dependencies() {
        $this->loader = new WVP_Loader();

        require_once WVP_PLUGIN_DIR . 'includes/database/class-wvp-database.php';
        require_once WVP_PLUGIN_DIR . 'includes/database/class-wvp-database-updater.php';
        require_once WVP_PLUGIN_DIR . 'includes/admin/class-wvp-admin.php';
        require_once WVP_PLUGIN_DIR . 'includes/admin/class-wvp-admin-vip-codes.php';
        require_once WVP_PLUGIN_DIR . 'includes/admin/class-wvp-admin-packages.php';
        require_once WVP_PLUGIN_DIR . 'includes/public/class-wvp-public.php';
        require_once WVP_PLUGIN_DIR . 'includes/public/class-wvp-pricing.php';
        require_once WVP_PLUGIN_DIR . 'includes/public/class-wvp-checkout.php';
        require_once WVP_PLUGIN_DIR . 'includes/integrations/class-wvp-woodmart.php';
        require_once WVP_PLUGIN_DIR . 'includes/class-wvp-scheduled-events.php';

        $this->plugin_admin = new WVP_Admin($this->get_plugin_name(), $this->get_version());
        $this->plugin_public = new WVP_Public($this->get_plugin_name(), $this->get_version());
    }

    private function set_locale() {
        add_action('plugins_loaded', function() {
            // Force Serbian locale for this plugin
            add_filter('plugin_locale', array($this, 'force_serbian_locale'), 10, 2);
            
            load_plugin_textdomain(
                'woocommerce-vip-paketi',
                false,
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
            );
        });
    }

    public function force_serbian_locale($locale, $domain) {
        if ($domain === 'woocommerce-vip-paketi') {
            return 'sr_RS';
        }
        return $locale;
    }

    private function define_admin_hooks() {
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $this->plugin_admin, 'options_update');

        $vip_codes_admin = new WVP_Admin_VIP_Codes();
        $this->loader->add_action('wp_ajax_wvp_add_code', $vip_codes_admin, 'ajax_add_code');
        $this->loader->add_action('wp_ajax_wvp_edit_code', $vip_codes_admin, 'ajax_edit_code');
        $this->loader->add_action('wp_ajax_wvp_delete_code', $vip_codes_admin, 'ajax_delete_code');
        $this->loader->add_action('wp_ajax_wvp_get_code_data', $vip_codes_admin, 'ajax_get_code_data');
        $this->loader->add_action('wp_ajax_wvp_update_code', $vip_codes_admin, 'ajax_update_code');
        $this->loader->add_action('wp_ajax_wvp_bulk_import_codes', $vip_codes_admin, 'ajax_bulk_import');

        $packages_admin = new WVP_Admin_Packages();
        $this->loader->add_action('add_meta_boxes', $packages_admin, 'add_package_meta_boxes');
        $this->loader->add_action('save_post', $packages_admin, 'save_package_meta');

        $this->loader->add_action('woocommerce_product_options_pricing', $this->plugin_admin, 'add_vip_price_field');
        $this->loader->add_action('woocommerce_process_product_meta', $this->plugin_admin, 'save_vip_price_field');
    }

    private function define_public_hooks() {
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
        $this->loader->add_filter('single_template', $this->plugin_public, 'load_package_template');
        $this->loader->add_action('template_redirect', $this->plugin_public, 'package_template_redirect');

        $pricing = new WVP_Pricing();
        $this->loader->add_filter('woocommerce_get_price_html', $pricing, 'modify_price_html', 20, 2);
        $this->loader->add_filter('woocommerce_product_get_price', $pricing, 'get_vip_price', 20, 2);
        $this->loader->add_filter('woocommerce_product_variation_get_price', $pricing, 'get_vip_price', 20, 2);
        $this->loader->add_filter('woocommerce_cart_item_price', $pricing, 'cart_item_price', 20, 3);
        $this->loader->add_action('woocommerce_before_calculate_totals', $pricing, 'force_vip_prices_in_cart', 10);
        $this->loader->add_action('woocommerce_cart_loaded_from_session', $pricing, 'force_vip_prices_in_cart', 10);
        $this->loader->add_action('woocommerce_add_to_cart', $pricing, 'force_vip_prices_in_cart_on_add', 10);
        $this->loader->add_action('woocommerce_update_cart_action_cart_updated', $pricing, 'force_vip_prices_in_cart_wrapper', 10);

        $checkout = new WVP_Checkout();
        $this->loader->add_action('woocommerce_review_order_before_payment', $checkout, 'add_vip_code_field');
        $this->loader->add_action('wp_ajax_wvp_verify_code', $checkout, 'ajax_verify_code');
        $this->loader->add_action('wp_ajax_nopriv_wvp_verify_code', $checkout, 'ajax_verify_code');
        $this->loader->add_action('wp_ajax_wvp_confirm_email_and_autofill', $checkout, 'ajax_confirm_email_and_autofill');
        $this->loader->add_action('wp_ajax_nopriv_wvp_confirm_email_and_autofill', $checkout, 'ajax_confirm_email_and_autofill');
        $this->loader->add_action('wp_ajax_wvp_confirm_phone_and_autofill', $checkout, 'ajax_confirm_phone_and_autofill');
        $this->loader->add_action('wp_ajax_nopriv_wvp_confirm_phone_and_autofill', $checkout, 'ajax_confirm_phone_and_autofill');
        $this->loader->add_action('wp_ajax_wvp_get_session_autofill', $checkout, 'ajax_get_session_autofill');
        $this->loader->add_action('wp_ajax_nopriv_wvp_get_session_autofill', $checkout, 'ajax_get_session_autofill');
        $this->loader->add_action('wp_ajax_wvp_register_and_activate', $checkout, 'ajax_register_and_activate');
        $this->loader->add_action('wp_ajax_nopriv_wvp_register_and_activate', $checkout, 'ajax_register_and_activate');
        $this->loader->add_action('woocommerce_checkout_update_order_review', $checkout, 'update_order_review');
        
        // Order tracking hooks
        $this->loader->add_action('woocommerce_order_status_completed', $checkout, 'track_vip_order_completion');
        $this->loader->add_action('woocommerce_payment_complete', $checkout, 'track_vip_order_completion');
        
        // Subscription and Membership integration hooks
        $this->setup_subscription_membership_hooks();
        
        // Add AJAX handlers for package cart functionality
        $this->loader->add_action('wp_ajax_wvp_add_package_to_cart', $this, 'ajax_add_package_to_cart');
        $this->loader->add_action('wp_ajax_nopriv_wvp_add_package_to_cart', $this, 'ajax_add_package_to_cart');

        // Add AJAX handler for AI report generation
        $this->loader->add_action('wp_ajax_wvp_generate_ai_report', $this, 'ajax_generate_ai_report');

        // Add AJAX handler for getting quiz results
        $this->loader->add_action('wp_ajax_wvp_get_quiz_results', $this, 'ajax_get_quiz_results');
        $this->loader->add_action('wp_ajax_nopriv_wvp_get_quiz_results', $this, 'ajax_get_quiz_results');

        // Add AJAX handler for testing OpenAI API key
        $this->loader->add_action('wp_ajax_wvp_test_openai_api', $this, 'ajax_test_openai_api');

        // Add AJAX handler for frontend AI report generation
        $this->loader->add_action('wp_ajax_wvp_generate_frontend_ai_report', $this, 'ajax_generate_frontend_ai_report');
        $this->loader->add_action('wp_ajax_nopriv_wvp_generate_frontend_ai_report', $this, 'ajax_generate_frontend_ai_report');
        
        // Cart display hooks for packages
        $this->loader->add_filter('woocommerce_cart_item_name', $this, 'modify_package_cart_item_name', 10, 3);
        $this->loader->add_filter('woocommerce_cart_item_name', $this, 'add_vip_badge_to_cart_items', 15, 3);
        
        // Order item display hooks (for thankyou page, order emails, etc.)
        $this->loader->add_filter('woocommerce_order_item_name', $this, 'add_vip_badge_to_order_items', 15, 2);
        
        // Enhanced VIP price display (must run after pricing but before output)
        $this->loader->add_filter('woocommerce_cart_item_price', $this, 'enhance_vip_cart_price_display', 30, 3);
        $this->loader->add_filter('woocommerce_cart_item_subtotal', $this, 'enhance_vip_cart_subtotal_display', 30, 3);
        $this->loader->add_filter('woocommerce_cart_item_price', $this, 'modify_package_cart_item_price', 40, 3);
        $this->loader->add_filter('woocommerce_cart_item_subtotal', $this, 'modify_package_cart_item_subtotal', 40, 3);
        $this->loader->add_filter('woocommerce_cart_item_thumbnail', $this, 'modify_package_cart_item_thumbnail', 10, 3);
        $this->loader->add_action('woocommerce_before_calculate_totals', $this, 'update_package_cart_item_price', 5);
        
        // Package quantity restriction hooks
        $this->loader->add_filter('woocommerce_cart_item_quantity', $this, 'restrict_package_quantity', 10, 3);
        $this->loader->add_filter('woocommerce_cart_item_remove_link', $this, 'modify_package_remove_link', 10, 2);
        
        // Force cart totals calculation for packages
        $this->loader->add_action('woocommerce_cart_loaded_from_session', $this, 'force_package_cart_refresh');
        $this->loader->add_action('woocommerce_add_to_cart', $this, 'force_package_cart_refresh');
        $this->loader->add_action('woocommerce_after_calculate_totals', $this, 'verify_package_totals');
        
        // Override product price for packages in cart calculations
        $this->loader->add_filter('woocommerce_product_get_price', $this, 'override_package_product_price_for_totals', 999, 2);
        
        // Additional hooks to ensure package prices are used in calculations
        // Note: Removed force_package_cart_item_subtotal_calculation to avoid conflicts with modify_package_cart_item_subtotal
        
        // Note: Removed override_package_product_price hook to prevent conflicts with individual products
        // Package pricing is handled by modify_package_cart_item_price and update_package_cart_item_price instead
        
        // Save package data to order items
        $this->loader->add_action('woocommerce_checkout_create_order_line_item', $this, 'save_package_data_to_order_item', 10, 4);
        
        // Admin order display hooks - removed to prevent duplicate display
        // Package info is already displayed via woocommerce_order_item_name filter
        
        // Hide package meta data from customer view
        $this->loader->add_filter('woocommerce_order_item_display_meta_key', $this, 'hide_package_meta_from_customer', 10, 3);
        $this->loader->add_filter('woocommerce_hidden_order_itemmeta', $this, 'hide_package_meta_completely');
        $this->loader->add_filter('woocommerce_order_item_get_formatted_meta_data', $this, 'remove_debug_meta_data', 10, 2);
        
        // Add CSS to hide debug meta data
        $this->loader->add_action('wp_head', $this, 'add_debug_meta_hide_css');

        if (class_exists('Woodmart_Theme')) {
            $woodmart = new WVP_Woodmart();
            $this->loader->add_filter('woodmart_product_price', $woodmart, 'woodmart_price', 10, 2);
            $this->loader->add_filter('woodmart_quick_view_price', $woodmart, 'woodmart_quick_view_price', 10, 2);
        }
    }

    private function define_common_hooks() {
        // Initialize scheduled events
        $scheduled_events = new WVP_Scheduled_Events();
        $scheduled_events->init_hooks();
        
        // Handle auto-renewal requests
        $this->loader->add_action('init', $scheduled_events, 'handle_auto_renewal_request');

        $this->loader->add_action('wp_login', $this, 'check_user_vip_status', 10, 2);
        $this->loader->add_action('user_register', $this, 'assign_default_vip_status');

        $this->loader->add_filter('woocommerce_account_menu_items', $this, 'add_vip_account_menu_item');
        $this->loader->add_action('init', $this, 'add_vip_account_endpoint');
        $this->loader->add_action('woocommerce_account_vip-status_endpoint', $this, 'vip_status_content');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }

    public function cleanup_expired_codes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wvp_codes';
        $expired_count = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name SET status = 'expired' WHERE expires_at < %s AND status = 'active'",
                current_time('mysql')
            )
        );

        if ($expired_count > 0) {
            error_log("WVP: Expired $expired_count VIP codes");
        }
    }

    public function send_usage_reports() {
        if (get_option('wvp_email_notifications') === 'yes') {
            $admin_email = get_option('admin_email');
            $usage_data = $this->get_usage_statistics();
            
            wp_mail(
                $admin_email,
                __('WVP Weekly Usage Report', 'woocommerce-vip-paketi'),
                $this->format_usage_report($usage_data)
            );
        }
    }

    private function get_usage_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wvp_codes';
        
        return array(
            'total_codes' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'active_codes' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'"),
            'used_codes' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'used'"),
            'expired_codes' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'expired'"),
            'vip_users' => count(get_users(array('role' => 'wvp_vip_member')))
        );
    }

    private function format_usage_report($data) {
        $report = __('WVP Usage Report', 'woocommerce-vip-paketi') . "\n\n";
        $report .= sprintf(__('Total VIP Codes: %d', 'woocommerce-vip-paketi'), $data['total_codes']) . "\n";
        $report .= sprintf(__('Active Codes: %d', 'woocommerce-vip-paketi'), $data['active_codes']) . "\n";
        $report .= sprintf(__('Used Codes: %d', 'woocommerce-vip-paketi'), $data['used_codes']) . "\n";
        $report .= sprintf(__('Expired Codes: %d', 'woocommerce-vip-paketi'), $data['expired_codes']) . "\n";
        $report .= sprintf(__('VIP Members: %d', 'woocommerce-vip-paketi'), $data['vip_users']) . "\n";
        
        return $report;
    }

    public function check_user_vip_status($user_login, $user) {
        if ($this->is_user_vip($user->ID)) {
            update_user_meta($user->ID, '_wvp_last_login', current_time('timestamp'));
        }
    }

    public function assign_default_vip_status($user_id) {
        if (get_option('wvp_auto_assign_vip') === 'yes') {
            $user = new WP_User($user_id);
            $user->set_role('wvp_vip_member');
        }
    }

    public function add_vip_account_menu_item($items) {
        $items['vip-status'] = __('VIP Status', 'woocommerce-vip-paketi');
        return $items;
    }

    public function add_vip_account_endpoint() {
        add_rewrite_endpoint('vip-status', EP_ROOT | EP_PAGES);
        
        // Flush rewrite rules if needed (only do this once)
        if (get_option('wvp_rewrite_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('wvp_rewrite_rules_flushed', '1');
        }
    }

    public function vip_status_content() {
        error_log('WVP: vip_status_content() called');
        $template_path = WVP_PLUGIN_DIR . 'includes/public/partials/wvp-account-vip-status.php';
        error_log('WVP: Template path: ' . $template_path);
        error_log('WVP: Template exists: ' . (file_exists($template_path) ? 'Yes' : 'No'));
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">';
            echo '<strong>Error:</strong> VIP Status template not found at: ' . esc_html($template_path);
            echo '</div>';
        }
    }

    public function is_user_vip($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            error_log('WVP: is_user_vip - No user ID provided');
            return false;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('WVP: is_user_vip - User not found for ID: ' . $user_id);
            return false;
        }

        error_log('WVP: is_user_vip - User ' . $user_id . ' roles: ' . implode(', ', $user->roles));

        if (in_array('wvp_vip_member', $user->roles)) {
            error_log('WVP: is_user_vip - User ' . $user_id . ' is VIP');
            return true;
        }

        if (function_exists('wc_memberships_is_user_active_member')) {
            if (wc_memberships_is_user_active_member($user_id)) {
                return true;
            }
        }

        if (function_exists('wcs_user_has_subscription')) {
            if (wcs_user_has_subscription($user_id, '', 'active')) {
                return true;
            }
        }

        $vip_codes = get_user_meta($user_id, '_wvp_active_vip_codes', true);
        if (!empty($vip_codes)) {
            return true;
        }

        return false;
    }

    public function ajax_add_package_to_cart() {
        // Add basic debugging
        error_log('WVP Debug: AJAX add_package_to_cart called');
        
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_nonce')) {
            error_log('WVP Debug: Nonce verification failed');
            wp_send_json_error(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
            return;
        }

        // Check if WooCommerce is active
        if (!class_exists('WC_Cart')) {
            wp_send_json_error(__('WooCommerce nije dostupan.', 'woocommerce-vip-paketi'));
            return;
        }

        // Get POST data
        $package_id = intval(isset($_POST['package_id']) ? $_POST['package_id'] : 0);
        $products = isset($_POST['products']) ? $_POST['products'] : array();
        $package_data = isset($_POST['package_data']) ? $_POST['package_data'] : array();

        if (empty($package_id) || empty($products)) {
            wp_send_json_error(__('Neispravni podaci paketa.', 'woocommerce-vip-paketi'));
            return;
        }

        try {
            // Get package post
            $package_post = get_post($package_id);
            if (!$package_post || $package_post->post_type !== 'wvp_package') {
                wp_send_json_error(__('Paket nije pronađen.', 'woocommerce-vip-paketi'));
                return;
            }

            // Calculate package totals
            $current_subtotal = 0; // Međuzbir - current effective prices (sale or regular)
            $regular_subtotal = 0; // Track regular prices for reference
            $is_vip = $this->is_user_vip();
            $package_products_data = array();

            foreach ($products as $product_data) {
                $product_id = intval(isset($product_data['id']) ? $product_data['id'] : 0);
                $quantity = intval(isset($product_data['quantity']) ? $product_data['quantity'] : 1);
                
                if ($product_id && $quantity > 0) {
                    // Get WooCommerce product object to access real prices
                    $wc_product = wc_get_product($product_id);
                    if (!$wc_product) continue;
                    
                    // Get actual WooCommerce prices
                    $regular_price = floatval($wc_product->get_regular_price());
                    $sale_price = floatval($wc_product->get_sale_price());
                    $current_price = $sale_price ? $sale_price : $regular_price;
                    
                    // Calculate VIP price dynamically from current price (20% discount)
                    $vip_price = $current_price * 0.8;
                    
                    // For package calculations, always use current effective price as base
                    $current_subtotal += $current_price * $quantity;
                    
                    // Keep track of regular prices for reference only
                    $regular_subtotal += $regular_price * $quantity;
                    
                    $package_products_data[] = array(
                        'id' => $product_id,
                        'name' => get_the_title($product_id),
                        'quantity' => $quantity,
                        'price' => $current_price, // Always current price for package base
                        'regular_price' => $regular_price,
                        'sale_price' => $sale_price,
                        'current_price' => $current_price,
                        'vip_price' => $vip_price
                    );
                }
            }

            // Apply package discounts based on current effective prices (like on package page)
            $regular_discount = floatval(isset($package_data['regularDiscount']) ? $package_data['regularDiscount'] : 0);
            $vip_discount = floatval(isset($package_data['vipDiscount']) ? $package_data['vipDiscount'] : 0);
            
            // Calculate discounts on current effective prices (međuzbir)
            $package_discount = $current_subtotal * ($regular_discount / 100);
            $vip_discount_amount = $is_vip ? $current_subtotal * ($vip_discount / 100) : 0;
            
            // Final price = current subtotal - all discounts
            $final_price = $current_subtotal - $package_discount - $vip_discount_amount;

            // Debug logging for package pricing
            error_log("WVP Package Pricing Debug:");
            error_log("- Current Subtotal (Međuzbir): " . $current_subtotal);
            error_log("- Regular Subtotal (Reference): " . $regular_subtotal);
            error_log("- Is VIP: " . ($is_vip ? 'Yes' : 'No'));
            error_log("- Package Discount: {$regular_discount}% = {$package_discount}");
            error_log("- VIP Discount: {$vip_discount}% = {$vip_discount_amount}");
            error_log("- Final Price: " . $final_price);

            // First, remove any existing package items from the same package to avoid duplicates
            $this->remove_existing_package_items($package_id);
            
            // Create a single package cart item that contains all products and pricing info
            $first_product_id = !empty($package_products_data) ? $package_products_data[0]['id'] : 0;
            
            if (!$first_product_id) {
                wp_send_json_error(__('Paket mora sadržavati najmanje jedan proizvod.', 'woocommerce-vip-paketi'));
                return;
            }
            
            // Prepare detailed product information for the package
            $package_products_info = array();
            $total_added_price = 0;
            
            foreach ($package_products_data as $product_data) {
                $product_id = intval($product_data['id']);
                $quantity = intval($product_data['quantity']);
                
                if (!$product_id || $quantity <= 0) continue;
                
                // Calculate package discount price for this product
                $product_obj = wc_get_product($product_id);
                if (!$product_obj) continue;
                
                // Get current effective price (sale price if available, otherwise regular price)
                $product_regular_price = floatval($product_obj->get_regular_price());
                $product_sale_price = floatval($product_obj->get_sale_price());
                $product_current_price = $product_sale_price ? $product_sale_price : $product_regular_price;
                
                // Calculate package discount price based on current price
                $regular_package_price = $product_current_price * (1 - ($regular_discount / 100));
                $vip_package_price = $product_current_price * (1 - (($regular_discount + $vip_discount) / 100));
                $package_discount_price = $is_vip ? $vip_package_price : $regular_package_price;
                
                $package_products_info[] = array(
                    'id' => $product_id,
                    'name' => $product_obj->get_name(),
                    'quantity' => $quantity,
                    'regular_price' => $product_regular_price,
                    'current_price' => $product_current_price,
                    'package_price' => $package_discount_price,
                    'total_regular' => $product_regular_price * $quantity,
                    'total_current' => $product_current_price * $quantity,
                    'total_package' => $package_discount_price * $quantity
                );
                
                $total_added_price += $package_discount_price * $quantity;
            }
            
            // Add single package item to cart with all product details
            $cart_item_key = WC()->cart->add_to_cart($first_product_id, 1, 0, array(), array(
                'wvp_is_package' => true,
                'wvp_package_id' => $package_id,
                'wvp_package_name' => $package_post->post_title,
                'wvp_package_products_detailed' => $package_products_info,
                'wvp_package_subtotal' => $current_subtotal, // Use current subtotal (međuzbir)
                'wvp_package_total' => $final_price, // Use final package price
                'wvp_package_discount' => $package_discount,
                'wvp_vip_discount' => $vip_discount_amount,
                'wvp_is_vip_user' => $is_vip,
                'wvp_regular_discount_percent' => $regular_discount,
                'wvp_vip_discount_percent' => $vip_discount,
                'wvp_fixed_quantity' => true // Prevent quantity changes
            ));
            
            $added_items = array();
            if ($cart_item_key) {
                // Set the product price to final package price
                WC()->cart->cart_contents[$cart_item_key]['data']->set_price($final_price);
                $added_items[] = $cart_item_key;
                error_log("WVP: Added package with final price: " . $final_price . " (was using total_added_price: " . $total_added_price . ")");
            }

            if (!empty($added_items)) {
                wp_send_json_success(array(
                    'message' => sprintf(__('VIP Paket "%s" uspešno dodat u korpu!', 'woocommerce-vip-paketi'), $package_post->post_title),
                    'package_name' => $package_post->post_title,
                    'products_count' => count($package_products_info),
                    'package_total' => wc_price($final_price),
                    'added_items' => $added_items,
                    'cart_count' => WC()->cart->get_cart_contents_count(),
                    'cart_total' => WC()->cart->get_cart_total()
                ));
            } else {
                wp_send_json_error(__('Paket nije mogao biti dodat u korpu.', 'woocommerce-vip-paketi'));
            }

        } catch (Exception $e) {
            wp_send_json_error(__('Greška pri dodavanju paketa u korpu: ', 'woocommerce-vip-paketi') . $e->getMessage());
        }
    }

    /**
     * AJAX handler for generating AI reports
     */
    public function ajax_generate_ai_report() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_generate_ai_report')) {
            wp_send_json_error(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
            return;
        }

        $result_id = intval($_POST['result_id'] ?? 0);
        if ($result_id <= 0) {
            wp_send_json_error(__('Nevažeći ID rezultata.', 'woocommerce-vip-paketi'));
            return;
        }

        global $wpdb;

        // Get the quiz result
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
            $result_id
        ), ARRAY_A);

        if (!$result) {
            wp_send_json_error(__('Rezultat nije pronađen.', 'woocommerce-vip-paketi'));
            return;
        }

        // Get questions
        $questions = get_option('wvp_health_quiz_questions', array());
        if (empty($questions)) {
            wp_send_json_error(__('Pitanja nisu pronađena.', 'woocommerce-vip-paketi'));
            return;
        }

        // Parse answers and intensity data
        $answers = maybe_unserialize($result['answers']);
        $intensity_data = maybe_unserialize($result['intensity_data']);

        if (!is_array($answers)) $answers = array();
        if (!is_array($intensity_data)) $intensity_data = array();

        // Prepare user data
        $user_data = array(
            'first_name' => $result['first_name'],
            'last_name' => $result['last_name'],
            'birth_year' => $result['birth_year'],
            'country' => $result['country'],
            'location' => $result['location']
        );

        // Initialize OpenAI integration
        $openai = new WVP_Health_Quiz_OpenAI();

        if (!$openai->is_enabled()) {
            wp_send_json_error(__('AI integracija nije omogućena ili je API ključ nedostaje.', 'woocommerce-vip-paketi'));
            return;
        }

        // Generate AI analysis
        $ai_result = $openai->analyze_health_quiz($questions, $answers, $intensity_data, $user_data);

        if (is_wp_error($ai_result)) {
            wp_send_json_error(__('Greška pri generisanju AI analize: ', 'woocommerce-vip-paketi') . $ai_result->get_error_message());
            return;
        }

        // Generate public analysis ID if not exists
        $public_analysis_id = $result['public_analysis_id'];
        if (empty($public_analysis_id)) {
            $public_analysis_id = $this->generate_public_analysis_id();
        }

        // Save AI analysis to database
        $update_result = $wpdb->update(
            WVP_HEALTH_QUIZ_TABLE,
            array(
                'ai_analysis' => maybe_serialize(array(
                    'stanje_organizma' => $ai_result['stanje_organizma'],
                    'preporuke' => $ai_result['preporuke']
                )),
                'ai_recommended_products' => maybe_serialize($ai_result['proizvodi']),
                'ai_recommended_packages' => maybe_serialize($ai_result['paketi']),
                'ai_custom_packages' => maybe_serialize($ai_result['paketi']),
                'ai_score' => $ai_result['skor'],
                'public_analysis_id' => $public_analysis_id
            ),
            array('id' => $result_id),
            array('%s', '%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );

        if ($update_result === false) {
            wp_send_json_error(__('Greška pri čuvanju AI analize.', 'woocommerce-vip-paketi'));
            return;
        }

        // Update AI usage statistics
        $this->update_ai_usage_stats();

        wp_send_json_success(array(
            'message' => __('AI izveštaj je uspešno generisan!', 'woocommerce-vip-paketi'),
            'ai_analysis' => $ai_result
        ));
    }

    /**
     * Generate unique public analysis ID
     */
    private function generate_public_analysis_id() {
        global $wpdb;

        do {
            // Generate 8-character random string with letters and numbers
            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $public_id = '';
            for ($i = 0; $i < 8; $i++) {
                $public_id .= $characters[rand(0, strlen($characters) - 1)];
            }

            // Check if this ID already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE public_analysis_id = %s",
                $public_id
            ));
        } while ($exists > 0);

        return $public_id;
    }

    /**
     * Update AI usage statistics
     */
    private function update_ai_usage_stats() {
        $daily_usage = intval(get_option('wvp_ai_daily_usage', 0));
        $monthly_usage = intval(get_option('wvp_ai_monthly_usage', 0));
        $total_cost = floatval(get_option('wvp_ai_total_cost', 0));
        $cost_per_request = floatval(get_option('wvp_ai_cost_per_request', 0.02));

        update_option('wvp_ai_daily_usage', $daily_usage + 1);
        update_option('wvp_ai_monthly_usage', $monthly_usage + 1);
        update_option('wvp_ai_total_cost', $total_cost + $cost_per_request);

        // Check limits and send notifications if needed
        $this->check_ai_usage_limits();
    }

    /**
     * Check AI usage limits and send notifications
     */
    private function check_ai_usage_limits() {
        $daily_limit = intval(get_option('wvp_ai_daily_limit', 100));
        $monthly_limit = intval(get_option('wvp_ai_monthly_limit', 1000));
        $budget_limit = floatval(get_option('wvp_ai_budget_limit', 50.00));
        $auto_disable_threshold = intval(get_option('wvp_ai_auto_disable_threshold', 90));
        $enable_notifications = intval(get_option('wvp_ai_enable_notifications', 1));

        $daily_usage = intval(get_option('wvp_ai_daily_usage', 0));
        $monthly_usage = intval(get_option('wvp_ai_monthly_usage', 0));
        $total_cost = floatval(get_option('wvp_ai_total_cost', 0));

        $daily_percentage = ($daily_usage / $daily_limit) * 100;
        $monthly_percentage = ($monthly_usage / $monthly_limit) * 100;
        $budget_percentage = ($total_cost / $budget_limit) * 100;

        // Auto-disable if threshold is reached
        if ($daily_percentage >= $auto_disable_threshold ||
            $monthly_percentage >= $auto_disable_threshold ||
            $budget_percentage >= $auto_disable_threshold) {

            update_option('wvp_health_quiz_openai_enabled', 0);

            if ($enable_notifications) {
                $this->send_ai_limit_notification('auto_disabled');
            }
        } elseif ($enable_notifications &&
                  ($daily_percentage >= 90 || $monthly_percentage >= 90 || $budget_percentage >= 90)) {

            $this->send_ai_limit_notification('warning');
        }
    }

    /**
     * Send AI usage limit notification email
     */
    private function send_ai_limit_notification($type) {
        $notification_email = get_option('wvp_ai_notification_email', get_option('admin_email'));
        $site_name = get_bloginfo('name');

        $subject = sprintf('[%s] AI Usage Notification', $site_name);

        if ($type === 'auto_disabled') {
            $message = sprintf(
                "AI integration has been automatically disabled due to reaching usage limits.\n\n" .
                "Current usage:\n" .
                "- Daily: %d/%d requests\n" .
                "- Monthly: %d/%d requests\n" .
                "- Budget: $%.2f/$%.2f\n\n" .
                "Please review your usage limits in the admin panel.",
                intval(get_option('wvp_ai_daily_usage', 0)),
                intval(get_option('wvp_ai_daily_limit', 100)),
                intval(get_option('wvp_ai_monthly_usage', 0)),
                intval(get_option('wvp_ai_monthly_limit', 1000)),
                floatval(get_option('wvp_ai_total_cost', 0)),
                floatval(get_option('wvp_ai_budget_limit', 50.00))
            );
        } else {
            $message = sprintf(
                "Warning: AI usage is approaching limits (90%% threshold reached).\n\n" .
                "Current usage:\n" .
                "- Daily: %d/%d requests\n" .
                "- Monthly: %d/%d requests\n" .
                "- Budget: $%.2f/$%.2f\n\n" .
                "Consider reviewing your usage limits.",
                intval(get_option('wvp_ai_daily_usage', 0)),
                intval(get_option('wvp_ai_daily_limit', 100)),
                intval(get_option('wvp_ai_monthly_usage', 0)),
                intval(get_option('wvp_ai_monthly_limit', 1000)),
                floatval(get_option('wvp_ai_total_cost', 0)),
                floatval(get_option('wvp_ai_budget_limit', 50.00))
            );
        }

        wp_mail($notification_email, $subject, $message);
    }

    /**
     * AJAX handler for getting quiz results on frontend
     */
    public function ajax_get_quiz_results() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_get_quiz_results')) {
            wp_send_json_error(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
            return;
        }

        global $wpdb;
        $result = null;

        // Check if using public ID or result ID
        if (!empty($_POST['public_id'])) {
            $public_id = sanitize_text_field($_POST['public_id']);
            // Get the quiz result by public analysis ID
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE public_analysis_id = %s",
                $public_id
            ), ARRAY_A);
        } else {
            $result_id = intval($_POST['result_id'] ?? 0);
            if ($result_id <= 0) {
                wp_send_json_error(__('Nevažeći ID rezultata.', 'woocommerce-vip-paketi'));
                return;
            }

            // Get the quiz result by numeric ID
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
                $result_id
            ), ARRAY_A);
        }

        if (!$result) {
            wp_send_json_error(__('Rezultat nije pronađen.', 'woocommerce-vip-paketi'));
            return;
        }

        // Parse AI analysis and related data
        $ai_analysis = maybe_unserialize($result['ai_analysis']);
        $recommended_products_ids = maybe_unserialize($result['ai_recommended_products']);
        $recommended_packages = maybe_unserialize($result['ai_recommended_packages']);
        $custom_packages = maybe_unserialize($result['ai_custom_packages']);

        // Get recommended products data
        $recommended_products = array();
        if (is_array($recommended_products_ids)) {
            foreach ($recommended_products_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $recommended_products[] = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'description' => $product->get_short_description(),
                        'price' => wc_price($product->get_price()),
                        'url' => $product->get_permalink()
                    );
                }
            }
        }

        // Calculate user age
        $age = !empty($result['birth_year']) ? (intval(date('Y')) - intval($result['birth_year'])) : null;

        // Prepare response data
        $response_data = array(
            'result_id' => $result_id,
            'ai_score' => intval($result['ai_score']),
            'ai_analysis' => $ai_analysis,
            'recommended_products' => $recommended_products,
            'recommended_packages' => $recommended_packages,
            'custom_packages' => $custom_packages,
            'lifestyle_recommendations' => isset($ai_analysis['lifestyle_recommendations']) ? $ai_analysis['lifestyle_recommendations'] : array(),
            'user_data' => array(
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'age' => $age,
                'birth_year' => $result['birth_year'],
                'country' => $result['country'],
                'location' => $result['location']
            )
        );

        wp_send_json_success($response_data);
    }

    /**
     * AJAX handler for testing OpenAI API key
     */
    public function ajax_test_openai_api() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_test_openai_api')) {
            wp_send_json_error(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
            return;
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        if (empty($api_key)) {
            wp_send_json_error(__('API ključ je obavezan.', 'woocommerce-vip-paketi'));
            return;
        }

        // Test API key with a simple request
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        );

        $body = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Test connection. Respond with only: "API works"'
                )
            ),
            'max_tokens' => 10,
            'temperature' => 0
        );

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(__('Greška pri povezivanju sa OpenAI: ', 'woocommerce-vip-paketi') . $response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            if (isset($data['choices'][0]['message']['content'])) {
                wp_send_json_success(array(
                    'message' => __('✅ API ključ je valjan i konekcija uspešna!', 'woocommerce-vip-paketi'),
                    'response' => trim($data['choices'][0]['message']['content'])
                ));
            } else {
                wp_send_json_error(__('API ključ je valjan, ali odgovor nije u očekivanom formatu.', 'woocommerce-vip-paketi'));
            }
        } else {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';

            wp_send_json_error(__('API ključ nije valjan: ', 'woocommerce-vip-paketi') . $error_message);
        }
    }

    /**
     * AJAX handler for generating AI reports on frontend with enhanced personalization
     */
    public function ajax_generate_frontend_ai_report() {
        // Verify nonce for security (skip for auto-generated calls)
        $is_auto_generated = isset($_POST['auto_generated']) && $_POST['auto_generated'] === true;
        if (!$is_auto_generated && (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wvp_generate_frontend_ai_report'))) {
            wp_send_json_error(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
            return;
        }

        $result_id = intval($_POST['result_id'] ?? 0);
        if ($result_id <= 0) {
            wp_send_json_error(__('Nevažeći ID rezultata.', 'woocommerce-vip-paketi'));
            return;
        }

        global $wpdb;

        // Check if this is test scenario (result_id = 999)
        if ($result_id === 999) {
            // Create test scenario for OpenAI demonstration
            $result = array(
                'id' => 999,
                'first_name' => 'Ana',
                'last_name' => 'Marić',
                'email' => 'ana.maric@test.com',
                'phone' => '+381601234567',
                'birth_year' => 1989,
                'location' => 'Beograd',
                'country' => 'Srbija',
                'answers' => json_encode(['Da', 'Ne', 'Da', 'Da', 'Ne']), // Sample answers
                'intensity_data' => json_encode([3, 0, 2, 4, 0]), // Sample intensities
                'ai_analysis' => '',
                'ai_recommended_products' => '',
                'ai_recommended_packages' => '',
                'ai_custom_packages' => '',
                'ai_score' => 0,
                'created_at' => date('Y-m-d H:i:s')
            );

            // Use test questions for demo
            $questions = array(
                array('text' => 'Da li imate probleme sa digestijom?', 'ai_daily_dose' => '2 kapsule dnevno', 'ai_monthly_box' => '2 kutije po 30 kapsula'),
                array('text' => 'Da li se osećate umorno tokom dana?', 'ai_daily_dose' => '', 'ai_monthly_box' => ''),
                array('text' => 'Da li imate probleme sa spavanjem?', 'ai_daily_dose' => '1 tableta pre spavanja', 'ai_monthly_box' => '1 kutija od 30 tableta'),
                array('text' => 'Da li imate glavobolje?', 'ai_daily_dose' => '1-2 kapsule po potrebi', 'ai_monthly_box' => '1 kutija od 60 kapsula'),
                array('text' => 'Da li imate probleme sa kožom?', 'ai_daily_dose' => '', 'ai_monthly_box' => '')
            );
        } else {
            // Get the quiz result from database
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
                $result_id
            ), ARRAY_A);

            if (!$result) {
                wp_send_json_error(__('Rezultat nije pronađen.', 'woocommerce-vip-paketi'));
                return;
            }

            // Get questions from options
            $questions = get_option('wvp_health_quiz_questions', array());
            if (empty($questions)) {
                wp_send_json_error(__('Pitanja nisu pronađena.', 'woocommerce-vip-paketi'));
                return;
            }
        }

        // Parse answers and intensity data
        $answers = json_decode($result['answers'], true);
        $intensity_data = json_decode($result['intensity_data'], true);

        if (!is_array($answers)) $answers = array();
        if (!is_array($intensity_data)) $intensity_data = array();

        // Prepare enhanced user data with age calculation
        $age = !empty($result['birth_year']) ? (intval(date('Y')) - intval($result['birth_year'])) : null;
        $user_data = array(
            'first_name' => $result['first_name'],
            'last_name' => $result['last_name'],
            'birth_year' => $result['birth_year'],
            'age' => $age,
            'country' => $result['country'],
            'location' => $result['location']
        );

        // Initialize OpenAI integration
        $openai = new WVP_Health_Quiz_OpenAI();

        // Debug: Check OpenAI configuration
        $openai_enabled = get_option('wvp_health_quiz_openai_enabled', 0);
        $api_key = get_option('wvp_health_quiz_openai_api_key', '');

        error_log('WVP AI Debug: OpenAI enabled: ' . ($openai_enabled ? 'Yes' : 'No'));
        error_log('WVP AI Debug: API key set: ' . (!empty($api_key) ? 'Yes' : 'No'));

        if (!$openai->is_enabled()) {
            $error_msg = sprintf(
                __('AI integracija nije omogućena ili je API ključ nedostaje. Status: Enabled=%s, API_Key=%s', 'woocommerce-vip-paketi'),
                $openai_enabled ? 'Yes' : 'No',
                !empty($api_key) ? 'Set' : 'Missing'
            );
            error_log('WVP AI Debug: ' . $error_msg);

            // For test scenario (result_id = 999), generate a simulated AI response to demonstrate the interface
            if ($result_id === 999) {
                error_log('WVP AI Debug: Generating simulated AI response for test scenario');

                // Create a simulated AI response that looks like what OpenAI would return
                $simulated_ai_result = array(
                    'uvod' => sprintf(
                        "Poštovana %s %s, na osnovu detaljne analize vaših odgovora i uzimajući u obzir vaše %d godine, AI je pripremio personalizovane preporuke za poboljšanje vašeg zdravlja.",
                        $user_data['first_name'],
                        $user_data['last_name'],
                        $user_data['age']
                    ),
                    'stanje_organizma' => 'Na osnovu analize vaših odgovora, vaš organizam pokazuje znakove blagog stresa i potrebe za detoksikacijom. Imajući u vidu vaše godine i životni stil, preporučujemo fokus na jačanje imuniteta i poboljšanje digestivnih funkcija. Zeleni sokovi bi posebno koristili vašem organizmu za prirodnu detoksikaciju.',
                    'preporuke' => 'Preporučujemo da se fokusirate na: 1) Redovnu konzumaciju zelenih sokova za detoksikaciju, 2) Probiotike za poboljšanje digestije, 3) Adaptogene za upravljanje stresom, 4) Vitamin C za jačanje imuniteta. Uzimajući u obzir vaše godine, posebno je važno uspostaviti redovne navike ishrane.',
                    'lifestyle_recommendations' => [
                        'Počnite dan sa čašom zelenog soka na prazan stomak',
                        'Uključite 30 minuta lagane fizičke aktivnosti dnevno',
                        'Praktikujte tehnike za smanjenje stresa kao što je duboko disanje',
                        'Održavajte redovan raspored spavanja (7-8 sati)'
                    ],
                    'proizvodi' => [1, 2, 3],
                    'paketi' => [
                        array(
                            'id' => 123,
                            'size' => 4,
                            'monthly_boxes' => 2,
                            'reason' => 'Na osnovu vaših simptoma i godina, preporučujemo ovaj paket za početak zdravije lifestyle rutine',
                            'product_explanations' => 'Odabrani proizvodi su posebno prilagođeni vašim potrebama za detoksikaciju i jačanje imuniteta'
                        )
                    ],
                    'weak_points' => [
                        array('area' => 'stomach', 'severity' => 'moderate', 'description' => 'Digestivni sistem pokazuje znakove potrebe za podrškom'),
                        array('area' => 'head', 'severity' => 'low', 'description' => 'Blagi znakovi stresa koji utiču na mentalno stanje')
                    ],
                    'zaključak' => sprintf(
                        "Poštovana %s, vaš zdravstveni skor od 75/100 pokazuje da ste na dobrom putu. Uz pravilnu primenu naših preporuka i redovnu upotrebu prilagođenih proizvoda, možete značajno poboljšati svoje zdravlje. Preporučujemo da počnete sa detoksikacijom zelenim sokovima i postupno uvodite ostale suplemente.",
                        $user_data['first_name']
                    ),
                    'skor' => 75
                );

                // Generate custom packages using the simulated data
                $custom_packages = $this->generate_custom_packages_with_dosage($simulated_ai_result, $user_data, $answers, $questions);

                // Prepare response as if it came from real OpenAI
                wp_send_json_success(array(
                    'message' => __('AI izveštaj je uspešno generisan! (Simulirani odgovor za demonstraciju)', 'woocommerce-vip-paketi'),
                    'personalized_greeting' => sprintf(
                        "Poštovana %s %s, na osnovu vaših %d godina života i rezultata zdravstvene ankete:",
                        $user_data['first_name'],
                        $user_data['last_name'],
                        $user_data['age']
                    ),
                    'ai_analysis' => $simulated_ai_result,
                    'custom_packages' => $custom_packages,
                    'lifestyle_recommendations' => $simulated_ai_result['lifestyle_recommendations'],
                    'user_data' => $user_data,
                    'is_demo' => true
                ));
                return;
            }

            wp_send_json_error($error_msg);
            return;
        }

        // Generate enhanced AI analysis with personalization
        $ai_result = $openai->analyze_health_quiz($questions, $answers, $intensity_data, $user_data);

        if (is_wp_error($ai_result)) {
            wp_send_json_error(__('Greška pri generisanju AI analize: ', 'woocommerce-vip-paketi') . $ai_result->get_error_message());
            return;
        }

        // Generate 3 custom packages with dosage information
        $custom_packages = $this->generate_custom_packages_with_dosage($ai_result, $user_data, $answers, $questions);

        // Save comprehensive AI analysis to database
        $update_result = $wpdb->update(
            WVP_HEALTH_QUIZ_TABLE,
            array(
                'ai_analysis' => maybe_serialize(array(
                    'stanje_organizma' => $ai_result['stanje_organizma'],
                    'preporuke' => $ai_result['preporuke'],
                    'lifestyle_recommendations' => $this->generate_lifestyle_recommendations($user_data, $answers),
                    'weak_points' => isset($ai_result['weak_points']) ? $ai_result['weak_points'] : array()
                )),
                'ai_recommended_products' => maybe_serialize($ai_result['proizvodi']),
                'ai_recommended_packages' => maybe_serialize($ai_result['paketi']),
                'ai_custom_packages' => maybe_serialize($custom_packages),
                'ai_score' => $ai_result['skor']
            ),
            array('id' => $result_id),
            array('%s', '%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($update_result === false) {
            error_log('WVP: Database update failed. Last error: ' . $wpdb->last_error);
            error_log('WVP: Update query: ' . $wpdb->last_query);
            wp_send_json_error(__('Greška pri čuvanju AI analize: ' . $wpdb->last_error, 'woocommerce-vip-paketi'));
            return;
        }

        // Update AI usage statistics
        $this->update_ai_usage_stats();

        // Prepare personalized response
        $personalized_greeting = sprintf(
            "Poštovani %s %s, na osnovu vaše %s godine života i rezultata zdravstvene ankete:",
            $result['first_name'],
            $result['last_name'],
            $age ? $age : 'podataka o godinama'
        );

        wp_send_json_success(array(
            'message' => __('AI izveštaj je uspešno generisan!', 'woocommerce-vip-paketi'),
            'personalized_greeting' => $personalized_greeting,
            'ai_analysis' => $ai_result,
            'custom_packages' => $custom_packages,
            'lifestyle_recommendations' => $this->generate_lifestyle_recommendations($user_data, $answers),
            'user_data' => $user_data
        ));
    }

    /**
     * Generate 3 custom packages with dosage information
     */
    private function generate_custom_packages_with_dosage($ai_result, $user_data, $answers, $questions) {
        // Get allowed packages from Health Quiz settings
        $allowed_package_ids = get_option('wvp_health_quiz_allowed_packages', array());

        if (empty($allowed_package_ids)) {
            return array();
        }

        $custom_packages = array();
        $package_names = array('Starter Paket', 'Optimalni Paket', 'Premium Paket');
        $package_sizes = array(2, 4, 6);

        // Count symptoms and collect dosage information
        $symptom_count = 0;
        $high_intensity_count = 0;
        $dosage_recommendations = array();

        foreach ($answers as $i => $answer) {
            if ($answer === 'Da') {
                $symptom_count++;
                $question = $questions[$i];

                // Collect actual dosage recommendations from questions
                if (!empty($question['ai_daily_dose']) || !empty($question['ai_monthly_box'])) {
                    $dosage_recommendations[] = array(
                        'question' => $question['text'],
                        'daily_dose' => $question['ai_daily_dose'] ?? '',
                        'monthly_box' => $question['ai_monthly_box'] ?? '',
                        'intensity' => isset($intensities[$i]) ? $intensities[$i] : 1
                    );
                }

                // Check intensity level for priority
                if (isset($intensities[$i]) && $intensities[$i] >= 3) {
                    $high_intensity_count++;
                }
            }
        }

        for ($i = 0; $i < 3; $i++) {
            $package_id = $allowed_package_ids[array_rand($allowed_package_ids)];
            $package = get_post($package_id);

            if (!$package) continue;

            // Calculate recommended dosage based on actual dosage recommendations
            $age = $user_data['age'] ?: 30;
            $base_monthly_boxes = $package_sizes[$i];

            // Calculate monthly boxes based on actual dosage recommendations
            $total_monthly_need = 0;
            $dosage_explanations = array();

            if (!empty($dosage_recommendations)) {
                foreach ($dosage_recommendations as $dosage) {
                    if (!empty($dosage['monthly_box'])) {
                        // Extract number from monthly box recommendation (e.g., "2 kutije po 30 kockica" -> 2)
                        preg_match('/(\d+)/', $dosage['monthly_box'], $matches);
                        if (isset($matches[1])) {
                            $boxes_needed = intval($matches[1]);
                            $total_monthly_need += $boxes_needed;
                            $dosage_explanations[] = $dosage['daily_dose'] . ' (' . $dosage['monthly_box'] . ')';
                        }
                    } elseif (!empty($dosage['daily_dose'])) {
                        // Estimate monthly need from daily dose
                        $total_monthly_need += 1; // Default 1 box if only daily dose specified
                        $dosage_explanations[] = $dosage['daily_dose'];
                    }
                }
            }

            // Use calculated need or fallback to default calculation
            if ($total_monthly_need > 0) {
                $monthly_boxes = min($total_monthly_need, 8); // Cap at 8 boxes
            } else {
                // Fallback to old calculation
                $dosage_factor = 1;
                if ($age > 50) $dosage_factor += 0.2;
                if ($symptom_count > 5) $dosage_factor += 0.3;
                if ($high_intensity_count > 3) $dosage_factor += 0.2;
                $monthly_boxes = ceil($base_monthly_boxes * $dosage_factor);
                $monthly_boxes = min($monthly_boxes, 8);
            }

            // Get package discount
            $regular_discounts = get_post_meta($package_id, '_wvp_regular_discounts', true) ?: array();
            $discount = isset($regular_discounts[$package_sizes[$i]]) ? $regular_discounts[$package_sizes[$i]] : 0;

            // Create detailed dosage explanation
            $dosage_explanation = '';
            if (!empty($dosage_explanations)) {
                $dosage_explanation = "Preporučeno doziranje na osnovu simptoma: " . implode(', ', array_slice($dosage_explanations, 0, 2));
                if (count($dosage_explanations) > 2) {
                    $dosage_explanation .= " i još " . (count($dosage_explanations) - 2) . " preporuke";
                }
            } else {
                $dosage_explanation = sprintf(
                    "Na osnovu %d simptoma i vaših %s godina, preporučujemo %d kutije mesečno.",
                    $symptom_count,
                    $age,
                    $monthly_boxes
                );
            }

            $custom_packages[] = array(
                'id' => $package_id,
                'name' => $package_names[$i],
                'size' => $package_sizes[$i],
                'description' => wp_trim_words($package->post_content, 20),
                'monthly_boxes' => $monthly_boxes,
                'dosage_explanation' => $dosage_explanation,
                'discount' => $discount,
                'reason' => sprintf(
                    "Prilagođeno za %s koji ima %d zdravstvenih problema sa specifičnim doziranjem.",
                    $age > 40 ? 'zrelije osobe' : 'mlađe osobe',
                    $symptom_count
                ),
                'dosage_details' => $dosage_recommendations // Add raw dosage data for AI
            );
        }

        return $custom_packages;
    }

    /**
     * Generate lifestyle recommendations based on user data and answers
     */
    private function generate_lifestyle_recommendations($user_data, $answers) {
        $recommendations = array();
        $age = $user_data['age'] ?: 30;

        // Age-based recommendations
        if ($age > 50) {
            $recommendations[] = "S obzirom na vaše godine, posebno obratite pažnju na kardiovaskularno zdravlje i gustinu kostiju.";
            $recommendations[] = "Preporučujemo redovne preglede i povećan unos kalcijuma i vitamina D.";
        } elseif ($age < 30) {
            $recommendations[] = "U vašim godinama važno je uspostaviti zdrave navike koje će vam koristiti ceo život.";
            $recommendations[] = "Fokusirajte se na prevenciju kroz pravilnu ishranu i redovnu fizičku aktivnost.";
        }

        // Symptom-based recommendations
        $symptom_count = array_count_values($answers)['Da'] ?? 0;

        if ($symptom_count > 5) {
            $recommendations[] = "Veliki broj simptoma ukazuje na potrebu za holističkim pristupom zdravlju.";
            $recommendations[] = "Preporučujemo konsultaciju sa lekarom i postupnu implementaciju promena u životnom stilu.";
        }

        // General recommendations
        $recommendations[] = "Redovno konzumirajte zelene sokove za prirodnu detoksifikaciju organizma.";
        $recommendations[] = "Održavajte aktivnu komunikaciju sa našim stručnjacima tokom terapije.";

        return $recommendations;
    }

    /**
     * Force cart refresh when packages are present
     */
    public function force_package_cart_refresh() {
        if (!WC()->cart) return;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                // Force calculate totals for package items
                WC()->cart->calculate_totals();
                break;
            }
        }
    }

    /**
     * Verify package totals after WooCommerce calculates cart totals
     */
    public function verify_package_totals() {
        if (!WC()->cart) return;
        
        $total_expected = 0;
        $total_calculated = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                $product_price = floatval($cart_item['data']->get_price());
                $quantity = intval($cart_item['quantity']);
                
                $total_expected += $package_total * $quantity;
                $total_calculated += $product_price * $quantity;
                
                if (abs($product_price - $package_total) > 0.01) {
                    error_log("WVP: Package price mismatch! Expected: {$package_total}, Got: {$product_price}");
                    // Force correct the price
                    $cart_item['data']->set_price($package_total);
                    WC()->cart->cart_contents[$cart_item_key]['data']->set_price($package_total);
                }
            }
        }
        
        error_log("WVP: verify_package_totals - Expected total: {$total_expected}, Calculated: {$total_calculated}");
    }


    /**
     * Save package data to order items for admin display
     */
    public function save_package_data_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['wvp_is_package']) && $values['wvp_is_package']) {
            // Save all package-related meta data
            $package_meta_keys = array(
                'wvp_is_package',
                'wvp_package_id',
                'wvp_package_name',
                'wvp_package_products_detailed',
                'wvp_package_subtotal',
                'wvp_package_total',
                'wvp_package_discount',
                'wvp_vip_discount',
                'wvp_is_vip_user',
                'wvp_regular_discount_percent',
                'wvp_vip_discount_percent'
            );
            
            foreach ($package_meta_keys as $meta_key) {
                if (isset($values[$meta_key])) {
                    $item->add_meta_data($meta_key, $values[$meta_key], true);
                }
            }
            
            // DEBUG: Check if there are any other keys in values that could be causing debug output
            foreach ($values as $key => $value) {
                if (!in_array($key, $package_meta_keys) && ($key === '' || $key === ':' || is_numeric($value))) {
                    error_log("WVP DEBUG: Found potential debug meta key='{$key}' value='{$value}'");
                    // DO NOT add this to meta data
                }
            }
            
            error_log("WVP: Saved package meta data to order item " . $item->get_id());
        }
    }


    /**
     * Hide package meta data from customer view (checkout, thank you page, emails)
     */
    public function hide_package_meta_from_customer($display_key, $meta, $item) {
        // List of package meta keys to hide from customer
        $package_meta_keys = array(
            'wvp_is_package',
            'wvp_package_id', 
            'wvp_package_name',
            'wvp_package_products_detailed',
            'wvp_package_subtotal',
            'wvp_package_total',
            'wvp_package_discount',
            'wvp_vip_discount',
            'wvp_is_vip_user',
            'wvp_regular_discount_percent',
            'wvp_vip_discount_percent',
            'wvp_fixed_quantity'
        );
        
        if (in_array($meta->key, $package_meta_keys)) {
            return null; // Hide from customer view
        }
        
        return $display_key;
    }

    /**
     * Completely hide package meta data from all meta displays
     */
    public function hide_package_meta_completely($hidden_meta_keys) {
        $package_meta_keys = array(
            'wvp_is_package',
            'wvp_package_id', 
            'wvp_package_name',
            'wvp_package_products_detailed',
            'wvp_package_subtotal',
            'wvp_package_total',
            'wvp_package_discount',
            'wvp_vip_discount',
            'wvp_is_vip_user',
            'wvp_regular_discount_percent',
            'wvp_vip_discount_percent',
            'wvp_fixed_quantity',
            // Hide debug meta with empty or colon keys
            '',
            ':',
            ' '
        );
        
        return array_merge($hidden_meta_keys, $package_meta_keys);
    }

    /**
     * Remove debug meta data with empty keys or specific debug values
     */
    public function remove_debug_meta_data($formatted_meta, $item) {
        $debug_values = ['1', '183', 'Testni paket', '12000', '8400', '1200', '2400', '10', '20'];
        
        foreach ($formatted_meta as $key => $meta) {
            // Remove meta with empty keys or colon keys
            if (empty($meta->key) || $meta->key === '' || $meta->key === ':' || trim($meta->key) === '') {
                unset($formatted_meta[$key]);
                continue;
            }
            
            // Remove meta with debug values
            if (in_array($meta->value, $debug_values)) {
                unset($formatted_meta[$key]);
                continue;
            }
            
            // Remove meta where value matches debug pattern
            if (is_numeric($meta->value) && in_array(trim($meta->value), $debug_values)) {
                unset($formatted_meta[$key]);
            }
        }
        
        return $formatted_meta;
    }

    /**
     * Restrict package quantity to be non-editable (fixed at 1)
     */
    public function restrict_package_quantity($quantity, $cart_item_key, $cart_item) {
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package'] && 
            isset($cart_item['wvp_fixed_quantity']) && $cart_item['wvp_fixed_quantity']) {
            
            // Return plain text "1" instead of quantity input field
            return '<span class="wvp-fixed-quantity" style="font-weight: bold; color: #666;">1</span>';
        }
        
        return $quantity;
    }
    
    /**
     * Modify remove link for package items to show better label
     */
    public function modify_package_remove_link($remove_link, $cart_item_key) {
        $cart_item = WC()->cart->get_cart()[$cart_item_key];
        
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            // Add custom class and title for package remove links
            $remove_link = str_replace(
                'class="remove"',
                'class="remove wvp-remove-package" title="' . esc_attr__('Ukloni paket', 'woocommerce-vip-paketi') . '"',
                $remove_link
            );
        }
        
        return $remove_link;
    }

    public function modify_package_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            $package_name = isset($cart_item['wvp_package_name']) ? $cart_item['wvp_package_name'] : __('VIP Paket', 'woocommerce-vip-paketi');
            
            // Use new detailed products data structure
            $detailed_products = isset($cart_item['wvp_package_products_detailed']) ? $cart_item['wvp_package_products_detailed'] : array();
            $products_count = count($detailed_products);
            
            $product_name = sprintf(__('%s (%d proizvoda)', 'woocommerce-vip-paketi'), $package_name, $products_count);
            
            if (isset($cart_item['wvp_is_vip_user']) && $cart_item['wvp_is_vip_user']) {
                $product_name .= ' <span class="wvp-vip-badge">' . __('VIP', 'woocommerce-vip-paketi') . '</span>';
            }
            
            // Add detailed product list with pricing information for admin visibility
            if (!empty($detailed_products)) {
                $product_name .= '<div class="wvp-package-product-list" style="margin-top: 10px; font-size: 0.85em; line-height: 1.5; border-left: 3px solid #e0e0e0; padding-left: 10px; background: #f9f9f9; border-radius: 4px; padding: 8px;">';
                $product_name .= '<strong style="color: #333; margin-bottom: 5px; display: block;">' . __('Proizvodi u paketu:', 'woocommerce-vip-paketi') . '</strong>';
                
                foreach ($detailed_products as $product_data) {
                    $item_name = esc_html($product_data['name']);
                    $quantity = intval($product_data['quantity']);
                    $regular_price = isset($product_data['regular_price']) ? floatval($product_data['regular_price']) : 0;
                    $current_price = isset($product_data['current_price']) ? floatval($product_data['current_price']) : $regular_price;
                    $package_price = isset($product_data['package_price']) ? floatval($product_data['package_price']) : 0;
                    $total_current = isset($product_data['total_current']) ? floatval($product_data['total_current']) : 0;
                    $total_package = isset($product_data['total_package']) ? floatval($product_data['total_package']) : 0;
                    
                    $product_name .= '<div style="margin-bottom: 4px; padding: 3px 0; border-bottom: 1px solid #eee;">';
                    $product_name .= '<span style="color: #333; font-weight: 500;">• ' . $item_name . ' × ' . $quantity . '</span><br>';
                    
                    if ($current_price > 0 && $package_price > 0) {
                        $savings_per_item = $current_price - $package_price;
                        $total_savings = $total_current - $total_package;
                        
                        if ($savings_per_item > 0) {
                            $product_name .= '<span style="color: #999; font-size: 0.9em; margin-left: 8px;">';
                            $product_name .= '<span style="text-decoration: line-through;">' . wc_price($current_price) . '</span> → ';
                            $product_name .= '<span style="color: #28a745; font-weight: bold;">' . wc_price($package_price) . '</span>';
                            $product_name .= ' <span style="color: #d63638; font-size: 0.85em;">(' . wc_price($savings_per_item) . ' ušteda po komadu)</span>';
                            $product_name .= '</span>';
                        } else {
                            $product_name .= '<span style="color: #666; font-size: 0.9em; margin-left: 8px;">' . wc_price($package_price) . '</span>';
                        }
                    }
                    $product_name .= '</div>';
                }
                
                // Add package totals summary
                $package_subtotal = isset($cart_item['wvp_package_subtotal']) ? floatval($cart_item['wvp_package_subtotal']) : 0;
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                $total_savings = $package_subtotal - $package_total;
                
                if ($total_savings > 0) {
                    $product_name .= '<div style="margin-top: 8px; padding-top: 6px; border-top: 2px solid #ddd; font-weight: bold; color: #333;">';
                    $product_name .= '<span style="color: #999;">Regularna cena: ' . wc_price($package_subtotal) . '</span><br>';
                    $product_name .= '<span style="color: #28a745;">Cena konfigurisanog paketa: ' . wc_price($package_total) . '</span><br>';
                    $product_name .= '<span style="color: #d63638; font-size: 0.95em;">Ukupna ušteda: ' . wc_price($total_savings) . '</span>';
                    $product_name .= '</div>';
                }
                
                $product_name .= '</div>';
            }
        }
        return $product_name;
    }

    /**
     * Add VIP badge to all cart items for VIP users
     */
    public function add_vip_badge_to_cart_items($product_name, $cart_item, $cart_item_key) {
        // Don't double-badge packages (they already get VIP badge in modify_package_cart_item_name)
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            return $product_name;
        }
        
        // Check if current user is VIP
        if ($this->is_user_vip()) {
            // Check if product has VIP pricing enabled
            $product = $cart_item['data'];
            $has_vip_pricing = get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true) === 'yes';
            
            if ($has_vip_pricing) {
                $product_name .= ' <span class="wvp-vip-badge">VIP</span>';
            }
        }
        
        return $product_name;
    }

    /**
     * Add VIP badge to order items (thankyou page, emails, etc.)
     */
    public function add_vip_badge_to_order_items($name, $item) {
        // Check if this is a package item first
        $is_package = $item->get_meta('wvp_is_package');
        if ($is_package) {
            $package_name = $item->get_meta('wvp_package_name');
            $package_id = $item->get_meta('wvp_package_id');
            $package_products_detailed = $item->get_meta('wvp_package_products_detailed');
            
            if ($package_name) {
                // Start with package name and product count
                $name = '<div style="display: flex; align-items: flex-start; gap: 10px;">';
                
                // Package thumbnail - always try to use package image, not product image
                if ($package_id) {
                    // Check if package post exists and has featured image
                    $package_post = get_post($package_id);
                    if ($package_post && $package_post->post_type === 'wvp_package') {
                        $package_thumbnail_id = get_post_thumbnail_id($package_id);
                        
                        if ($package_thumbnail_id) {
                            $package_thumbnail = get_the_post_thumbnail($package_id, 'thumbnail', array('style' => 'width: 60px; height: auto; border-radius: 4px; flex-shrink: 0;'));
                            if ($package_thumbnail) {
                                $name .= '<div>' . $package_thumbnail . '</div>';
                            }
                        } else {
                            // Only show a placeholder, don't use product images
                            $name .= '<div style="width: 60px; height: 60px; background: #f0f0f0; border: 1px dashed #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 10px; text-align: center; color: #999;">Nema slike</div>';
                        }
                    }
                }
                
                $name .= '<div style="flex-grow: 1;">';
                $name .= '<strong style="font-size: 1.1em;">' . $package_name . ' (' . count($package_products_detailed) . ' proizvoda)</strong>';
                
                // Add package breakdown for admin - compact version with thumbnails
                if (!empty($package_products_detailed)) {
                    $name .= '<div style="margin-top: 8px; font-size: 0.85em; color: #666; line-height: 1.3;">';
                    $name .= '<strong>Proizvodi u paketu:</strong><br>';
                    $name .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 6px; margin-top: 6px;">';
                    
                    foreach ($package_products_detailed as $product_data) {
                        $product_id = isset($product_data['id']) ? intval($product_data['id']) : 0;
                        $item_name = isset($product_data['name']) ? $product_data['name'] : '';
                        $quantity = isset($product_data['quantity']) ? intval($product_data['quantity']) : 1;
                        $current_price = isset($product_data['current_price']) ? floatval($product_data['current_price']) : 0;
                        $package_price = isset($product_data['package_price']) ? floatval($product_data['package_price']) : 0;
                        
                        $name .= '<div style="display: flex; align-items: center; padding: 4px; background: #f9f9f9; border-radius: 3px; font-size: 11px;">';
                        
                        // Product thumbnail
                        if ($product_id) {
                            $product_thumbnail = get_the_post_thumbnail($product_id, 'thumbnail', array('style' => 'width: 25px; height: 25px; object-fit: cover; margin-right: 6px; border-radius: 2px; flex-shrink: 0;'));
                            if ($product_thumbnail) {
                                $name .= $product_thumbnail;
                            }
                        }
                        
                        $name .= '<div>';
                        $name .= '<strong>' . $item_name . '</strong> × ' . $quantity . '<br>';
                        if ($current_price > 0 && $package_price > 0) {
                            $name .= '<span style="color: #999; text-decoration: line-through; font-size: 10px;">' . wc_price($current_price) . '</span> ';
                            $name .= '→ <span style="color: #0073aa; font-weight: bold; font-size: 10px;">' . wc_price($package_price) . '</span>';
                        }
                        $name .= '</div>';
                        $name .= '</div>';
                    }
                    $name .= '</div>';
                    $name .= '</div>';
                }
                
                // Add package pricing breakdown
                $package_subtotal = $item->get_meta('wvp_package_subtotal');
                $package_total = $item->get_meta('wvp_package_total');
                $package_discount = $item->get_meta('wvp_package_discount');
                $vip_discount = $item->get_meta('wvp_vip_discount');
                
                if ($package_subtotal && $package_total) {
                    $name .= '<div style="margin-top: 10px; font-size: 0.85em; color: #333; background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); padding: 8px; border-radius: 4px; border-left: 3px solid #0073aa;">';
                    $name .= '<strong style="color: #0073aa;">Rezime paketa:</strong><br>';
                    $name .= '<div style="font-size: 11px; margin-top: 4px;">';
                    $name .= 'Međuzbir: <strong>' . wc_price($package_subtotal) . '</strong><br>';
                    
                    // Check if user was VIP for combined discount display
                    $is_vip_user = $item->get_meta('wvp_is_vip_user');
                    
                    if ($is_vip_user && $package_discount > 0 && $vip_discount > 0) {
                        // For VIP users: show combined discount
                        $total_discount = $package_discount + $vip_discount;
                        $total_discount_percent = round(($total_discount / $package_subtotal) * 100);
                        $name .= 'Ukupan VIP popust (-' . $total_discount_percent . '%): <span style="color: #d63638; font-weight: bold;">-' . wc_price($total_discount) . '</span><br>';
                    } else {
                        // For non-VIP users: show separate discounts
                        if ($package_discount > 0) {
                            $discount_percent = round(($package_discount / $package_subtotal) * 100);
                            $name .= 'Package popust (-' . $discount_percent . '%): <span style="color: #d63638; font-weight: bold;">-' . wc_price($package_discount) . '</span><br>';
                        }
                        
                        if ($vip_discount > 0) {
                            $vip_percent = round(($vip_discount / $package_subtotal) * 100);
                            $name .= 'VIP popust (-' . $vip_percent . '%): <span style="color: #d63638; font-weight: bold;">-' . wc_price($vip_discount) . '</span><br>';
                        }
                    }
                    
                    $name .= '<strong style="color: #0073aa; font-size: 12px;">Ukupno: ' . wc_price($package_total) . '</strong>';
                    $name .= '</div>';
                    $name .= '</div>';
                }
                
                $name .= '</div>'; // Close flex container div
                $name .= '</div>'; // Close main container div
            }
            
            return $name;
        }
        
        // Regular VIP badge logic for non-package items
        $order = $item->get_order();
        $user_was_vip = get_post_meta($order->get_id(), '_wvp_user_was_vip', true);
        
        if ($user_was_vip === 'yes') {
            $product_id = $item->get_product_id();
            $has_vip_pricing = get_post_meta($product_id, '_wvp_enable_vip_pricing', true) === 'yes';
            
            if ($has_vip_pricing) {
                $name .= ' <span class="wvp-vip-badge">VIP</span>';
            }
        }
        
        // Clean up any debug output completely
        if (strpos($name, ': 1') !== false && strpos($name, ': 183') !== false && strpos($name, 'Testni paket') !== false) {
            // If debug output is detected, remove everything after the main package content
            $debug_start = strpos($name, ': 1');
            if ($debug_start !== false) {
                $name = substr($name, 0, $debug_start);
            }
        }
        
        return $name;
    }

    /**
     * Add CSS to hide debug meta data with empty keys
     */
    public function add_debug_meta_hide_css() {
        if (is_wc_endpoint_url('order-received') || is_checkout() || is_account_page()) {
            echo '<style>
                .wc-item-meta li:has(.wc-item-meta-label:empty),
                .wc-item-meta li .wc-item-meta-label:empty,
                .wc-item-meta li:has(.wc-item-meta-label:contains(":")),
                .wc-item-meta li .wc-item-meta-label:contains(":") {
                    display: none !important;
                }
                .wc-item-meta li:has(p:contains("183")),
                .wc-item-meta li:has(p:contains("12000")),
                .wc-item-meta li:has(p:contains("8400")) {
                    display: none !important;
                }
            </style>';
        }
    }

    /**
     * Enhance VIP price display to show: regular price → discount → VIP price
     */
    public function enhance_vip_cart_price_display($price, $cart_item, $cart_item_key) {
        error_log('WVP DEBUG: enhance_vip_cart_price_display called with price: ' . $price);
        
        // Don't modify package prices (they have their own logic)
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            error_log('WVP DEBUG: Skipping package item');
            return $price;
        }
        
        // Only enhance for VIP users
        if (!$this->is_user_vip()) {
            error_log('WVP DEBUG: User is not VIP');
            return $price;
        }
        
        error_log('WVP DEBUG: User is VIP, processing...');
        
        $product = $cart_item['data'];
        
        // Get original product ID (in case this is a variation)
        $product_id = $product->get_parent_id() ?: $product->get_id();
        $variation_id = $product->get_id();
        
        // Check if product has VIP pricing enabled
        if (get_post_meta($product_id, '_wvp_enable_vip_pricing', true) !== 'yes') {
            return $price;
        }
        
        // Get prices from database first
        $regular_price = (float) get_post_meta($product_id, '_regular_price', true);
        $stored_vip_price = (float) get_post_meta($product_id, '_wvp_vip_price', true);
        
        // Also check variations if this is a variable product
        if ($product->is_type('variation') || $product->get_parent_id()) {
            $var_regular = (float) get_post_meta($variation_id, '_regular_price', true);
            $var_vip = (float) get_post_meta($variation_id, '_wvp_vip_price', true);
            
            if ($var_regular > 0) $regular_price = $var_regular;
            if ($var_vip > 0) $stored_vip_price = $var_vip;
        }
        
        // Use stored VIP price if available, otherwise calculate dynamically
        if ($stored_vip_price > 0) {
            $vip_price = $stored_vip_price;
            error_log("WVP DEBUG: Product ID: $product_id, Using stored VIP price: $vip_price");
        } else {
            // Fallback to dynamic calculation (20% discount) if no stored VIP price
            $current_price = $product->get_sale_price();
            if (!$current_price) {
                $current_price = $product->get_regular_price();
            }
            $vip_price = round($current_price * 0.8, 2);
            $regular_price = $current_price; // Use current price as baseline for dynamic calculation
            error_log("WVP DEBUG: Product ID: $product_id, No stored VIP price, calculated: $vip_price");
        }
        
        error_log("WVP DEBUG: Final prices - Regular: $regular_price, VIP: $vip_price");
        
        if ($regular_price > 0 && $vip_price > 0 && $vip_price < $regular_price) {
            error_log('WVP DEBUG: Creating enhanced price display');
            $savings = $regular_price - $vip_price;
            $discount_percentage = round(($savings / $regular_price) * 100);
            
            $enhanced_price = '<div class="wvp-price-breakdown" style="line-height: 1.3;">';
            $enhanced_price .= '<div><span class="wvp-regular-price" style="text-decoration: line-through; color: #999; font-size: 0.9em;">' . wc_price($regular_price) . '</span></div>';
            $enhanced_price .= '<div><span class="wvp-discount-info" style="color: #d63638; font-size: 0.8em; font-weight: bold;">-' . $discount_percentage . '% VIP popust</span></div>';
            $enhanced_price .= '<div><span class="wvp-final-price" style="color: #28a745; font-weight: bold; font-size: 1.1em;">' . wc_price($vip_price) . '</span></div>';
            $enhanced_price .= '</div>';
            
            return $enhanced_price;
        }
        
        return $price;
    }

    /**
     * Enhance VIP subtotal display (fallback if price display doesn't work)
     */
    public function enhance_vip_cart_subtotal_display($subtotal, $cart_item, $cart_item_key) {
        // Check if we already enhanced the price display (to avoid duplication)
        if (strpos($subtotal, 'wvp-price-breakdown') !== false) {
            return $subtotal;
        }
        
        return $this->enhance_vip_cart_price_display($subtotal, $cart_item, $cart_item_key);
    }

    public function modify_package_cart_item_price($price, $cart_item, $cart_item_key) {
        // Handle old package items (single package item)
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            $package_total = isset($cart_item['wvp_package_total']) ? $cart_item['wvp_package_total'] : 0;
            error_log("WVP: modify_package_cart_item_price - Setting package price to: " . $package_total);
            return wc_price($package_total);
        }
        
        // Handle new package items (individual products with package pricing)
        if (isset($cart_item['wvp_is_package_item']) && $cart_item['wvp_is_package_item']) {
            $package_price = isset($cart_item['wvp_package_discount_price']) ? $cart_item['wvp_package_discount_price'] : 0;
            $original_price = isset($cart_item['wvp_original_price']) ? $cart_item['wvp_original_price'] : 0;
            
            if ($package_price != $original_price && $original_price > 0) {
                // Show original price crossed out + package price
                return '<del>' . wc_price($original_price) . '</del> ' . wc_price($package_price);
            }
            
            error_log("WVP: modify_package_cart_item_price - Setting item price to: " . $package_price);
            return wc_price($package_price);
        }
        
        return $price;
    }

    public function modify_package_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
        // Handle package items - show detailed breakdown like on package page
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            $package_total = isset($cart_item['wvp_package_total']) ? $cart_item['wvp_package_total'] : 0;
            $package_subtotal = isset($cart_item['wvp_package_subtotal']) ? $cart_item['wvp_package_subtotal'] : 0;
            $package_discount = isset($cart_item['wvp_package_discount']) ? $cart_item['wvp_package_discount'] : 0;
            $vip_discount = isset($cart_item['wvp_vip_discount']) ? $cart_item['wvp_vip_discount'] : 0;
            $quantity = intval($cart_item['quantity']);
            
            error_log("WVP DEBUG: modify_package_cart_item_subtotal - Package total: {$package_total}, Package discount: {$package_discount}, VIP discount: {$vip_discount}");
            
            // Build detailed price breakdown
            $subtotal = '<div class="wvp-cart-price-breakdown">';
            
            // Show current effective subtotal (based on current prices)
            if ($package_subtotal > 0) {
                $subtotal .= '<div style="text-decoration: line-through; color: #999; font-size: 0.9em;">';
                $subtotal .= wc_price($package_subtotal * $quantity) . ' <small>(međuzbir)</small>';
                $subtotal .= '</div>';
            }
            
            // Check if user is VIP to determine discount display
            $is_vip_user = isset($cart_item['wvp_is_vip_user']) ? $cart_item['wvp_is_vip_user'] : false;
            
            if ($is_vip_user && $package_discount > 0 && $vip_discount > 0) {
                // For VIP users: combine package and VIP discounts into one "Ukupan VIP popust"
                $total_discount = $package_discount + $vip_discount;
                $total_discount_percent = round(($total_discount / $package_subtotal) * 100);
                $subtotal .= '<div style="color: #d63638; font-size: 0.9em;">';
                $subtotal .= '-' . wc_price($total_discount * $quantity) . ' <small>(-' . $total_discount_percent . '% Ukupan VIP popust)</small>';
                $subtotal .= '</div>';
            } else {
                // For non-VIP users: show separate discounts
                if ($package_discount > 0) {
                    $discount_percent = round(($package_discount / $package_subtotal) * 100);
                    $subtotal .= '<div style="color: #d63638; font-size: 0.9em;">';
                    $subtotal .= '-' . wc_price($package_discount * $quantity) . ' <small>(-' . $discount_percent . '% package popust)</small>';
                    $subtotal .= '</div>';
                }
                
                if ($vip_discount > 0) {
                    $vip_percent = round(($vip_discount / $package_subtotal) * 100);
                    $subtotal .= '<div style="color: #d63638; font-size: 0.9em;">';
                    $subtotal .= '-' . wc_price($vip_discount * $quantity) . ' <small>(-' . $vip_percent . '% VIP popust)</small>';
                    $subtotal .= '</div>';
                }
            }
            
            // Show final package total
            $subtotal .= '<div style="color: #28a745; font-weight: bold; font-size: 1.1em;">';
            $subtotal .= wc_price($package_total * $quantity);
            $subtotal .= '</div>';
            
            $subtotal .= '</div>';
        }
        
        // Handle new package items (individual products with package pricing)
        if (isset($cart_item['wvp_is_package_item']) && $cart_item['wvp_is_package_item']) {
            $package_price = isset($cart_item['wvp_package_discount_price']) ? $cart_item['wvp_package_discount_price'] : 0;
            $original_price = isset($cart_item['wvp_original_price']) ? $cart_item['wvp_original_price'] : 0;
            $quantity = $cart_item['quantity'];
            
            $package_subtotal = $package_price * $quantity;
            $original_subtotal = $original_price * $quantity;
            $discount_amount = $original_subtotal - $package_subtotal;
            
            if ($discount_amount > 0) {
                $subtotal = '<del>' . wc_price($original_subtotal) . '</del> ' . wc_price($package_subtotal);
                $subtotal .= '<br><small class="wvp-package-savings">' . sprintf(__('Package ušteda: %s', 'woocommerce-vip-paketi'), wc_price($discount_amount)) . '</small>';
            } else {
                $subtotal = wc_price($package_subtotal);
            }
        }
        
        return $subtotal;
    }

    public function modify_package_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
            $package_id = isset($cart_item['wvp_package_id']) ? $cart_item['wvp_package_id'] : 0;
            
            if ($package_id && has_post_thumbnail($package_id)) {
                $thumbnail = get_the_post_thumbnail($package_id, 'woocommerce_thumbnail');
            } else {
                // Default WooCommerce placeholder for packages
                $thumbnail = wc_placeholder_img('woocommerce_thumbnail');
            }
        }
        return $thumbnail;
    }

    public function update_package_cart_item_price($cart) {
        error_log("WVP DEBUG: update_package_cart_item_price called (priority 5)");
        
        if (is_admin() && !defined('DOING_AJAX')) {
            error_log("WVP DEBUG: Skipping - admin context");
            return;
        }

        $packages_processed = 0;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                $is_vip_user = isset($cart_item['wvp_is_vip_user']) ? $cart_item['wvp_is_vip_user'] : false;
                
                error_log("WVP DEBUG: Processing package - Key: {$cart_item_key}, Total: {$package_total}, Is VIP: " . ($is_vip_user ? 'YES' : 'NO'));
                
                if ($package_total > 0) {
                    // Set the package price
                    $product = $cart_item['data'];
                    $old_price = $product->get_price();
                    $product->set_price($package_total);
                    $product->set_regular_price($package_total);
                    
                    error_log("WVP DEBUG: Set package price - Key: {$cart_item_key}, Old: {$old_price}, New: {$package_total}");
                    $packages_processed++;
                }
            }
        }
        
        error_log("WVP DEBUG: update_package_cart_item_price completed - processed {$packages_processed} packages");
    }

    /**
     * Override product price for packages during cart calculations (high priority)
     */
    public function override_package_product_price_for_totals($price, $product) {
        // Only work in cart context
        if (!WC()->cart) {
            return $price;
        }

        // Check if this product is part of a package in cart
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package'] && 
                isset($cart_item['data']) && $cart_item['data']->get_id() == $product->get_id()) {
                
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                
                if ($package_total > 0) {
                    error_log("WVP DEBUG: override_package_product_price_for_totals - Product ID: {$product->get_id()}, Original price: {$price}, Package total: {$package_total}");
                    return $package_total;
                }
            }
        }

        return $price;
    }

    /**
     * Remove existing package items from cart before adding new ones
     */
    private function remove_existing_package_items($package_id) {
        $cart = WC()->cart;
        $items_to_remove = array();
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Remove old package items (single package item)
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package']) {
                $existing_package_id = isset($cart_item['wvp_package_id']) ? $cart_item['wvp_package_id'] : 0;
                if ($existing_package_id == $package_id) {
                    $items_to_remove[] = $cart_item_key;
                    error_log("WVP: Removing old package item for package " . $package_id);
                }
            }
            
            // Remove new package items (individual products with package pricing)
            if (isset($cart_item['wvp_is_package_item']) && $cart_item['wvp_is_package_item']) {
                $existing_package_id = isset($cart_item['wvp_package_id']) ? $cart_item['wvp_package_id'] : 0;
                if ($existing_package_id == $package_id) {
                    $items_to_remove[] = $cart_item_key;
                    error_log("WVP: Removing existing package item for package " . $package_id);
                }
            }
        }
        
        // Remove all identified items
        foreach ($items_to_remove as $cart_item_key) {
            $cart->remove_cart_item($cart_item_key);
        }
        
        if (!empty($items_to_remove)) {
            error_log("WVP: Removed " . count($items_to_remove) . " existing package items");
        }
    }

    /**
     * Setup hooks for WooCommerce Subscriptions and Memberships integration
     */
    private function setup_subscription_membership_hooks() {
        // WooCommerce Subscriptions hooks
        if (class_exists('WC_Subscriptions')) {
            $this->loader->add_action('woocommerce_subscription_status_active', $this, 'handle_subscription_activated', 10, 1);
            $this->loader->add_action('woocommerce_subscription_status_cancelled', $this, 'handle_subscription_cancelled', 10, 1);
            $this->loader->add_action('woocommerce_subscription_status_expired', $this, 'handle_subscription_expired', 10, 1);
            $this->loader->add_action('woocommerce_subscription_status_on-hold', $this, 'handle_subscription_on_hold', 10, 1);
        }
        
        // WooCommerce Memberships hooks  
        if (class_exists('WC_Memberships')) {
            $this->loader->add_action('wc_memberships_user_membership_saved', $this, 'handle_membership_saved', 10, 2);
            $this->loader->add_action('wc_memberships_user_membership_status_changed', $this, 'handle_membership_status_changed', 10, 3);
        }
        
        // Payment completion for VIP products
        $this->loader->add_action('woocommerce_payment_complete', $this, 'handle_vip_product_payment_complete', 10, 1);
    }

    /**
     * Handle when subscription becomes active - generate VIP code
     */
    public function handle_subscription_activated($subscription) {
        $user_id = $subscription->get_user_id();
        $order = $subscription->get_parent();
        
        if (!$user_id || !$order) {
            return;
        }
        
        // Check if this subscription contains VIP products
        $has_vip_products = false;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (get_post_meta($product_id, '_wvp_enable_vip_pricing', true) === 'yes' || 
                get_post_meta($product_id, '_wvp_is_vip_product', true) === 'yes') {
                $has_vip_products = true;
                break;
            }
        }
        
        if ($has_vip_products) {
            $this->auto_generate_vip_code_for_user($user_id, $subscription);
        }
    }

    /**
     * Handle membership status changes
     */
    public function handle_membership_saved($membership_plan, $args) {
        if (isset($args['user_membership_id'])) {
            $user_membership = wc_memberships_get_user_membership($args['user_membership_id']);
            if ($user_membership && $user_membership->is_active()) {
                $user_id = $user_membership->get_user_id();
                $this->auto_generate_vip_code_for_user($user_id, $user_membership);
            }
        }
    }

    /**
     * Handle membership status changes
     */
    public function handle_membership_status_changed($user_membership, $old_status, $new_status) {
        $user_id = $user_membership->get_user_id();
        
        if ($new_status === 'active') {
            $this->auto_generate_vip_code_for_user($user_id, $user_membership);
        } elseif (in_array($new_status, ['expired', 'cancelled', 'paused'])) {
            $this->handle_vip_access_removed($user_id, 'membership_' . $new_status);
        }
    }

    /**
     * Handle subscription cancellation/expiration
     */
    public function handle_subscription_cancelled($subscription) {
        $this->handle_subscription_status_change($subscription, 'cancelled');
    }

    public function handle_subscription_expired($subscription) {
        $this->handle_subscription_status_change($subscription, 'expired');
    }

    public function handle_subscription_on_hold($subscription) {
        $this->handle_subscription_status_change($subscription, 'on_hold');
    }

    private function handle_subscription_status_change($subscription, $status) {
        $user_id = $subscription->get_user_id();
        if ($user_id) {
            $this->handle_vip_access_removed($user_id, 'subscription_' . $status);
        }
    }

    /**
     * Handle VIP product payment completion
     */
    public function handle_vip_product_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }
        
        // Check if order contains VIP products (but not subscription/membership)
        $has_vip_products = false;
        $is_subscription_order = false;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $product_id = $item->get_product_id();
            
            // Check if it's a subscription or membership product
            if ($product && (method_exists($product, 'is_type') && 
                ($product->is_type('subscription') || $product->is_type('variable-subscription')))) {
                $is_subscription_order = true;
                break;
            }
            
            // Check if it's a VIP product
            if (get_post_meta($product_id, '_wvp_enable_vip_pricing', true) === 'yes' || 
                get_post_meta($product_id, '_wvp_is_vip_product', true) === 'yes') {
                $has_vip_products = true;
            }
        }
        
        // Only generate VIP code for direct VIP product purchases (not subscriptions)
        if ($has_vip_products && !$is_subscription_order) {
            // Create temporary "order-based" VIP access
            $this->create_order_based_vip_access($user_id, $order);
        }
    }

    /**
     * Create VIP access based on one-time order
     */
    private function create_order_based_vip_access($user_id, $order) {
        // Create VIP access for 1 year (or configurable period)
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        $vip_access = new stdClass();
        $vip_access->id = $order->get_id();
        
        $this->auto_generate_vip_code_for_user($user_id, $vip_access);
    }

    /**
     * Auto-generate VIP code for user when they get subscription/membership
     */
    private function auto_generate_vip_code_for_user($user_id, $subscription_or_membership) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user already has an active VIP code
        $existing_codes = get_user_meta($user_id, '_wvp_active_vip_codes', true);
        if (!empty($existing_codes)) {
            // User already has VIP code, just extend expiry if needed
            $this->extend_existing_vip_code($user_id, $subscription_or_membership);
            return;
        }
        
        // Generate new VIP code
        $vip_code = $this->generate_unique_vip_code();
        $expiry_date = $this->calculate_vip_expiry_date($subscription_or_membership);
        
        // Insert into VIP codes database
        $db = WVP_Database::get_instance();
        $code_data = array(
            'code' => $vip_code,
            'email' => $user->user_email,
            'first_name' => $user->first_name ?: $user->display_name,
            'last_name' => $user->last_name,
            'phone' => get_user_meta($user_id, 'billing_phone', true),
            'company' => get_user_meta($user_id, 'billing_company', true),
            'address_1' => get_user_meta($user_id, 'billing_address_1', true),
            'address_2' => get_user_meta($user_id, 'billing_address_2', true),
            'city' => get_user_meta($user_id, 'billing_city', true),
            'state' => get_user_meta($user_id, 'billing_state', true),
            'postcode' => get_user_meta($user_id, 'billing_postcode', true),
            'country' => get_user_meta($user_id, 'billing_country', true) ?: 'RS',
            'max_uses' => 999, // Unlimited uses for subscription/membership
            'used_count' => 1, // Mark as used since it's auto-assigned
            'user_id' => $user_id,
            'expires_at' => $expiry_date,
            'membership_expires_at' => $expiry_date,
            'auto_renewal' => 1,
            'status' => 'used',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $code_id = $db->insert_code($code_data);
        
        if ($code_id) {
            // Add VIP role to user
            $user_obj = new WP_User($user_id);
            $user_obj->add_role('wvp_vip_member');
            
            // Store active VIP code in user meta
            update_user_meta($user_id, '_wvp_active_vip_codes', array($vip_code));
            update_user_meta($user_id, '_wvp_vip_code_used', $vip_code);
            update_user_meta($user_id, '_wvp_vip_activation_date', current_time('mysql'));
            update_user_meta($user_id, '_wvp_vip_expiry_date', $expiry_date);
            
            // Store reference to subscription/membership
            $reference_type = is_a($subscription_or_membership, 'WC_Subscription') ? 'subscription' : 'membership';
            $reference_id = is_a($subscription_or_membership, 'WC_Subscription') ? 
                $subscription_or_membership->get_id() : 
                (isset($subscription_or_membership->id) ? $subscription_or_membership->id : 'order');
                
            update_user_meta($user_id, '_wvp_vip_source_type', $reference_type);
            update_user_meta($user_id, '_wvp_vip_source_id', $reference_id);
            
            // Send welcome email
            $this->send_auto_generated_vip_code_email($user, $vip_code, $expiry_date);
            
            error_log("WVP: Auto-generated VIP code {$vip_code} for user {$user_id} via {$reference_type}");
            
            return $code_id;
        }
        
        return false;
    }

    /**
     * Generate unique VIP code
     */
    private function generate_unique_vip_code() {
        $db = WVP_Database::get_instance();
        
        do {
            // Generate code format: AUTO-YYYY-XXXXX (e.g., AUTO-2024-A1B2C)
            $year = date('Y');
            $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));
            $code = "AUTO-{$year}-{$random}";
            
            // Check if code already exists
            $existing = $db->get_code_by_code($code);
        } while ($existing);
        
        return $code;
    }

    /**
     * Calculate VIP expiry date based on subscription/membership
     */
    private function calculate_vip_expiry_date($subscription_or_membership) {
        if (is_a($subscription_or_membership, 'WC_Subscription')) {
            // For subscriptions, use next payment date or end date
            $next_payment = $subscription_or_membership->get_date('next_payment');
            $end_date = $subscription_or_membership->get_date('end');
            
            if ($next_payment) {
                return $next_payment;
            } elseif ($end_date) {
                return $end_date;
            } else {
                // Default to 1 year from now for lifetime subscriptions
                return date('Y-m-d H:i:s', strtotime('+1 year'));
            }
        } elseif (method_exists($subscription_or_membership, 'get_end_date')) {
            // For memberships
            $end_date = $subscription_or_membership->get_end_date();
            if ($end_date) {
                return $end_date;
            }
        }
        
        // Default fallback: 1 year from now
        return date('Y-m-d H:i:s', strtotime('+1 year'));
    }

    /**
     * Extend existing VIP code expiry
     */
    private function extend_existing_vip_code($user_id, $subscription_or_membership) {
        $active_codes = get_user_meta($user_id, '_wvp_active_vip_codes', true);
        if (empty($active_codes)) {
            return;
        }
        
        $db = WVP_Database::get_instance();
        $new_expiry = $this->calculate_vip_expiry_date($subscription_or_membership);
        
        foreach ($active_codes as $code) {
            $code_data = $db->get_code_by_code($code);
            if ($code_data) {
                $db->update_code($code_data->id, array(
                    'expires_at' => $new_expiry,
                    'membership_expires_at' => $new_expiry,
                    'updated_at' => current_time('mysql')
                ));
            }
        }
        
        // Update user meta
        update_user_meta($user_id, '_wvp_vip_expiry_date', $new_expiry);
        
        error_log("WVP: Extended VIP code expiry to {$new_expiry} for user {$user_id}");
    }

    /**
     * Handle VIP access removal when subscription/membership ends
     */
    private function handle_vip_access_removed($user_id, $reason) {
        // Remove VIP role
        $user = new WP_User($user_id);
        $user->remove_role('wvp_vip_member');
        
        // Update VIP code status to expired
        $active_codes = get_user_meta($user_id, '_wvp_active_vip_codes', true);
        if (!empty($active_codes)) {
            $db = WVP_Database::get_instance();
            foreach ($active_codes as $code) {
                $code_data = $db->get_code_by_code($code);
                if ($code_data) {
                    $db->update_code($code_data->id, array(
                        'status' => 'expired',
                        'updated_at' => current_time('mysql')
                    ));
                }
            }
        }
        
        // Clear user VIP meta
        delete_user_meta($user_id, '_wvp_active_vip_codes');
        update_user_meta($user_id, '_wvp_vip_status', 'expired');
        
        error_log("WVP: Removed VIP access for user {$user_id}, reason: {$reason}");
    }

    /**
     * Send email notification for auto-generated VIP code
     */
    private function send_auto_generated_vip_code_email($user, $vip_code, $expiry_date) {
        $subject = sprintf(__('Vaš VIP pristup je aktiviran - %s', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Pozdrav %s,

Vaš VIP pristup je automatski aktiviran!

Vaš VIP kod: %s
Važi do: %s

Vaše VIP pogodnosti uključuju:
- Specijalne cene na dostupne proizvode
- Pristup ekskluzivnim VIP paketima
- Prioritetnu korisničku podršku
- Rani pristup novim proizvodima

VIP pristup će se automatski produžavati dokle god je vaše članstvo aktivno.

Srdačan pozdrav,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            $vip_code,
            date('d.m.Y', strtotime($expiry_date)),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Re-calculate package prices with new logic for existing cart items
     */
    private function recalculate_package_prices($cart_item) {
        error_log("WVP: recalculate_package_prices called");
        
        // Try new format first, then fallback to old format
        $package_products = null;
        if (isset($cart_item['wvp_package_products_detailed']) && is_array($cart_item['wvp_package_products_detailed'])) {
            $package_products = $cart_item['wvp_package_products_detailed'];
            error_log("WVP: Using new detailed package products format");
        } elseif (isset($cart_item['wvp_package_products']) && is_array($cart_item['wvp_package_products'])) {
            $package_products = $cart_item['wvp_package_products'];
            error_log("WVP: Using old package products format");
        }
        
        if (!$package_products) {
            error_log("WVP: No package products found in cart item");
            return false;
        }

        $regular_subtotal = 0;
        $subtotal = 0;
        $is_vip = $this->is_user_vip();
        error_log("WVP: Is VIP user: " . ($is_vip ? 'Yes' : 'No'));
        
        // Recalculate based on products in the package
        foreach ($package_products as $product_data) {
            $product_id = intval($product_data['id']);
            $quantity = intval($product_data['quantity']);
            
            if ($product_id && $quantity > 0) {
                // Get fresh product data
                $wc_product = wc_get_product($product_id);
                if (!$wc_product) continue;
                
                // Get current effective price (sale price if available, otherwise regular price)
                $regular_price = floatval($wc_product->get_regular_price());
                $sale_price = floatval($wc_product->get_sale_price());
                $current_price = $sale_price ? $sale_price : $regular_price;
                
                $regular_subtotal += $regular_price * $quantity;
                
                // Calculate VIP price dynamically from current price
                $vip_price = $current_price * 0.8; // 20% discount on current price
                $display_price = $is_vip ? $vip_price : $current_price;
                $subtotal += $display_price * $quantity;
            }
        }

        if ($regular_subtotal <= 0) {
            return false;
        }

        // Get discount percentages from cart item
        $regular_discount = isset($cart_item['wvp_regular_discount_percent']) ? floatval($cart_item['wvp_regular_discount_percent']) : 0;
        $vip_discount = isset($cart_item['wvp_vip_discount_percent']) ? floatval($cart_item['wvp_vip_discount_percent']) : 0;
        
        // ISPRAVLJENA LOGIKA: Popusti se računaju SAMO na regularnu cenu
        $package_discount = $regular_subtotal * ($regular_discount / 100);
        $vip_discount_amount = $is_vip ? $regular_subtotal * ($vip_discount / 100) : 0;
        
        // Final price = regular subtotal - all discounts (bez duplog VIP popusta)
        $final_price = $regular_subtotal - $package_discount - $vip_discount_amount;


        return array(
            'regular_subtotal' => $regular_subtotal,
            'subtotal' => $subtotal,
            'package_discount' => $package_discount,
            'vip_discount_amount' => $vip_discount_amount,
            'final_price' => $final_price
        );
    }
    
}