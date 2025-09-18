<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Checkout {

    private $db;

    public function __construct() {
        $this->db = WVP_Database::get_instance();
    }

    public function add_vip_code_field() {
        if (get_option('wvp_enable_checkout_codes') !== 'yes') {
            return;
        }

        if ($this->is_user_vip()) {
            // If user is already VIP, show VIP status instead of code field
            $this->show_vip_status();
            return;
        }

        ?>
        <div id="wvp-checkout-vip-section" class="wvp-checkout-section">
            <h3>
                <?php _e('Already a VIP member?', 'woocommerce-vip-paketi'); ?>
                <span class="wvp-tooltip" title="<?php esc_attr_e('Enter your VIP code to access special pricing', 'woocommerce-vip-paketi'); ?>">
                    <span class="dashicons dashicons-info"></span>
                </span>
            </h3>
            
            <div class="wvp-code-input-wrapper">
                <p class="form-row form-row-wide">
                    <input type="text" 
                           id="wvp_code" 
                           name="wvp_code" 
                           placeholder="<?php esc_attr_e('Enter VIP code', 'woocommerce-vip-paketi'); ?>" 
                           class="input-text" />
                    <button type="button" 
                            id="wvp_verify_code" 
                            class="button wvp-verify-button">
                        <?php _e('Verify Code', 'woocommerce-vip-paketi'); ?>
                    </button>
                </p>
            </div>
            
            <div id="wvp_code_messages" class="wvp-messages"></div>
        </div>

        <style>
        .wvp-checkout-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .wvp-code-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wvp-code-input-wrapper .form-row {
            margin: 0;
            flex: 1;
            display: flex;
            gap: 10px;
        }
        
        .wvp-code-input-wrapper input {
            flex: 1;
        }
        
        .wvp-verify-button {
            white-space: nowrap;
        }
        
        .wvp-messages {
            margin-top: 10px;
        }
        
        .wvp-message {
            padding: 10px 15px;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .wvp-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .wvp-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f1b0b7;
        }
        
        .wvp-message.loading {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .wvp-tooltip {
            cursor: help;
            color: #666;
        }
        
        .wvp-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Modal styles */
        .wvp-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wvp-modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .wvp-modal-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wvp-modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .wvp-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wvp-modal-close:hover {
            color: #333;
        }
        
        .wvp-modal-body {
            padding: 20px;
        }
        
        .wvp-confirmation-option {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .wvp-confirmation-option h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 16px;
        }
        
        .wvp-confirmation-option p {
            margin: 0 0 15px 0;
            color: #666;
        }
        
        .wvp-confirmation-option .form-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .wvp-confirmation-option input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .wvp-confirmation-option button {
            padding: 8px 16px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .wvp-confirmation-option button:hover {
            background: #005a87;
        }
        
        .wvp-modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .wvp-modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .wvp-modal-buttons .button {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #ced4da;
        }
        
        .wvp-modal-buttons .button:hover {
            background: #e9ecef;
        }
        
        .wvp-modal-buttons .button-primary {
            background: #007cba;
            color: white;
        }
        
        .wvp-modal-buttons .button-primary:hover {
            background: #005a87;
        }
        
        /* Data preview styles */
        .wvp-data-preview {
            background: #f1f3f4;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .wvp-data-preview h4 {
            margin: 0 0 12px 0;
            color: #1d2327;
            font-size: 16px;
        }
        
        .wvp-data-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .wvp-data-list li {
            padding: 4px 0;
            color: #3c434a;
            font-size: 14px;
        }
        
        .wvp-data-list li strong {
            color: #1d2327;
            min-width: 100px;
            display: inline-block;
        }
        
        /* Verification section */
        .wvp-verification-section {
            border-top: 1px solid #dcdcde;
            padding-top: 20px;
        }
        
        .wvp-verification-section p {
            margin: 0 0 15px 0;
            color: #3c434a;
            line-height: 1.5;
        }
        
        .wvp-input-group {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 15px;
        }
        
        .wvp-identity-input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid #dcdcde;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .wvp-identity-input:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
        }
        
        .wvp-confirm-button {
            padding: 10px 18px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: background-color 0.2s;
        }
        
        .wvp-confirm-button:hover {
            background: #135e96;
        }
        
        @media (max-width: 600px) {
            .wvp-modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .wvp-modal-header, .wvp-modal-body {
                padding: 15px;
            }
            
            .wvp-confirmation-option .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .wvp-confirmation-option input {
                width: 100%;
            }
            
            .wvp-modal-buttons {
                flex-direction: column;
            }
            
            .wvp-input-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .wvp-identity-input {
                width: 100%;
            }
            
            .wvp-confirm-button {
                width: 100%;
            }
        }
        </style>
        <?php
    }

    public function ajax_verify_code() {
        error_log('WVP Debug: ajax_verify_code called');
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wvp_public_nonce') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'woocommerce-process_checkout')) {
            error_log('WVP Debug: Nonce verification failed');
            wp_die('Invalid nonce');
        }

        $code = sanitize_text_field($_POST['code']);
        $billing_data = isset($_POST['billing_data']) ? $_POST['billing_data'] : array();
        $email = is_user_logged_in() ? wp_get_current_user()->user_email : (isset($billing_data['billing_email']) ? $billing_data['billing_email'] : '');

        error_log('WVP Debug: Code to verify: ' . $code);


        // Skip billing validation to allow VIP code verification without billing data
        // $billing_validation = $this->validate_billing_data($billing_data);
        // if (is_wp_error($billing_validation)) {
        //     wp_send_json_error(array(
        //         'message' => $billing_validation->get_error_message(),
        //         'type' => 'billing_validation'
        //     ));
        // }

        if (empty($code)) {
            wp_send_json_error(array(
                'message' => __('Molimo unesite VIP kod', 'woocommerce-vip-paketi')
            ));
        }

        // First check if code exists and get its current status
        $code_data_check = $this->db->get_code($code);
        
        if (!$code_data_check && strpos($code, '-') !== false) {
            $code_without_dashes = str_replace('-', '', $code);
            $code_data_check = $this->db->get_code($code_without_dashes);
            if ($code_data_check) {
                $code = $code_without_dashes; // Use the found version
            }
        }

        if (!$code_data_check) {
            error_log('WVP Debug: Code not found: ' . $code);
            wp_send_json_error(array(
                'message' => __('VIP kod nije pronađen', 'woocommerce-vip-paketi')
            ));
        }


        // Always show confirmation popup for any valid VIP code
        if ($code_data_check) {
            error_log('WVP Debug: Code found, showing popup for code: ' . $code_data_check->code);
            wp_send_json_success(array(
                'used_code' => true,
                'code_data' => array(
                    'id' => $code_data_check->id,
                    'code' => $code_data_check->code,
                    'email' => $code_data_check->email,
                    'first_name' => $code_data_check->first_name,
                    'last_name' => $code_data_check->last_name,
                    'phone' => $code_data_check->phone,
                    'company' => $code_data_check->company,
                    'address_1' => $code_data_check->address_1,
                    'address_2' => $code_data_check->address_2,
                    'city' => $code_data_check->city,
                    'state' => $code_data_check->state,
                    'postcode' => $code_data_check->postcode,
                    'country' => $code_data_check->country,
                    'user_id' => $code_data_check->user_id
                ),
                'message' => __('VIP kod je pronađen sa podacima za naplatu. Molimo potvrdite vašu email adresu ili telefon da automatski popunimo formu.', 'woocommerce-vip-paketi')
            ));
        }

        // Try original code first, then without dashes for compatibility
        $validation_result = $this->db->validate_code($code, $email);
        
        if (is_wp_error($validation_result)) {
            // If original code fails and has dashes, try without dashes
            if (strpos($code, '-') !== false) {
                $code_without_dashes = str_replace('-', '', $code);
                $validation_result = $this->db->validate_code($code_without_dashes, $email);
            }
        }

        if (is_wp_error($validation_result)) {
            wp_send_json_error(array(
                'message' => $validation_result->get_error_message()
            ));
        }

        $code_data = $validation_result;
        $user_id = get_current_user_id();

        if (!$user_id) {
            $user_id = $this->handle_guest_user_with_code($code_data, $billing_data);
            if (is_wp_error($user_id)) {
                // Check if error is due to missing user data
                if ($user_id->get_error_code() === 'email_required') {
                    // Return special response to show registration form
                    wp_send_json_success(array(
                        'needs_registration' => true,
                        'code_data' => array(
                            'id' => $code_data->id,
                            'code' => $code_data->code,
                            'email' => $code_data->email ?: '',
                            'first_name' => $code_data->first_name ?: '',
                            'last_name' => $code_data->last_name ?: '',
                            'phone' => $code_data->phone ?: '',
                            'company' => $code_data->company ?: '',
                            'address_1' => $code_data->address_1 ?: '',
                            'address_2' => $code_data->address_2 ?: '',
                            'city' => $code_data->city ?: '',
                            'state' => $code_data->state ?: '',
                            'postcode' => $code_data->postcode ?: '',
                            'country' => $code_data->country ?: 'RS'
                        ),
                        'message' => __('Potrebno je da unesete vaše podatke da biste aktivirali VIP kod.', 'woocommerce-vip-paketi')
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => $user_id->get_error_message()
                    ));
                }
            }
        }

        $result = $this->assign_vip_status_to_user($user_id, $code_data);
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        // Populate VIP code with billing data if available
        if (!empty($billing_data)) {
            $populate_result = $this->db->populate_code_from_billing($code_data->id, $billing_data);
            if (is_wp_error($populate_result)) {
            }
        }

        // Link the code to the user
        $link_result = $this->db->link_code_to_user($code_data->id, $user_id);
        if (is_wp_error($link_result)) {
        }

        $use_result = $this->db->use_code($code_data->id);
        if (is_wp_error($use_result)) {
            wp_send_json_error(array(
                'message' => $use_result->get_error_message()
            ));
        }

        $this->send_vip_activation_email($user_id, $code_data);

        do_action('wvp_vip_status_activated', $user_id, $code_data);

        // Clear session data since VIP code is now verified
        $this->clear_vip_session_data();

        // Set a session flag to trigger pricing update
        WC()->session->set('wvp_vip_pricing_active', true);
        WC()->session->set('wvp_user_just_became_vip', $user_id);
        
        // Update package prices in cart for new VIP status
        try {
            $this->update_package_prices_for_vip_activation();
        } catch (Exception $e) {
            error_log('WVP ERROR: update_package_prices_for_vip_activation failed: ' . $e->getMessage());
        }
        
        // Force VIP pricing recalculation
        if (WC()->cart) {
            // Trigger hooks manually to ensure prices are set
            do_action('woocommerce_before_calculate_totals', WC()->cart);
            
            // Calculate totals
            WC()->cart->calculate_totals();
            
            error_log('WVP DEBUG: Forced cart recalculation after VIP activation - Cart total: ' . WC()->cart->get_total());
        }
        
        // Set flag to refresh checkout after VIP activation
        WC()->session->set('wvp_refresh_checkout_needed', true);

        wp_send_json_success(array(
            'message' => __('VIP code verified successfully! Your VIP pricing is now active.', 'woocommerce-vip-paketi'),
            'redirect' => false,
            'refresh_checkout' => true  // Refresh checkout to show updated VIP prices
        ));
    }

    private function handle_guest_user_with_code($code_data, $billing_data = array()) {
        $auto_registration = get_option('wvp_auto_registration', 'no');
        
        if ($auto_registration !== 'yes') {
            // Enable auto-registration by default for VIP codes
            update_option('wvp_auto_registration', 'yes');
        }

        // Use email from billing data first, then from code data as fallback
        $email = isset($billing_data['billing_email']) && !empty($billing_data['billing_email']) 
                ? $billing_data['billing_email'] 
                : $code_data->email;
                
        if (empty($email)) {
            return new WP_Error('email_required', __('VIP code requires an email address', 'woocommerce-vip-paketi'));
        }

        $existing_user = get_user_by('email', $email);
        
        if ($existing_user) {
            wp_set_current_user($existing_user->ID);
            wp_set_auth_cookie($existing_user->ID, true);
            return $existing_user->ID;
        } else {
            return $this->create_user_from_code($code_data, $billing_data);
        }
    }

    private function create_user_from_code($code_data, $billing_data = array()) {
        // Use email from billing data first, then from code data as fallback
        $email = isset($billing_data['billing_email']) && !empty($billing_data['billing_email']) 
                ? $billing_data['billing_email'] 
                : $code_data->email;
                
        // Generate username from billing data (name) or email
        $username = $this->generate_username_from_billing($billing_data, $email);
        $password = wp_generate_password(12, false);
        

        // Ensure VIP member role exists
        if (!get_role('wvp_vip_member')) {
            $customer_caps = get_role('customer') ? get_role('customer')->capabilities : array();
            add_role('wvp_vip_member', __('VIP Clan', 'woocommerce-vip-paketi'), array_merge($customer_caps, array(
                'wvp_vip_pricing' => true,
                'wvp_vip_packages' => true
            )));
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'wvp_vip_member'  // Set as VIP member instead of customer
        );
        
        // Add first/last name from billing data if available
        if (isset($billing_data['billing_first_name']) && !empty($billing_data['billing_first_name'])) {
            $user_data['first_name'] = sanitize_text_field($billing_data['billing_first_name']);
        }
        if (isset($billing_data['billing_last_name']) && !empty($billing_data['billing_last_name'])) {
            $user_data['last_name'] = sanitize_text_field($billing_data['billing_last_name']);
            $user_data['display_name'] = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
        }

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Populate user billing/shipping meta from VIP code data and billing data
        $this->populate_user_meta_from_vip_code($user_id, $code_data);
        $this->populate_user_meta_from_billing_data($user_id, $billing_data);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        $this->send_new_account_email($user_id, $password);

        return $user_id;
    }

    private function generate_username_from_billing($billing_data, $email) {
        $username = '';
        
        // Try to generate username from first name and last name
        if (isset($billing_data['billing_first_name']) && isset($billing_data['billing_last_name']) && 
            !empty($billing_data['billing_first_name']) && !empty($billing_data['billing_last_name'])) {
            
            $first_name = sanitize_user($billing_data['billing_first_name'], true);
            $last_name = sanitize_user($billing_data['billing_last_name'], true);
            $username = strtolower($first_name . '.' . $last_name);
        }
        // Try to generate username from first name only
        else if (isset($billing_data['billing_first_name']) && !empty($billing_data['billing_first_name'])) {
            $first_name = sanitize_user($billing_data['billing_first_name'], true);
            $username = strtolower($first_name);
        }
        
        // Fallback to email-based username
        if (empty($username) && !empty($email)) {
            $username = $this->generate_username_from_email($email);
            return $username; // Early return to avoid duplicate checking
        }
        
        // Ensure username is not empty
        if (empty($username)) {
            $username = 'vip_user_' . wp_generate_password(6, false, false);
        }
        
        // Handle duplicate usernames
        if (username_exists($username)) {
            $counter = 1;
            while (username_exists($username . $counter)) {
                $counter++;
            }
            $username = $username . $counter;
        }
        
        return $username;
    }

    private function generate_username_from_email($email) {
        $username = sanitize_user(substr($email, 0, strpos($email, '@')));
        
        if (username_exists($username)) {
            $counter = 1;
            while (username_exists($username . $counter)) {
                $counter++;
            }
            $username = $username . $counter;
        }

        return $username;
    }

    private function assign_vip_status_to_user($user_id, $code_data) {
        $user = new WP_User($user_id);
        
        
        if (!in_array('wvp_vip_member', $user->roles)) {
            $user->add_role('wvp_vip_member');
            
            // Clear user cache to ensure role change is reflected immediately
            wp_cache_delete($user_id, 'users');
            wp_cache_delete($user_id, 'user_meta');
            clean_user_cache($user_id);
            
        } else {
        }

        update_user_meta($user_id, '_wvp_vip_code_used', $code_data->code);
        update_user_meta($user_id, '_wvp_vip_activation_date', current_time('mysql'));
        
        if ($code_data->expires_at) {
            update_user_meta($user_id, '_wvp_vip_expiry_date', $code_data->expires_at);
        }

        $active_codes = get_user_meta($user_id, '_wvp_active_vip_codes', true) ?: array();
        $active_codes[] = $code_data->id;
        update_user_meta($user_id, '_wvp_active_vip_codes', $active_codes);

        return true;
    }

    public function update_order_review($post_data) {
        parse_str($post_data, $form_data);
        
        if (isset($form_data['wvp_code']) && !empty($form_data['wvp_code'])) {
            WC()->session->set('wvp_pending_code', $form_data['wvp_code']);
        }
    }

    private function send_new_account_email($user_id, $password) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $user_id);
        $login_url = wp_login_url();

        $subject = sprintf(__('[%s] Your VIP account has been created', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello,

Your VIP account has been automatically created using your VIP code.

Login Details:
Username: %s
Password: %s

You can login at: %s

Welcome to our VIP program!

Best regards,
%s', 'woocommerce-vip-paketi'),
            $user->user_login,
            $password,
            $login_url,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    private function send_vip_activation_email($user_id, $code_data) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $user_id);

        $subject = sprintf(__('[%s] VIP Status Activated', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Čestitamo! Vaš VIP status je aktiviran sa kodom: %s

Sada možete da uživate u:
- Posebnim VIP cenama na dostupnim proizvodima
- Pristupu ekskluzivnim VIP paketima
- Prioritetnoj korisničkoj podršci

Vaše VIP pogodnosti su sada aktivne u celoj prodavnici.

Srdačan pozdrav,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            $code_data->code,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    private function is_user_vip() {
        $core = new WVP_Core();
        return $core->is_user_vip();
    }

    public function maybe_apply_vip_pricing_on_checkout() {
        if (!$this->is_user_vip()) {
            return;
        }

        $pricing = new WVP_Pricing();
        $pricing->apply_cart_vip_pricing();
    }

    public function add_vip_info_to_order($order_id) {
        $pricing = new WVP_Pricing();
        $pricing->save_vip_pricing_to_order($order_id);
    }

    public function display_vip_notice_after_code_verification() {
        if (WC()->session->get('wvp_code_just_verified')) {
            echo '<div class="woocommerce-message">';
            echo __('VIP pricing has been applied to your cart!', 'woocommerce-vip-paketi');
            echo '</div>';
            
            WC()->session->__unset('wvp_code_just_verified');
        }
    }

    public function validate_checkout_vip_code() {
        $pending_code = WC()->session->get('wvp_pending_code');
        
        if ($pending_code && !$this->is_user_vip()) {
            wc_add_notice(__('Please verify your VIP code before proceeding with checkout.', 'woocommerce-vip-paketi'), 'error');
        }
    }

    public function clear_vip_session_data() {
        WC()->session->__unset('wvp_pending_code');
        WC()->session->__unset('wvp_code_just_verified');
    }

    private function validate_billing_data($billing_data) {
        $required_fields = array(
            'billing_first_name' => __('First Name', 'woocommerce-vip-paketi'),
            'billing_last_name' => __('Last Name', 'woocommerce-vip-paketi'),
            'billing_email' => __('Email', 'woocommerce-vip-paketi'),
            'billing_phone' => __('Phone', 'woocommerce-vip-paketi'),
            'billing_address_1' => __('Address', 'woocommerce-vip-paketi'),
            'billing_city' => __('City', 'woocommerce-vip-paketi'),
            'billing_postcode' => __('Postal Code', 'woocommerce-vip-paketi'),
            'billing_country' => __('Country', 'woocommerce-vip-paketi')
        );

        $missing_fields = array();

        foreach ($required_fields as $field => $label) {
            if (empty($billing_data[$field])) {
                $missing_fields[] = $label;
            }
        }

        if (!empty($missing_fields)) {
            return new WP_Error(
                'missing_billing_fields', 
                sprintf(
                    __('Please fill in the following billing fields before entering VIP code: %s', 'woocommerce-vip-paketi'),
                    implode(', ', $missing_fields)
                )
            );
        }

        // Validate email format
        if (!is_email($billing_data['billing_email'])) {
            return new WP_Error('invalid_email', __('Please enter a valid email address', 'woocommerce-vip-paketi'));
        }

        return true;
    }

    public function check_membership_expiry($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $codes = $this->db->get_codes_by_user($user_id);
        $has_active_membership = false;

        foreach ($codes as $code) {
            if ($code->membership_expires_at && strtotime($code->membership_expires_at) > current_time('timestamp')) {
                $has_active_membership = true;
                break;
            }
        }

        if (!$has_active_membership) {
            // Remove VIP role if no active membership
            $user = new WP_User($user_id);
            $user->remove_role('wvp_vip_member');
            
            // Send expiry notification
            $this->send_membership_expiry_email($user_id);
        }

        return $has_active_membership;
    }

    private function send_membership_expiry_email($user_id) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $subject = sprintf(__('[%s] VIP Membership Expired', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Vaše VIP članstvo je isteklo. Da biste nastavili da uživate u VIP pogodnostima:

- Posebne cene na proizvode
- Pristup ekskluzivnim VIP paketima
- Prioritetna korisnička podrška

Molimo obnovite svoje članstvo ili nas kontaktirajte za pomoć.

Srdačan pozdrav,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    public function process_membership_renewal($user_id, $purchase_data) {
        $codes = $this->db->get_codes_by_user($user_id);
        
        if (empty($codes)) {
            return new WP_Error('no_codes', __('No VIP codes found for user', 'woocommerce-vip-paketi'));
        }

        // Update the most recent code with purchase data
        $latest_code = $codes[0];
        $result = $this->db->update_membership_data($latest_code->id, $purchase_data);

        if (!is_wp_error($result)) {
            // Restore VIP role
            $user = new WP_User($user_id);
            if (!in_array('wvp_vip_member', $user->roles)) {
                $user->add_role('wvp_vip_member');
            }

            $this->send_membership_renewal_email($user_id, $latest_code);
        }

        return $result;
    }

    private function send_membership_renewal_email($user_id, $code_data) {
        if (get_option('wvp_email_notifications') !== 'yes') {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $expiry_date = $code_data->membership_expires_at ? date('F j, Y', strtotime($code_data->membership_expires_at)) : 'N/A';

        $subject = sprintf(__('[%s] VIP Membership Renewed', 'woocommerce-vip-paketi'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Odlične vesti! Vaše VIP članstvo je obnovljeno.

Vaše VIP pogodnosti su sada aktivne do: %s

Možete nastaviti da uživate u:
- Posebnim VIP cenama na dostupnim proizvodima
- Pristupu ekskluzivnim VIP paketima
- Prioritetnoj korisničkoj podršci

Hvala vam na produženom članstvu!

Srdačan pozdrav,
%s', 'woocommerce-vip-paketi'),
            $user->display_name,
            $expiry_date,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Show VIP status section for already logged in VIP users
     */
    private function show_vip_status() {
        $current_user = wp_get_current_user();
        $vip_label = get_option('wvp_vip_price_label', __('VIP Cena', 'woocommerce-vip-paketi'));
        
        ?>
        <div id="wvp-checkout-vip-status" class="wvp-checkout-section wvp-vip-active">
            <h3>
                <span class="wvp-vip-badge">VIP</span>
                Aktivni VIP clan
                <span class="dashicons dashicons-yes-alt" style="color: #28a745; margin-left: 8px;"></span>
            </h3>
            
            <div class="wvp-vip-benefits">
                <p class="wvp-vip-message">
                    <strong>Dobrodosli <?php echo esc_html($current_user->display_name); ?>!</strong>
                    Vase VIP cene su automatski primenjene na sve proizvode u narudzbini.
                </p>
                
                <?php
                // Show VIP savings if available
                $cart_total_savings = $this->calculate_current_vip_savings();
                if ($cart_total_savings > 0) {
                    ?>
                    <div class="wvp-savings-summary">
                        <span class="wvp-savings-badge">
                            Stedite <?php echo wc_price($cart_total_savings); ?> sa VIP cenama!
                        </span>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        
        <style>
        .wvp-vip-active {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
            border: 2px solid #28a745;
            border-left: 5px solid #28a745;
        }
        
        .wvp-vip-active h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        
        .wvp-vip-benefits {
            margin-top: 10px;
        }
        
        .wvp-savings-summary {
            margin-top: 10px;
            text-align: center;
        }
        
        .wvp-savings-badge {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            display: inline-block;
        }
        </style>
        <?php
    }

    /**
     * Calculate current VIP savings in cart
     */
    private function calculate_current_vip_savings() {
        if (!WC()->cart) {
            return 0;
        }
        
        $total_savings = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            // Check if this is a VIP package
            if (isset($cart_item['wvp_is_package']) && $cart_item['wvp_is_package'] && isset($cart_item['wvp_is_vip_user']) && $cart_item['wvp_is_vip_user']) {
                // For packages, calculate savings from original subtotal to final price
                $package_subtotal = isset($cart_item['wvp_package_subtotal']) ? floatval($cart_item['wvp_package_subtotal']) : 0;
                $package_total = isset($cart_item['wvp_package_total']) ? floatval($cart_item['wvp_package_total']) : 0;
                
                if ($package_subtotal > $package_total) {
                    $package_savings = $package_subtotal - $package_total;
                    $total_savings += $package_savings;
                    
                    error_log("WVP VIP Savings - Package: {$package_savings} (Subtotal: {$package_subtotal}, Total: {$package_total})");
                }
            } else {
                // Regular product VIP pricing
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                
                // Check if product has VIP pricing
                if (get_post_meta($product->get_id(), '_wvp_enable_vip_pricing', true) === 'yes') {
                    // Use current price (sale price if available, otherwise regular price)
                    $current_price = $product->get_sale_price();
                    if (!$current_price) {
                        $current_price = $product->get_regular_price();
                    }
                    $current_price = (float) $current_price;
                    
                    $vip_price = (float) get_post_meta($product->get_id(), '_wvp_vip_price', true);
                    
                    if ($current_price > 0 && $vip_price > 0 && $vip_price < $current_price) {
                        $savings_per_item = $current_price - $vip_price;
                        $item_savings = $savings_per_item * $quantity;
                        $total_savings += $item_savings;
                        
                        error_log("WVP VIP Savings - Product: {$item_savings} ({$quantity} x {$savings_per_item}) - Current price: {$current_price}, VIP price: {$vip_price}");
                    }
                }
            }
        }
        
        error_log("WVP VIP Savings - Total: {$total_savings}");
        return $total_savings;
    }

    public function ajax_confirm_phone_and_autofill() {
        error_log('WVP DEBUG: ajax_confirm_phone_and_autofill called');
        error_log('WVP DEBUG: POST data: ' . print_r($_POST, true));
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wvp_public_nonce') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'woocommerce-process_checkout')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'woocommerce-vip-paketi')
            ));
        }

        $phone = sanitize_text_field($_POST['phone']);
        $code_id = absint($_POST['code_id']);
        
        error_log('WVP DEBUG: Phone: ' . $phone . ', Code ID: ' . $code_id);


        if (empty($phone) || empty($code_id)) {
            wp_send_json_error(array(
                'message' => __('Neispravni podaci.', 'woocommerce-vip-paketi')
            ));
        }

        // Get code data
        $code_data = $this->db->get_code_by_id($code_id);
        if (!$code_data) {
            wp_send_json_error(array(
                'message' => __('VIP kod nije pronađen.', 'woocommerce-vip-paketi')
            ));
        }

        // Check if phone matches the code (if code has phone)
        if (!empty($code_data->phone) && $this->normalize_phone($code_data->phone) !== $this->normalize_phone($phone)) {
            wp_send_json_error(array(
                'message' => __('Broj telefona se ne slaže sa VIP kodom.', 'woocommerce-vip-paketi')
            ));
        }

        // Use email from code data for user operations
        $email = $code_data->email;
        
        // Check if user already exists by email
        $existing_user = null;
        if (!empty($email)) {
            $existing_user = get_user_by('email', $email);
        }
        
        if ($existing_user) {
            // Login existing user
            wp_set_current_user($existing_user->ID);
            wp_set_auth_cookie($existing_user->ID, true);
            
            // Ensure user has VIP role
            if (!user_can($existing_user->ID, 'wvp_vip_pricing')) {
                $user = new WP_User($existing_user->ID);
                $user->add_role('wvp_vip_member');
                
                // Clear user cache
                clean_user_cache($existing_user->ID);
                wp_cache_delete($existing_user->ID, 'users');
                wp_cache_delete($existing_user->user_login, 'userlogins');
            }
            
        } else if (!empty($email)) {
            // Create new user based on VIP code data
            $user_id = $this->create_user_from_vip_code_data($code_data);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message()
                ));
            }
            
            // Login the new user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
            
        }

        // Set VIP pricing session
        WC()->session->set('wvp_vip_pricing_active', true);
        
        // Update package prices in cart for new VIP status
        $this->update_package_prices_for_vip_activation();
        
        // Force VIP pricing recalculation
        if (WC()->cart) {
            // Trigger hooks manually to ensure prices are set
            do_action('woocommerce_before_calculate_totals', WC()->cart);
            
            // Calculate totals
            WC()->cart->calculate_totals();
            
            error_log('WVP DEBUG: Forced cart recalculation after VIP activation (phone) - Cart total: ' . WC()->cart->get_total());
        }

        // Return data for form autofill
        wp_send_json_success(array(
            'message' => __('Uspešno ste potvrđeni! Forma će biti automatski popunjena.', 'woocommerce-vip-paketi'),
            'autofill_data' => array(
                'billing_first_name' => $code_data->first_name ?: '',
                'billing_last_name' => $code_data->last_name ?: '',
                'billing_email' => $code_data->email ?: '',
                'billing_phone' => $code_data->phone ?: '',
                'billing_company' => $code_data->company ?: '',
                'billing_address_1' => $code_data->address_1 ?: '',
                'billing_address_2' => $code_data->address_2 ?: '',
                'billing_city' => $code_data->city ?: '',
                'billing_state' => $code_data->state ?: '',
                'billing_postcode' => $code_data->postcode ?: '',
                'billing_country' => $code_data->country ?: 'RS'
            ),
            'user_logged_in' => is_user_logged_in(),
            'refresh_checkout' => true
        ));
    }

    public function ajax_confirm_email_and_autofill() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wvp_public_nonce') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'woocommerce-process_checkout')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'woocommerce-vip-paketi')
            ));
        }

        $email = sanitize_email($_POST['email']);
        $code_id = absint($_POST['code_id']);


        if (empty($email) || empty($code_id)) {
            wp_send_json_error(array(
                'message' => __('Neispravni podaci.', 'woocommerce-vip-paketi')
            ));
        }

        // Get code data
        $code_data = $this->db->get_code_by_id($code_id);
        if (!$code_data) {
            wp_send_json_error(array(
                'message' => __('VIP kod nije pronađen.', 'woocommerce-vip-paketi')
            ));
        }

        // Check if email matches the code
        if (strtolower($code_data->email) !== strtolower($email)) {
            wp_send_json_error(array(
                'message' => __('Email adresa se ne slaže sa VIP kodom.', 'woocommerce-vip-paketi')
            ));
        }

        // Check if user already exists
        $existing_user = get_user_by('email', $email);
        
        if ($existing_user) {
            // Login existing user
            wp_set_current_user($existing_user->ID);
            wp_set_auth_cookie($existing_user->ID, true);
            
            // Ensure user has VIP role
            if (!user_can($existing_user->ID, 'wvp_vip_pricing')) {
                $user = new WP_User($existing_user->ID);
                $user->add_role('wvp_vip_member');
                
                // Clear user cache
                clean_user_cache($existing_user->ID);
                wp_cache_delete($existing_user->ID, 'users');
                wp_cache_delete($existing_user->user_login, 'userlogins');
            }
            
        } else {
            // Create new user based on VIP code data
            $user_id = $this->create_user_from_vip_code_data($code_data);
            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message()
                ));
            }
            
            // Login the new user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
            
        }

        // Set VIP pricing session
        WC()->session->set('wvp_vip_pricing_active', true);
        
        // Update package prices in cart for new VIP status
        $this->update_package_prices_for_vip_activation();
        
        // Force VIP pricing recalculation
        if (WC()->cart) {
            // Trigger hooks manually to ensure prices are set
            do_action('woocommerce_before_calculate_totals', WC()->cart);
            
            // Calculate totals
            WC()->cart->calculate_totals();
            
            error_log('WVP DEBUG: Forced cart recalculation after VIP activation (email) - Cart total: ' . WC()->cart->get_total());
        }
        
        // Store autofill data in session for post-reload usage
        $autofill_data = array(
            'billing_first_name' => $code_data->first_name ?: '',
            'billing_last_name' => $code_data->last_name ?: '',
            'billing_email' => $code_data->email ?: '',
            'billing_phone' => $code_data->phone ?: '',
            'billing_company' => $code_data->company ?: '',
            'billing_address_1' => $code_data->address_1 ?: '',
            'billing_address_2' => $code_data->address_2 ?: '',
            'billing_city' => $code_data->city ?: '',
            'billing_state' => $code_data->state ?: '',
            'billing_postcode' => $code_data->postcode ?: '',
            'billing_country' => $code_data->country ?: 'RS'
        );
        WC()->session->set('wvp_autofill_data', $autofill_data);
        
        // Force recalculation of cart prices
        if (WC()->cart) {
            WC()->cart->calculate_totals();
        }

        // Return data for form autofill
        wp_send_json_success(array(
            'message' => __('Uspešno ste ulogovani! Forma će biti automatski popunjena.', 'woocommerce-vip-paketi'),
            'autofill_data' => $autofill_data,
            'user_logged_in' => is_user_logged_in(),
            'refresh_checkout' => true
        ));
    }

    public function ajax_get_session_autofill() {
        // Check if there's autofill data in session
        $autofill_data = WC()->session->get('wvp_autofill_data');
        
        if ($autofill_data) {
            // Clear the session data after retrieving it
            WC()->session->__unset('wvp_autofill_data');
            
            wp_send_json_success(array(
                'autofill_data' => $autofill_data,
                'message' => __('Podaci automatski popunjeni iz VIP koda!', 'woocommerce-vip-paketi')
            ));
        } else {
            wp_send_json_success(array(
                'autofill_data' => null,
                'message' => __('Nema podataka za automatsko popunjavanje.', 'woocommerce-vip-paketi')
            ));
        }
    }

    private function create_user_from_vip_code_data($code_data) {
        $email = $code_data->email;
        $username = $this->generate_username_from_code_data($code_data);
        $password = wp_generate_password(12, false);


        // Ensure VIP member role exists
        if (!get_role('wvp_vip_member')) {
            $customer_caps = get_role('customer') ? get_role('customer')->capabilities : array();
            add_role('wvp_vip_member', __('VIP Član', 'woocommerce-vip-paketi'), array_merge($customer_caps, array(
                'wvp_vip_pricing' => true,
                'wvp_vip_packages' => true
            )));
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $code_data->first_name ?: '',
            'last_name' => $code_data->last_name ?: '',
            'display_name' => trim(($code_data->first_name ?: '') . ' ' . ($code_data->last_name ?: '')) ?: $username,
            'role' => 'wvp_vip_member'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Set comprehensive billing and shipping meta data from VIP code
        $this->populate_user_meta_from_vip_code($user_id, $code_data);

        // Update the VIP code to link it to the new user
        $this->db->link_code_to_user($code_data->id, $user_id);

        // Send welcome email
        $this->send_new_account_email($user_id, $password);

        return $user_id;
    }

    private function generate_username_from_code_data($code_data) {
        $base_username = '';
        
        // Try to create username from first/last name
        if ($code_data->first_name && $code_data->last_name) {
            $base_username = strtolower($code_data->first_name . '.' . $code_data->last_name);
        } elseif ($code_data->first_name) {
            $base_username = strtolower($code_data->first_name);
        } elseif ($code_data->email) {
            // Use email prefix as fallback
            $email_parts = explode('@', $code_data->email);
            $base_username = strtolower($email_parts[0]);
        } else {
            // Last resort: use VIP code
            $base_username = 'vip_' . strtolower($code_data->code);
        }

        // Clean username (remove special characters, replace spaces with dots)
        $base_username = preg_replace('/[^a-z0-9._-]/', '', str_replace(' ', '.', $base_username));
        
        // Ensure username is unique
        $username = $base_username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    private function normalize_phone($phone) {
        // Remove all non-digit characters and normalize phone number
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different formats
        if (strlen($normalized) >= 10) {
            // Take last 9 digits for Serbian numbers (without country code)
            return substr($normalized, -9);
        }
        
        return $normalized;
    }

    public function ajax_register_and_activate() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wvp_public_nonce') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'woocommerce-process_checkout')) {
            wp_die('Invalid nonce');
        }

        $user_data = $_POST['user_data'] ?? array();
        
        if (empty($user_data) || empty($user_data['code_id'])) {
            wp_send_json_error(array(
                'message' => __('Neispravni podaci.', 'woocommerce-vip-paketi')
            ));
        }

        // Get VIP code data
        $code_id = absint($user_data['code_id']);
        $code_data = $this->db->get_code_by_id($code_id);
        
        if (!$code_data) {
            wp_send_json_error(array(
                'message' => __('VIP kod nije pronađen.', 'woocommerce-vip-paketi')
            ));
        }

        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'address_1', 'city', 'postcode'];
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Polje %s je obavezno.', 'woocommerce-vip-paketi'), $field)
                ));
            }
        }

        // Validate email format
        if (!is_email($user_data['email'])) {
            wp_send_json_error(array(
                'message' => __('Molimo unesite validnu email adresu.', 'woocommerce-vip-paketi')
            ));
        }

        // Check if user with email already exists
        $existing_user = get_user_by('email', $user_data['email']);
        if ($existing_user) {
            // Login existing user and activate VIP
            wp_set_current_user($existing_user->ID);
            wp_set_auth_cookie($existing_user->ID, true);
            
            // Ensure user has VIP role
            if (!user_can($existing_user->ID, 'wvp_vip_pricing')) {
                $user = new WP_User($existing_user->ID);
                $user->add_role('wvp_vip_member');
                
                clean_user_cache($existing_user->ID);
                wp_cache_delete($existing_user->ID, 'users');
                wp_cache_delete($existing_user->user_login, 'userlogins');
            }
            
            $user_id = $existing_user->ID;
        } else {
            // Create new user
            $username = $this->generate_username_from_user_data($user_data);
            $password = wp_generate_password(12, false);

            // Ensure VIP member role exists
            if (!get_role('wvp_vip_member')) {
                $customer_caps = get_role('customer') ? get_role('customer')->capabilities : array();
                add_role('wvp_vip_member', __('VIP Član', 'woocommerce-vip-paketi'), array_merge($customer_caps, array(
                    'wvp_vip_pricing' => true,
                    'wvp_vip_packages' => true
                )));
            }

            $new_user_data = array(
                'user_login' => $username,
                'user_email' => $user_data['email'],
                'user_pass' => $password,
                'first_name' => sanitize_text_field($user_data['first_name']),
                'last_name' => sanitize_text_field($user_data['last_name']),
                'display_name' => sanitize_text_field(trim($user_data['first_name'] . ' ' . $user_data['last_name'])),
                'role' => 'wvp_vip_member'
            );

            $user_id = wp_insert_user($new_user_data);

            if (is_wp_error($user_id)) {
                wp_send_json_error(array(
                    'message' => $user_id->get_error_message()
                ));
            }

            // Set additional user meta
            $meta_fields = ['phone', 'company', 'address_1', 'city', 'postcode', 'country'];
            foreach ($meta_fields as $field) {
                if (!empty($user_data[$field])) {
                    update_user_meta($user_id, 'billing_' . $field, sanitize_text_field($user_data[$field]));
                }
            }

            // Login the new user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);

            // Send welcome email
            $this->send_new_account_email($user_id, $password);
        }

        // Update VIP code with user data - convert to billing format
        $billing_data = array(
            'billing_email' => $user_data['email'],
            'billing_first_name' => $user_data['first_name'],
            'billing_last_name' => $user_data['last_name'],
            'billing_phone' => $user_data['phone'] ?? '',
            'billing_company' => $user_data['company'] ?? '',
            'billing_address_1' => $user_data['address_1'],
            'billing_address_2' => $user_data['address_2'] ?? '',
            'billing_city' => $user_data['city'],
            'billing_state' => $user_data['state'] ?? '',
            'billing_postcode' => $user_data['postcode'],
            'billing_country' => $user_data['country'] ?? 'RS'
        );

        // Update the VIP code in database - force update since this is user registration
        $this->db->populate_code_from_billing($code_id, $billing_data, true);
        $this->db->link_code_to_user($code_id, $user_id);
        $this->db->use_code($code_id);

        // Assign VIP status
        $this->assign_vip_status_to_user($user_id, $code_data);

        // Set VIP pricing session
        WC()->session->set('wvp_vip_pricing_active', true);
        
        // Update package prices in cart for new VIP status
        $this->update_package_prices_for_vip_activation();
        
        // Force VIP pricing recalculation
        if (WC()->cart) {
            // Trigger hooks manually to ensure prices are set
            do_action('woocommerce_before_calculate_totals', WC()->cart);
            
            // Calculate totals
            WC()->cart->calculate_totals();
            
            error_log('WVP DEBUG: Forced cart recalculation after VIP activation (register) - Cart total: ' . WC()->cart->get_total());
        }

        wp_send_json_success(array(
            'message' => __('VIP članstvo je uspešno aktivirano! Dobrodošli u VIP klub!', 'woocommerce-vip-paketi'),
            'autofill_data' => array(
                'billing_first_name' => $user_data['first_name'],
                'billing_last_name' => $user_data['last_name'],
                'billing_email' => $user_data['email'],
                'billing_phone' => $user_data['phone'] ?? '',
                'billing_company' => $user_data['company'] ?? '',
                'billing_address_1' => $user_data['address_1'],
                'billing_address_2' => $user_data['address_2'] ?? '',
                'billing_city' => $user_data['city'],
                'billing_state' => $user_data['state'] ?? '',
                'billing_postcode' => $user_data['postcode'],
                'billing_country' => $user_data['country'] ?? 'RS'
            ),
            'user_logged_in' => true,
            'refresh_checkout' => true
        ));
    }

    public function track_vip_order_completion($order_id) {
        // Get order object
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prevent double processing
        $already_processed = get_post_meta($order_id, '_wvp_order_tracked', true);
        if ($already_processed) {
            return;
        }

        // Mark as processed
        update_post_meta($order_id, '_wvp_order_tracked', 1);

        // Get order user
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        // Check if user has VIP role
        if (!user_can($user_id, 'wvp_vip_pricing')) {
            return;
        }

        // Get VIP codes associated with this user
        $vip_codes = $this->db->get_codes_by_user($user_id);
        if (empty($vip_codes)) {
            return;
        }

        // Calculate order total
        $order_total = $order->get_total();

        // Update purchase statistics for all VIP codes associated with this user
        foreach ($vip_codes as $code) {
            // Only update active codes
            if ($code->status === 'used') {
                $this->db->update_membership_data($code->id, array(
                    'amount' => $order_total
                ));
            }
        }

        // Add order note
        $order->add_order_note(sprintf(
            __('VIP purchase statistics updated. Order total: %s', 'woocommerce-vip-paketi'), 
            wc_price($order_total)
        ));
    }

    public function sync_existing_orders_for_vip_users() {
        // Get all VIP users
        $vip_users = get_users(array(
            'role' => 'wvp_vip_member',
            'fields' => 'ID'
        ));

        $updated_codes = 0;
        $processed_orders = 0;

        foreach ($vip_users as $user_id) {
            // Get user's completed orders
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('completed', 'processing'),
                'limit' => -1
            ));

            // Get user's VIP codes
            $vip_codes = $this->db->get_codes_by_user($user_id);
            if (empty($vip_codes)) {
                continue;
            }

            $total_spent = 0;
            $purchase_count = 0;
            $last_purchase_date = null;

            foreach ($orders as $order) {
                // Skip if already processed
                if (get_post_meta($order->get_id(), '_wvp_order_tracked', true)) {
                    continue;
                }

                $total_spent += $order->get_total();
                $purchase_count++;
                $order_date = $order->get_date_completed() ?: $order->get_date_created();
                
                if (!$last_purchase_date || $order_date > $last_purchase_date) {
                    $last_purchase_date = $order_date;
                }

                // Mark as processed
                update_post_meta($order->get_id(), '_wvp_order_tracked', 1);
                $processed_orders++;
            }

            // Update VIP codes with aggregated data
            if ($purchase_count > 0) {
                foreach ($vip_codes as $code) {
                    if ($code->status === 'used') {
                        $this->db->update_code($code->id, array(
                            'purchase_count' => $code->purchase_count + $purchase_count,
                            'total_spent' => $code->total_spent + $total_spent,
                            'last_purchase_date' => $last_purchase_date ? $last_purchase_date->format('Y-m-d H:i:s') : null
                        ));
                        $updated_codes++;
                    }
                }
            }
        }

        return array(
            'updated_codes' => $updated_codes,
            'processed_orders' => $processed_orders
        );
    }

    private function generate_username_from_user_data($user_data) {
        $base_username = '';
        
        // Try to create username from first/last name
        if (!empty($user_data['first_name']) && !empty($user_data['last_name'])) {
            $base_username = strtolower(sanitize_user($user_data['first_name'] . '.' . $user_data['last_name']));
        } else if (!empty($user_data['email'])) {
            // Use part before @ in email
            $email_parts = explode('@', $user_data['email']);
            $base_username = sanitize_user($email_parts[0]);
        }
        
        if (empty($base_username)) {
            $base_username = 'vip_user';
        }
        
        // Ensure username is unique
        $username = $base_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Populate user meta data from VIP code information
     */
    private function populate_user_meta_from_vip_code($user_id, $code_data) {
        // Map VIP code data to WordPress user meta fields (both billing and shipping)
        $field_mapping = array(
            'first_name' => array('first_name', 'billing_first_name', 'shipping_first_name'),
            'last_name' => array('last_name', 'billing_last_name', 'shipping_last_name'),
            'company' => array('billing_company', 'shipping_company'),
            'address_1' => array('billing_address_1', 'shipping_address_1'),
            'address_2' => array('billing_address_2', 'shipping_address_2'),
            'city' => array('billing_city', 'shipping_city'),
            'state' => array('billing_state', 'shipping_state'),
            'postcode' => array('billing_postcode', 'shipping_postcode'),
            'country' => array('billing_country', 'shipping_country'),
            'phone' => array('billing_phone'),
            'email' => array('billing_email')
        );

        foreach ($field_mapping as $code_field => $meta_fields) {
            $value = isset($code_data->$code_field) ? $code_data->$code_field : null;
            
            if (!empty($value)) {
                foreach ($meta_fields as $meta_field) {
                    update_user_meta($user_id, $meta_field, sanitize_text_field($value));
                }
            }
        }

        // Set default country to Serbia if not provided
        if (empty($code_data->country)) {
            update_user_meta($user_id, 'billing_country', 'RS');
            update_user_meta($user_id, 'shipping_country', 'RS');
        }

        // Store VIP activation information
        update_user_meta($user_id, '_wvp_vip_activated', current_time('mysql'));
        update_user_meta($user_id, '_wvp_vip_code_used', $code_data->code);
        update_user_meta($user_id, '_wvp_active_vip_codes', array($code_data->code));

        error_log("WVP: Populated user meta from VIP code for user {$user_id}. Code: {$code_data->code}");
    }

    /**
     * Populate user meta data from billing form data (checkout forms)
     */
    private function populate_user_meta_from_billing_data($user_id, $billing_data) {
        if (empty($billing_data) || !is_array($billing_data)) {
            return;
        }

        // Direct mapping of billing form fields to user meta
        $billing_fields = array(
            'billing_first_name', 'billing_last_name', 'billing_company',
            'billing_address_1', 'billing_address_2', 'billing_city',
            'billing_state', 'billing_postcode', 'billing_country',
            'billing_phone', 'billing_email'
        );

        // Also populate shipping fields (mirror billing for convenience)
        $shipping_fields = array(
            'shipping_first_name', 'shipping_last_name', 'shipping_company',
            'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_state', 'shipping_postcode', 'shipping_country'
        );

        // Populate billing fields
        foreach ($billing_fields as $field) {
            if (isset($billing_data[$field]) && !empty($billing_data[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($billing_data[$field]));
                
                // Also update core WordPress fields
                if ($field === 'billing_first_name') {
                    update_user_meta($user_id, 'first_name', sanitize_text_field($billing_data[$field]));
                } elseif ($field === 'billing_last_name') {
                    update_user_meta($user_id, 'last_name', sanitize_text_field($billing_data[$field]));
                }
            }
        }

        // Populate shipping fields (mirror billing data)
        foreach ($shipping_fields as $field) {
            $billing_equivalent = str_replace('shipping_', 'billing_', $field);
            if (isset($billing_data[$billing_equivalent]) && !empty($billing_data[$billing_equivalent])) {
                update_user_meta($user_id, $field, sanitize_text_field($billing_data[$billing_equivalent]));
            }
        }

        error_log("WVP: Populated user meta from billing data for user {$user_id}");
    }

    /**
     * Update package prices in cart when VIP status is activated
     */
    private function update_package_prices_for_vip_activation() {
        if (!WC()->cart) {
            error_log('WVP DEBUG: No cart available for VIP package update');
            return;
        }

        error_log('WVP DEBUG: Starting package prices update for VIP activation');
        error_log('WVP DEBUG: Cart items count: ' . count(WC()->cart->get_cart()));
        
        $packages_updated = 0;

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            // Only process package items
            if (!isset($cart_item['wvp_is_package']) || !$cart_item['wvp_is_package']) {
                error_log('WVP DEBUG: Skipping non-package item: ' . $cart_item_key);
                continue;
            }

            error_log('WVP DEBUG: Found package item: ' . $cart_item_key);

            // Get package data
            $package_subtotal = isset($cart_item['wvp_package_subtotal']) ? floatval($cart_item['wvp_package_subtotal']) : 0;
            $regular_discount_percent = isset($cart_item['wvp_regular_discount_percent']) ? floatval($cart_item['wvp_regular_discount_percent']) : 0;
            $vip_discount_percent = isset($cart_item['wvp_vip_discount_percent']) ? floatval($cart_item['wvp_vip_discount_percent']) : 0;
            $current_is_vip = isset($cart_item['wvp_is_vip_user']) ? $cart_item['wvp_is_vip_user'] : false;

            error_log('WVP DEBUG: Package data - Subtotal: ' . $package_subtotal . ', Regular discount: ' . $regular_discount_percent . '%, VIP discount: ' . $vip_discount_percent . '%, Current VIP status: ' . ($current_is_vip ? 'YES' : 'NO'));

            if ($package_subtotal <= 0) {
                error_log('WVP DEBUG: Skipping package - invalid subtotal');
                continue;
            }

            // Skip if already marked as VIP user
            if ($current_is_vip) {
                error_log('WVP DEBUG: Package already marked as VIP user, skipping');
                continue;
            }

            // Recalculate package total with VIP discounts
            $package_discount = $package_subtotal * ($regular_discount_percent / 100);
            $vip_discount_amount = $package_subtotal * ($vip_discount_percent / 100); // Now user is VIP
            $new_package_total = $package_subtotal - $package_discount - $vip_discount_amount;

            // Update cart item with new VIP pricing
            WC()->cart->cart_contents[$cart_item_key]['wvp_package_total'] = $new_package_total;
            WC()->cart->cart_contents[$cart_item_key]['wvp_vip_discount_amount'] = $vip_discount_amount;
            WC()->cart->cart_contents[$cart_item_key]['wvp_vip_discount'] = $vip_discount_amount; // For display
            WC()->cart->cart_contents[$cart_item_key]['wvp_package_discount'] = $package_discount; // For display
            WC()->cart->cart_contents[$cart_item_key]['wvp_is_vip_user'] = true;
            
            // Force the cart item product price to match the new package total
            if (isset(WC()->cart->cart_contents[$cart_item_key]['data'])) {
                WC()->cart->cart_contents[$cart_item_key]['data']->set_price($new_package_total);
                WC()->cart->cart_contents[$cart_item_key]['data']->set_regular_price($new_package_total);
            }
            
            // Ensure package quantity is always 1
            WC()->cart->cart_contents[$cart_item_key]['quantity'] = 1;

            error_log("WVP DEBUG: Updated package in cart - Key: {$cart_item_key}, Old Total: " . (isset($cart_item['wvp_package_total']) ? $cart_item['wvp_package_total'] : 'not set') . ", New Total: {$new_package_total}");
            $packages_updated++;
        }

        if ($packages_updated > 0) {
            // Update cart session to persist changes
            WC()->cart->set_session();
            error_log("WVP DEBUG: Updated {$packages_updated} package(s) for VIP activation");
        } else {
            error_log('WVP DEBUG: No packages needed VIP price update');
        }
    }
}