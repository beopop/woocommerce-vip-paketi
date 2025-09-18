<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Database {

    private static $instance = null;
    private $wpdb;
    private $table_codes;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_codes = $wpdb->prefix . 'wvp_codes';
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_table_name() {
        return $this->table_codes;
    }

    public function insert_code($data) {
        $defaults = array(
            'code' => '',
            'email' => null,
            'domain' => null,
            'first_name' => null,
            'last_name' => null,
            'company' => null,
            'address_1' => null,
            'address_2' => null,
            'city' => null,
            'state' => null,
            'postcode' => null,
            'country' => null,
            'phone' => null,
            'user_id' => null,
            'purchase_count' => 0,
            'total_spent' => 0.00,
            'last_purchase_date' => null,
            'membership_expires_at' => null,
            'auto_renewal' => 0,
            'max_uses' => 1,
            'current_uses' => 0,
            'used_count' => 0, // For auto-generated codes tracking
            'expires_at' => null,
            'last_warning_sent' => null,
            'status' => 'active',
            'created_at' => null,
            'updated_at' => null
        );

        $data = wp_parse_args($data, $defaults);
        
        // Set timestamps if not provided
        if (empty($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        if (empty($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        $result = $this->wpdb->insert(
            $this->table_codes,
            $data,
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%d', '%d', 
                '%d', '%d', '%s', '%s', '%s', '%s', '%s'
            )
        );

        if ($result === false) {
            return new WP_Error('db_insert_error', __('Failed to insert VIP code', 'woocommerce-vip-paketi'), $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    public function get_code($code) {
        error_log('WVP DB: Looking for code: ' . $code . ' in table: ' . $this->table_codes);
        
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_codes} WHERE code = %s",
                $code
            )
        );
        
        error_log('WVP DB: Query result: ' . ($result ? 'Found' : 'Not found'));
        if ($result) {
            error_log('WVP DB: Code details: ' . print_r($result, true));
        }
        
        return $result;
    }

    /**
     * Alias for get_code() to support auto-generated code functionality
     */
    public function get_code_by_code($code) {
        return $this->get_code($code);
    }

    public function get_code_by_id($id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_codes} WHERE id = %d",
                $id
            )
        );

        return $result;
    }

    public function update_code($id, $data) {
        // Always set updated_at timestamp
        $data['updated_at'] = current_time('mysql');
        
        // Build format array dynamically based on the data fields being updated
        $format = array();
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'user_id':
                case 'purchase_count':
                case 'current_uses':
                case 'used_count':
                case 'max_uses':
                case 'auto_renewal':
                    $format[] = '%d';
                    break;
                case 'total_spent':
                    $format[] = '%f';
                    break;
                default:
                    $format[] = '%s';
                    break;
            }
        }
        
        $result = $this->wpdb->update(
            $this->table_codes,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_update_error', __('Failed to update VIP code', 'woocommerce-vip-paketi'), $this->wpdb->last_error);
        }

        return $result;
    }

    public function delete_code($id) {
        $result = $this->wpdb->delete(
            $this->table_codes,
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_delete_error', __('Failed to delete VIP code', 'woocommerce-vip-paketi'), $this->wpdb->last_error);
        }

        return $result;
    }

    public function get_codes($args = array()) {
        $defaults = array(
            'status' => null,
            'email' => null,
            'domain' => null,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_conditions = array();
        $where_values = array();

        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['email'])) {
            $where_conditions[] = 'email = %s';
            $where_values[] = $args['email'];
        }

        if (!empty($args['domain'])) {
            $where_conditions[] = 'domain = %s';
            $where_values[] = $args['domain'];
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $order_clause = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'
        );

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = sprintf('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $query = "SELECT * FROM {$this->table_codes} {$where_clause} {$order_clause} {$limit_clause}";

        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        return $this->wpdb->get_results($query);
    }

    public function count_codes($args = array()) {
        $where_conditions = array();
        $where_values = array();

        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if (!empty($args['email'])) {
            $where_conditions[] = 'email = %s';
            $where_values[] = $args['email'];
        }

        if (!empty($args['domain'])) {
            $where_conditions[] = 'domain = %s';
            $where_values[] = $args['domain'];
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $query = "SELECT COUNT(*) FROM {$this->table_codes} {$where_clause}";

        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        return (int) $this->wpdb->get_var($query);
    }

    public function validate_code($code, $email = null) {
        $code_data = $this->get_code($code);

        if (!$code_data) {
            return new WP_Error('invalid_code', __('VIP code not found', 'woocommerce-vip-paketi'));
        }

        if ($code_data->status !== 'active') {
            return new WP_Error('inactive_code', __('VIP code is not active', 'woocommerce-vip-paketi'));
        }

        if ($code_data->expires_at && strtotime($code_data->expires_at) < current_time('timestamp')) {
            $this->update_code($code_data->id, array('status' => 'expired'));
            return new WP_Error('expired_code', __('VIP code has expired', 'woocommerce-vip-paketi'));
        }

        if ($code_data->current_uses >= $code_data->max_uses) {
            $this->update_code($code_data->id, array('status' => 'used'));
            return new WP_Error('used_code', __('VIP code has reached maximum uses', 'woocommerce-vip-paketi'));
        }

        if ($code_data->email && $email && $code_data->email !== $email) {
            return new WP_Error('email_mismatch', __('VIP code is assigned to a different email', 'woocommerce-vip-paketi'));
        }

        return $code_data;
    }

    public function use_code($code_id) {
        $code_data = $this->get_code_by_id($code_id);
        
        if (!$code_data) {
            return new WP_Error('invalid_code_id', __('VIP code not found', 'woocommerce-vip-paketi'));
        }

        $new_uses = $code_data->current_uses + 1;
        $new_status = $new_uses >= $code_data->max_uses ? 'used' : 'active';

        $result = $this->update_code($code_id, array(
            'current_uses' => $new_uses,
            'status' => $new_status
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        do_action('wvp_code_used', $code_id, $code_data);

        return true;
    }

    public function bulk_insert_codes($codes_data) {
        $inserted = 0;
        $errors = array();

        foreach ($codes_data as $code_data) {
            $result = $this->insert_code($code_data);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf(__('Failed to insert code %s: %s', 'woocommerce-vip-paketi'), $code_data['code'], $result->get_error_message());
            } else {
                $inserted++;
            }
        }

        return array(
            'inserted' => $inserted,
            'errors' => $errors
        );
    }

    public function cleanup_expired_codes() {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_codes} SET status = 'expired' WHERE expires_at < %s AND status = 'active'",
                current_time('mysql')
            )
        );

        return $result;
    }

    public function get_statistics() {
        $stats = array();

        $stats['total'] = $this->count_codes();
        $stats['active'] = $this->count_codes(array('status' => 'active'));
        $stats['used'] = $this->count_codes(array('status' => 'used'));
        $stats['expired'] = $this->count_codes(array('status' => 'expired'));
        $stats['inactive'] = $this->count_codes(array('status' => 'inactive'));

        $stats['usage_rate'] = $stats['total'] > 0 ? round(($stats['used'] / $stats['total']) * 100, 2) : 0;

        return $stats;
    }

    public function populate_code_from_billing($code_id, $billing_data, $force_update = false) {
        $update_data = array();

        // Map billing fields to VIP code fields
        $field_mapping = array(
            'billing_first_name' => 'first_name',
            'billing_last_name' => 'last_name',
            'billing_company' => 'company',
            'billing_address_1' => 'address_1',
            'billing_address_2' => 'address_2',
            'billing_city' => 'city',
            'billing_state' => 'state',
            'billing_postcode' => 'postcode',
            'billing_country' => 'country',
            'billing_phone' => 'phone',
            'billing_email' => 'email'
        );

        // Get current code data to check which fields are empty
        $current_code = $this->get_code_by_id($code_id);
        if (!$current_code) {
            return new WP_Error('invalid_code_id', __('VIP code not found', 'woocommerce-vip-paketi'));
        }

        // Update fields based on force_update flag
        foreach ($field_mapping as $billing_field => $code_field) {
            if (isset($billing_data[$billing_field]) && !empty($billing_data[$billing_field])) {
                // If force_update is true, update regardless of current value
                // Otherwise, only update if current field is empty
                if ($force_update || empty($current_code->$code_field)) {
                    $update_data[$code_field] = sanitize_text_field($billing_data[$billing_field]);
                }
            }
        }

        // If we have data to update, perform the update
        if (!empty($update_data)) {
            return $this->update_code($code_id, $update_data);
        }

        return true;
    }

    public function link_code_to_user($code_id, $user_id) {
        return $this->update_code($code_id, array('user_id' => $user_id));
    }

    public function update_membership_data($code_id, $purchase_data) {
        $code = $this->get_code_by_id($code_id);
        if (!$code) {
            return new WP_Error('invalid_code_id', __('VIP code not found', 'woocommerce-vip-paketi'));
        }

        $update_data = array(
            'purchase_count' => $code->purchase_count + 1,
            'total_spent' => $code->total_spent + floatval($purchase_data['amount']),
            'last_purchase_date' => current_time('mysql')
        );

        // Set membership expiry if not set or extend existing
        if (isset($purchase_data['membership_duration_days'])) {
            $current_expiry = $code->membership_expires_at ? $code->membership_expires_at : current_time('mysql');
            $new_expiry = date('Y-m-d H:i:s', strtotime($current_expiry . ' + ' . $purchase_data['membership_duration_days'] . ' days'));
            $update_data['membership_expires_at'] = $new_expiry;
        }

        return $this->update_code($code_id, $update_data);
    }

    public function get_codes_by_user($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_codes} WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
    }

    public function get_expiring_memberships($days_ahead = 7) {
        $date_limit = date('Y-m-d H:i:s', strtotime('+' . $days_ahead . ' days'));
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_codes} 
                WHERE membership_expires_at IS NOT NULL 
                AND membership_expires_at <= %s 
                AND status = 'active' 
                ORDER BY membership_expires_at ASC",
                $date_limit
            )
        );
    }
}