<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET['ajax'] or $_POST['ajax']
 * - Redirect with the appropriate $url
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check
if (!current_user_can('activate_plugins')) {
    exit;
}

// Check that the plugin is actually being uninstalled
$plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
if ($plugin !== plugin_basename(__FILE__)) {
    exit;
}

/**
 * Only remove ALL plugin data if the constant is set.
 * This prevents accidental data loss.
 */
if (!defined('WVP_REMOVE_ALL_DATA')) {
    return;
}

if (defined('WVP_REMOVE_ALL_DATA') && WVP_REMOVE_ALL_DATA === true) {
    
    global $wpdb;

    // Drop custom tables
    $table_name = $wpdb->prefix . 'wvp_codes';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Delete options
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
        'wvp_woodmart_integration',
        'wvp_woodmart_vip_color',
        'wvp_woodmart_badge_position',
        'wvp_db_version'
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }

    // Delete user meta
    $meta_keys = array(
        '_wvp_vip_status',
        '_wvp_vip_code_used',
        '_wvp_vip_activation_date',
        '_wvp_vip_expiry_date',
        '_wvp_active_vip_codes',
        '_wvp_last_login'
    );

    foreach ($meta_keys as $meta_key) {
        $wpdb->delete($wpdb->usermeta, array('meta_key' => $meta_key));
    }

    // Delete post meta
    $post_meta_keys = array(
        '_wvp_vip_price',
        '_wvp_enable_vip_pricing',
        '_wvp_package_allowed',
        '_wvp_package_config',
        '_wvp_allowed_products',
        '_wvp_min_items',
        '_wvp_max_items',
        '_wvp_package_sizes',
        '_wvp_regular_discounts',
        '_wvp_vip_discounts',
        '_wvp_allow_coupons',
        '_wvp_show_discount_table',
        '_wvp_show_for_non_vip',
        '_wvp_package_status'
    );

    foreach ($post_meta_keys as $meta_key) {
        $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key));
    }

    // Delete order meta (for order analytics)
    $order_meta_keys = array(
        '_wvp_user_was_vip',
        '_wvp_total_vip_savings',
        '_wvp_vip_price_used',
        '_wvp_regular_price',
        '_wvp_vip_price',
        '_wvp_savings'
    );

    foreach ($order_meta_keys as $meta_key) {
        $wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key));
    }

    // Delete custom posts (packages)
    $packages = get_posts(array(
        'post_type' => 'wvp_package',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    foreach ($packages as $package) {
        wp_delete_post($package->ID, true);
    }

    // Remove custom roles
    remove_role('wvp_vip_member');

    // Clear scheduled hooks
    wp_clear_scheduled_hook('wvp_cleanup_expired_codes');
    wp_clear_scheduled_hook('wvp_send_usage_reports');

    // Delete transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wvp_%' OR option_name LIKE '_transient_timeout_wvp_%'");
    
    // For multisite
    if (is_multisite()) {
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '%wvp_%'");
    }

    // Clear any cached data
    wp_cache_flush();
    
    // Log the uninstall (if logging is enabled)
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('WVP Plugin: All data removed during uninstall at ' . current_time('mysql'));
    }
}