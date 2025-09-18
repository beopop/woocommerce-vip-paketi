<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Activator {

    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::create_vip_role();
        self::register_post_types();
        self::schedule_events();
        
        // Set flag to flush rewrite rules on next admin load
        update_option('wvp_flush_rewrite_rules', 'yes');
        
        update_option('wvp_version', WVP_VERSION);
        update_option('wvp_activation_time', current_time('timestamp'));
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'wvp_codes';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL UNIQUE,
            email varchar(100) DEFAULT NULL,
            domain varchar(100) DEFAULT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            company varchar(100) DEFAULT NULL,
            address_1 varchar(200) DEFAULT NULL,
            address_2 varchar(200) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            state varchar(100) DEFAULT NULL,
            postcode varchar(20) DEFAULT NULL,
            country varchar(10) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            purchase_count int(11) NOT NULL DEFAULT 0,
            total_spent decimal(10,2) NOT NULL DEFAULT 0.00,
            last_purchase_date datetime DEFAULT NULL,
            membership_expires_at datetime DEFAULT NULL,
            auto_renewal tinyint(1) NOT NULL DEFAULT 0,
            max_uses int(11) DEFAULT 1,
            current_uses int(11) DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            last_warning_sent datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive','expired','used') DEFAULT 'active',
            PRIMARY KEY (id),
            KEY idx_code (code),
            KEY idx_email (email),
            KEY idx_status (status),
            KEY idx_user_id (user_id),
            KEY idx_membership_expires (membership_expires_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if ($wpdb->last_error) {
            error_log('WVP Database Error: ' . $wpdb->last_error);
        }
    }

    private static function create_default_options() {
        $default_options = array(
            'wvp_enable_vip_pricing' => 'yes',
            'wvp_vip_role_enabled' => 'yes',
            'wvp_vip_price_label' => 'VIP Cena',
            'wvp_non_vip_display_format' => 'both',
            'wvp_enable_checkout_codes' => 'yes',
            'wvp_auto_registration' => 'yes',
            'wvp_email_notifications' => 'yes',
            'wvp_package_allowed_products' => array(),
            'wvp_enable_packages' => 'yes',
            'wvp_woodmart_integration' => 'yes'
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    private static function create_vip_role() {
        if (!get_role('wvp_vip_member')) {
            $customer_caps = get_role('customer')->capabilities;
            
            add_role('wvp_vip_member', __('VIP Clan', 'woocommerce-vip-paketi'), array_merge($customer_caps, array(
                'wvp_vip_pricing' => true,
                'wvp_vip_packages' => true
            )));
        }
    }

    private static function register_post_types() {
        // Register package post type
        register_post_type('wvp_package', array(
            'labels' => array(
                'name' => __('VIP Paketi', 'woocommerce-vip-paketi'),
                'singular_name' => __('VIP Paket', 'woocommerce-vip-paketi'),
                'add_new_item' => __('Dodaj Novi Paket', 'woocommerce-vip-paketi'),
                'edit_item' => __('Izmeni Paket', 'woocommerce-vip-paketi'),
                'new_item' => __('Novi Paket', 'woocommerce-vip-paketi'),
                'view_item' => __('Prikaži Paket', 'woocommerce-vip-paketi'),
                'search_items' => __('Pretraži Pakete', 'woocommerce-vip-paketi'),
                'not_found' => __('Nema pronađenih paketa', 'woocommerce-vip-paketi'),
                'not_found_in_trash' => __('Nema pronađenih paketa u korpi', 'woocommerce-vip-paketi')
            ),
            'public' => true,
            'has_archive' => true,
            'show_ui' => true,
            'show_in_menu' => false, // We handle this in admin
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'paketi'),
            'show_in_rest' => true
        ));
    }

    private static function schedule_events() {
        if (!wp_next_scheduled('wvp_cleanup_expired_codes')) {
            wp_schedule_event(time(), 'daily', 'wvp_cleanup_expired_codes');
        }

        if (!wp_next_scheduled('wvp_send_usage_reports')) {
            wp_schedule_event(time(), 'weekly', 'wvp_send_usage_reports');
        }

        if (!wp_next_scheduled('wvp_check_membership_expiry')) {
            wp_schedule_event(time(), 'daily', 'wvp_check_membership_expiry');
        }

        if (!wp_next_scheduled('wvp_process_auto_renewals')) {
            wp_schedule_event(time(), 'daily', 'wvp_process_auto_renewals');
        }
    }
}