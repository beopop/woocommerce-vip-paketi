<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Scheduled_Events {

    private $db;
    private $checkout;

    public function __construct() {
        $this->db = WVP_Database::get_instance();
        $this->checkout = new WVP_Checkout();
    }

    public function init_hooks() {
        add_action('wvp_cleanup_expired_codes', array($this, 'cleanup_expired_codes'));
        add_action('wvp_send_usage_reports', array($this, 'send_usage_reports'));
        add_action('wvp_check_membership_expiry', array($this, 'check_membership_expiry'));
        add_action('wvp_process_auto_renewals', array($this, 'process_auto_renewals'));
    }

    public function cleanup_expired_codes() {
        error_log('WVP: Running cleanup_expired_codes scheduled event');

        $result = $this->db->cleanup_expired_codes();
        
        if ($result !== false) {
            error_log('WVP: Cleanup completed - ' . $result . ' codes marked as expired');
        } else {
            error_log('WVP: Cleanup failed - ' . $this->db->wpdb->last_error);
        }
    }

    public function send_usage_reports() {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        error_log('WVP: Running send_usage_reports scheduled event');

        $stats = $this->db->get_statistics();
        $admin_email = get_option('admin_email');
        
        if (!$admin_email) {
            return;
        }

        $subject = sprintf(__('[%s] Weekly VIP Codes Usage Report', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Weekly VIP Codes Usage Report

Total Codes: %d
Active Codes: %d
Used Codes: %d
Expired Codes: %d
Inactive Codes: %d

Usage Rate: %s%%

Generated on: %s

Best regards,
VIP Codes System', 'woocommerce-vip-paketi'),
            $stats['total'],
            $stats['active'],
            $stats['used'],
            $stats['expired'],
            $stats['inactive'],
            $stats['usage_rate'],
            current_time('F j, Y g:i a')
        );

        wp_mail($admin_email, $subject, $message);
        error_log('WVP: Usage report sent to ' . $admin_email);
    }

    public function check_membership_expiry() {
        error_log('WVP: Running check_membership_expiry scheduled event');

        // Get codes expiring in next 7 days
        $expiring_codes = $this->db->get_expiring_memberships(7);
        
        foreach ($expiring_codes as $code) {
            if ($code->user_id) {
                // Send expiry warning
                $this->send_expiry_warning($code);
            }
        }

        // Get already expired codes
        $expired_codes = $this->db->get_expiring_memberships(0);
        
        foreach ($expired_codes as $code) {
            if ($code->user_id) {
                // Check and remove VIP status if expired
                $this->checkout->check_membership_expiry($code->user_id);
            }
        }

        error_log('WVP: Checked ' . count($expiring_codes) . ' expiring and ' . count($expired_codes) . ' expired memberships');
    }

    public function process_auto_renewals() {
        error_log('WVP: Running process_auto_renewals scheduled event');

        // Get codes with auto_renewal enabled that are expiring soon
        global $wpdb;
        $table_name = $this->db->get_table_name();
        
        $auto_renewal_codes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE auto_renewal = 1 
                AND membership_expires_at IS NOT NULL 
                AND membership_expires_at <= %s 
                AND status = 'active'
                ORDER BY membership_expires_at ASC",
                date('Y-m-d H:i:s', strtotime('+3 days'))
            )
        );

        foreach ($auto_renewal_codes as $code) {
            $this->send_auto_renewal_prompt($code);
        }

        error_log('WVP: Processed ' . count($auto_renewal_codes) . ' auto-renewal prompts');
    }

    private function send_expiry_warning($code_data) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $code_data->user_id);
        if (!$user) {
            return;
        }

        $days_until_expiry = ceil((strtotime($code_data->membership_expires_at) - current_time('timestamp')) / 86400);
        
        $subject = sprintf(__('[%s] VIP Membership Expiring Soon', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Your VIP membership will expire in %d day(s) on %s.

To continue enjoying your VIP benefits:
- Special pricing on products
- Access to exclusive VIP packages  
- Priority customer support

Please renew your membership before it expires or contact us for assistance.

VIP Code: %s

Best regards,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            $days_until_expiry,
            date('F j, Y', strtotime($code_data->membership_expires_at)),
            $code_data->code,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
        
        // Update code to mark warning sent (add a flag)
        $this->db->update_code($code_data->id, array(
            'last_warning_sent' => current_time('mysql')
        ));

        error_log('WVP: Expiry warning sent to user ' . $code_data->user_id);
    }

    private function send_auto_renewal_prompt($code_data) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $code_data->user_id);
        if (!$user) {
            return;
        }

        $renewal_url = add_query_arg(array(
            'wvp_auto_renew' => 1,
            'code_id' => $code_data->id,
            'user_id' => $code_data->user_id
        ), home_url());

        $subject = sprintf(__('[%s] Auto-Renewal Reminder', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Your VIP membership with auto-renewal is set to expire on %s.

Click the link below to proceed with automatic renewal:
%s

If you prefer not to renew automatically, please disable auto-renewal in your account settings.

VIP Code: %s

Best regards,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            date('F j, Y', strtotime($code_data->membership_expires_at)),
            $renewal_url,
            $code_data->code,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
        error_log('WVP: Auto-renewal prompt sent to user ' . $code_data->user_id);
    }

    public function handle_auto_renewal_request() {
        if (!isset($_GET['wvp_auto_renew']) || !isset($_GET['code_id']) || !isset($_GET['user_id'])) {
            return;
        }

        $code_id = intval($_GET['code_id']);
        $user_id = intval($_GET['user_id']);

        // Verify the code belongs to the user
        $code = $this->db->get_code_by_id($code_id);
        if (!$code || $code->user_id != $user_id) {
            wp_die(__('Invalid renewal request', 'woocommerce-vip-paketi'));
        }

        // Process the renewal (this would typically integrate with WooCommerce)
        $purchase_data = array(
            'amount' => get_option('wvp_renewal_price', 50.00),
            'membership_duration_days' => get_option('wvp_membership_duration', 365)
        );

        $result = $this->checkout->process_membership_renewal($user_id, $purchase_data);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        // Redirect to success page
        wp_redirect(add_query_arg('wvp_renewed', 'success', home_url()));
        exit;
    }
}