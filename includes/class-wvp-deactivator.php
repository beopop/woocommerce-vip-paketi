<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Deactivator {

    public static function deactivate() {
        self::clear_scheduled_events();
        self::flush_rewrite_rules();
        
        update_option('wvp_deactivation_time', current_time('timestamp'));
    }

    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('wvp_cleanup_expired_codes');
        wp_clear_scheduled_hook('wvp_send_usage_reports');
    }

    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    public static function uninstall() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        self::drop_tables();
        self::delete_options();
        self::delete_user_meta();
        self::delete_post_meta();
        self::remove_custom_role();
        self::clear_scheduled_events();
    }

    private static function drop_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wvp_codes';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        delete_option('wvp_db_version');
    }

    private static function delete_options() {
        $options_to_delete = array(
            'wvp_version',
            'wvp_activation_time',
            'wvp_deactivation_time',
            'wvp_enable_vip_pricing',
            'wvp_vip_role_enabled',
            'wvp_vip_price_label',
            'wvp_non_vip_display_format',
            'wvp_enable_checkout_codes',
            'wvp_auto_registration',
            'wvp_email_notifications',
            'wvp_package_allowed_products',
            'wvp_enable_packages',
            'wvp_woodmart_integration'
        );

        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
    }

    private static function delete_user_meta() {
        global $wpdb;

        $meta_keys = array(
            '_wvp_vip_status',
            '_wvp_vip_code_used',
            '_wvp_vip_activation_date',
            '_wvp_vip_expiry_date'
        );

        foreach ($meta_keys as $meta_key) {
            $wpdb->delete($wpdb->usermeta, array('meta_key' => $meta_key));
        }
    }

    private static function delete_post_meta() {
        global $wpdb;

        $meta_keys = array(
            '_wvp_vip_price',
            '_wvp_enable_vip_pricing',
            '_wvp_package_allowed',
            '_wvp_package_config'
        );

        foreach ($meta_keys as $meta_key) {
            $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key));
        }
    }

    private static function remove_custom_role() {
        remove_role('wvp_vip_member');
    }
}