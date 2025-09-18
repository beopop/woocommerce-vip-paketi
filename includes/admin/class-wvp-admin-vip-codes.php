<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Admin_VIP_Codes {

    private $db;

    public function __construct() {
        $this->db = WVP_Database::get_instance();
    }

    public function ajax_add_code() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-vip-paketi'));
        }

        $code_data = array(
            'code' => sanitize_text_field($_POST['code']),
            'email' => sanitize_email($_POST['email']),
            'domain' => sanitize_text_field($_POST['domain']),
            'max_uses' => absint($_POST['max_uses']),
            'expires_at' => !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null,
            'status' => sanitize_text_field($_POST['status'])
        );

        // Add new fields if provided
        $new_fields = array(
            'first_name', 'last_name', 'company', 'phone',
            'address_1', 'address_2', 'city', 'state', 'postcode', 'country',
            'membership_expires_at', 'auto_renewal', 'purchase_count', 'total_spent'
        );

        foreach ($new_fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'auto_renewal') {
                    $code_data[$field] = isset($_POST[$field]) ? 1 : 0;
                } elseif ($field === 'purchase_count') {
                    $code_data[$field] = absint($_POST[$field]);
                } elseif ($field === 'total_spent') {
                    $code_data[$field] = floatval($_POST[$field]);
                } elseif ($field === 'membership_expires_at') {
                    $code_data[$field] = !empty($_POST[$field]) ? sanitize_text_field($_POST[$field]) : null;
                } else {
                    $code_data[$field] = sanitize_text_field($_POST[$field]);
                }
            }
        }

        if (empty($code_data['code'])) {
            wp_send_json_error(__('Code is required.', 'woocommerce-vip-paketi'));
        }

        if ($code_data['max_uses'] < 1) {
            $code_data['max_uses'] = 1;
        }

        if ($this->db->get_code($code_data['code'])) {
            wp_send_json_error(__('Code already exists.', 'woocommerce-vip-paketi'));
        }

        $result = $this->db->insert_code($code_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('VIP code added successfully.', 'woocommerce-vip-paketi'),
            'code_id' => $result
        ));
    }

    public function ajax_edit_code() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-vip-paketi'));
        }

        $code_id = absint($_POST['code_id']);
        $code_data = array(
            'code' => sanitize_text_field($_POST['code']),
            'email' => sanitize_email($_POST['email']),
            'domain' => sanitize_text_field($_POST['domain']),
            'max_uses' => absint($_POST['max_uses']),
            'expires_at' => !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null,
            'status' => sanitize_text_field($_POST['status'])
        );

        if (empty($code_data['code'])) {
            wp_send_json_error(__('Code is required.', 'woocommerce-vip-paketi'));
        }

        if ($code_data['max_uses'] < 1) {
            $code_data['max_uses'] = 1;
        }

        $existing_code = $this->db->get_code($code_data['code']);
        if ($existing_code && $existing_code->id != $code_id) {
            wp_send_json_error(__('Code already exists.', 'woocommerce-vip-paketi'));
        }

        $result = $this->db->update_code($code_id, $code_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('VIP code updated successfully.', 'woocommerce-vip-paketi')
        ));
    }


    public function ajax_bulk_import() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-vip-paketi'));
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Please select a valid CSV file.', 'woocommerce-vip-paketi'));
        }

        $file_path = $_FILES['csv_file']['tmp_name'];
        $csv_data = $this->parse_csv_file($file_path);

        if (empty($csv_data)) {
            wp_send_json_error(__('CSV file is empty or invalid.', 'woocommerce-vip-paketi'));
        }

        $result = $this->db->bulk_insert_codes($csv_data);

        wp_send_json_success(array(
            'message' => sprintf(
                __('Bulk import completed. %d codes imported successfully.', 'woocommerce-vip-paketi'),
                $result['inserted']
            ),
            'inserted' => $result['inserted'],
            'errors' => $result['errors']
        ));
    }

    public function parse_csv_file($file_path) {
        $csv_data = array();
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header = fgetcsv($handle);
            
            if (!$header || !in_array('code', $header)) {
                fclose($handle);
                return false;
            }

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) !== count($header)) {
                    continue;
                }

                $data = array_combine($header, $row);
                
                $code_data = array(
                    'code' => sanitize_text_field($data['code']),
                    'email' => isset($data['email']) ? sanitize_email($data['email']) : null,
                    'domain' => isset($data['domain']) ? sanitize_text_field($data['domain']) : null,
                    'first_name' => isset($data['first_name']) ? sanitize_text_field($data['first_name']) : null,
                    'last_name' => isset($data['last_name']) ? sanitize_text_field($data['last_name']) : null,
                    'company' => isset($data['company']) ? sanitize_text_field($data['company']) : null,
                    'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : null,
                    'address_1' => isset($data['address_1']) ? sanitize_text_field($data['address_1']) : null,
                    'address_2' => isset($data['address_2']) ? sanitize_text_field($data['address_2']) : null,
                    'city' => isset($data['city']) ? sanitize_text_field($data['city']) : null,
                    'state' => isset($data['state']) ? sanitize_text_field($data['state']) : null,
                    'postcode' => isset($data['postcode']) ? sanitize_text_field($data['postcode']) : null,
                    'country' => isset($data['country']) ? sanitize_text_field($data['country']) : null,
                    'max_uses' => isset($data['max_uses']) ? absint($data['max_uses']) : 1,
                    'membership_expires_at' => isset($data['membership_expires_at']) && !empty($data['membership_expires_at']) ? sanitize_text_field($data['membership_expires_at']) : null,
                    'expires_at' => isset($data['expires_at']) && !empty($data['expires_at']) ? sanitize_text_field($data['expires_at']) : null,
                    'auto_renewal' => isset($data['auto_renewal']) ? absint($data['auto_renewal']) : 0,
                    'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
                );

                if (!empty($code_data['code'])) {
                    $csv_data[] = $code_data;
                }
            }
            
            fclose($handle);
        }

        return $csv_data;
    }

    public function export_codes_csv() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-vip-paketi'));
        }

        $codes = $this->db->get_codes(array('limit' => 0));

        if (empty($codes)) {
            wp_redirect(admin_url('admin.php?page=wvp-vip-codes&message=no_codes'));
            exit;
        }

        $filename = 'wvp-codes-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('code', 'email', 'domain', 'first_name', 'last_name', 'company', 'phone', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'max_uses', 'current_uses', 'membership_expires_at', 'expires_at', 'auto_renewal', 'status', 'created_at'));

        foreach ($codes as $code) {
            fputcsv($output, array(
                $code->code,
                $code->email,
                $code->domain,
                $code->first_name,
                $code->last_name,
                $code->company,
                $code->phone,
                $code->address_1,
                $code->address_2,
                $code->city,
                $code->state,
                $code->postcode,
                $code->country,
                $code->max_uses,
                $code->current_uses,
                $code->membership_expires_at,
                $code->expires_at,
                $code->auto_renewal,
                $code->status,
                $code->created_at
            ));
        }

        fclose($output);
        exit;
    }

    public function get_codes_list($args = array()) {
        return $this->db->get_codes($args);
    }

    public function get_codes_count($args = array()) {
        return $this->db->count_codes($args);
    }

    public function get_codes_statistics() {
        return $this->db->get_statistics();
    }

    public function ajax_get_code_data() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
        }

        $code_id = absint($_POST['code_id']);
        if (!$code_id) {
            wp_send_json_error(__('Nevaljan ID koda.', 'woocommerce-vip-paketi'));
        }

        $code_data = $this->db->get_code_by_id($code_id);
        if (!$code_data) {
            wp_send_json_error(__('Kod nije pronađen.', 'woocommerce-vip-paketi'));
        }

        wp_send_json_success($code_data);
    }

    public function ajax_update_code() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
        }

        $code_id = absint($_POST['code_id']);
        if (!$code_id) {
            wp_send_json_error(__('Nevaljan ID koda.', 'woocommerce-vip-paketi'));
        }

        // Collect all possible fields
        $update_data = array();
        
        // Basic fields
        if (isset($_POST['code'])) $update_data['code'] = sanitize_text_field($_POST['code']);
        if (isset($_POST['email'])) $update_data['email'] = sanitize_email($_POST['email']);
        if (isset($_POST['domain'])) $update_data['domain'] = sanitize_text_field($_POST['domain']);
        if (isset($_POST['max_uses'])) $update_data['max_uses'] = absint($_POST['max_uses']);
        if (isset($_POST['status'])) $update_data['status'] = sanitize_text_field($_POST['status']);
        
        // Personal info fields
        if (isset($_POST['first_name'])) $update_data['first_name'] = sanitize_text_field($_POST['first_name']);
        if (isset($_POST['last_name'])) $update_data['last_name'] = sanitize_text_field($_POST['last_name']);
        if (isset($_POST['company'])) $update_data['company'] = sanitize_text_field($_POST['company']);
        if (isset($_POST['phone'])) $update_data['phone'] = sanitize_text_field($_POST['phone']);
        
        // Address fields
        if (isset($_POST['address_1'])) $update_data['address_1'] = sanitize_text_field($_POST['address_1']);
        if (isset($_POST['address_2'])) $update_data['address_2'] = sanitize_text_field($_POST['address_2']);
        if (isset($_POST['city'])) $update_data['city'] = sanitize_text_field($_POST['city']);
        if (isset($_POST['state'])) $update_data['state'] = sanitize_text_field($_POST['state']);
        if (isset($_POST['postcode'])) $update_data['postcode'] = sanitize_text_field($_POST['postcode']);
        if (isset($_POST['country'])) $update_data['country'] = sanitize_text_field($_POST['country']);
        
        // Membership fields
        if (isset($_POST['membership_expires_at']) && !empty($_POST['membership_expires_at'])) {
            $update_data['membership_expires_at'] = sanitize_text_field($_POST['membership_expires_at']);
        }
        if (isset($_POST['auto_renewal'])) $update_data['auto_renewal'] = absint($_POST['auto_renewal']);
        if (isset($_POST['purchase_count'])) $update_data['purchase_count'] = absint($_POST['purchase_count']);
        if (isset($_POST['total_spent'])) $update_data['total_spent'] = floatval($_POST['total_spent']);

        // Validate required fields
        if (empty($update_data['code'])) {
            wp_send_json_error(__('Kod je obavezno polje.', 'woocommerce-vip-paketi'));
        }

        // Check if code already exists (but not for the current record)
        $existing_code = $this->db->get_code($update_data['code']);
        if ($existing_code && $existing_code->id != $code_id) {
            wp_send_json_error(__('Kod već postoji.', 'woocommerce-vip-paketi'));
        }

        $result = $this->db->update_code($code_id, $update_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('VIP kod je uspešno ažuriran.', 'woocommerce-vip-paketi'),
            'code_id' => $code_id
        ));
    }

    public function ajax_delete_code() {
        check_ajax_referer('wvp_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
        }

        $code_id = absint($_POST['code_id']);
        if (!$code_id) {
            wp_send_json_error(__('Nevaljan ID koda.', 'woocommerce-vip-paketi'));
        }

        $result = $this->db->delete_code($code_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('VIP kod je uspešno obrisan.', 'woocommerce-vip-paketi'),
            'code_id' => $code_id
        ));
    }
}