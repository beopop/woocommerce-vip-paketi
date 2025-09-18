<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Add screen option filter for health quiz results
        add_filter('set-screen-option', array($this, 'wvp_health_set_screen_option'), 10, 3);
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, WVP_PLUGIN_URL . 'assets/css/wvp-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, WVP_PLUGIN_URL . 'assets/js/wvp-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'wvp_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wvp_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Da li ste sigurni da želite da obrišete ovaj element?', 'woocommerce-vip-paketi'),
                'loading' => __('Učitavam...', 'woocommerce-vip-paketi'),
                'error' => __('Došlo je do greške. Molimo pokušajte ponovo.', 'woocommerce-vip-paketi'),
                'success' => __('Operacija je uspešno završena.', 'woocommerce-vip-paketi')
            )
        ));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __('WVP Podešavanja', 'woocommerce-vip-paketi'),
            __('VIP Paketi', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-settings',
            array($this, 'display_plugin_admin_page'),
            'dashicons-star-filled',
            56
        );

        add_submenu_page(
            'wvp-settings',
            __('Opšte Postavke', 'woocommerce-vip-paketi'),
            __('Opšte Postavke', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-settings',
            array($this, 'display_plugin_admin_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('VIP Kodovi', 'woocommerce-vip-paketi'),
            __('VIP Kodovi', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-vip-codes',
            array($this, 'display_vip_codes_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('Paketi', 'woocommerce-vip-paketi'),
            __('Paketi', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-packages',
            array($this, 'display_packages_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('Podešavanja Proizvoda', 'woocommerce-vip-paketi'),
            __('Podešavanja Proizvoda', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-products',
            array($this, 'display_products_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('Izveštaji', 'woocommerce-vip-paketi'),
            __('Izveštaji', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-reports',
            array($this, 'display_reports_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('Dizajn Stranice Paketa', 'woocommerce-vip-paketi'),
            __('Dizajn Stranice Paketa', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-page-design',
            array($this, 'display_page_design')
        );

        // Health Quiz submenus
        add_submenu_page(
            'wvp-settings',
            __('Health Quiz Pitanja', 'woocommerce-vip-paketi'),
            __('Health Quiz Pitanja', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-health-quiz-questions',
            array($this, 'display_health_quiz_questions_page')
        );

        add_submenu_page(
            'wvp-settings',
            __('AI Integracija', 'woocommerce-vip-paketi'),
            __('AI Integracija', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-ai-integration',
            array($this, 'display_ai_integration_page')
        );

        $hook = add_submenu_page(
            'wvp-settings',
            __('Health Quiz Rezultati', 'woocommerce-vip-paketi'),
            __('Health Quiz Rezultati', 'woocommerce-vip-paketi'),
            'manage_woocommerce',
            'wvp-health-quiz-results',
            array($this, 'display_health_quiz_results_page')
        );
        add_action("load-$hook", array($this, 'health_quiz_results_screen_option'));
    }

    public function options_update() {
        // Set default package URL slug if not set
        if (get_option('wvp_package_url_slug') === false) {
            add_option('wvp_package_url_slug', 'konfiguracija-paketa');
            // Force rewrite rules flush for new installations
            update_option('wvp_flush_rewrite_rules', 'yes');
        }

        // Set default health quiz URL slug if not set
        if (get_option('wvp_health_quiz_url_slug') === false) {
            add_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');
            // Force rewrite rules flush for new installations
            update_option('wvp_health_quiz_flush_rewrite_rules', 'yes');
        }
        
        register_setting('wvp_settings_group', 'wvp_enable_vip_pricing');
        register_setting('wvp_settings_group', 'wvp_vip_role_enabled');
        register_setting('wvp_settings_group', 'wvp_vip_price_label');
        register_setting('wvp_settings_group', 'wvp_non_vip_display_format');
        register_setting('wvp_settings_group', 'wvp_enable_checkout_codes');
        register_setting('wvp_settings_group', 'wvp_auto_registration');
        register_setting('wvp_settings_group', 'wvp_email_notifications');
        register_setting('wvp_settings_group', 'wvp_package_allowed_products');
        register_setting('wvp_settings_group', 'wvp_enable_packages');
        register_setting('wvp_settings_group', 'wvp_woodmart_integration');
        register_setting('wvp_settings_group', 'wvp_debug_mode');
        register_setting('wvp_settings_group', 'wvp_default_package_sizes');
        register_setting('wvp_settings_group', 'wvp_default_regular_discount');
        register_setting('wvp_settings_group', 'wvp_default_vip_discount');
        register_setting('wvp_settings_group', 'wvp_packages_allow_coupons');
        register_setting('wvp_settings_group', 'wvp_woodmart_vip_color');
        register_setting('wvp_settings_group', 'wvp_woodmart_badge_position');
        register_setting('wvp_settings_group', 'wvp_package_url_slug', array(
            'sanitize_callback' => array($this, 'sanitize_package_url_slug')
        ));
        register_setting('wvp_settings_group', 'wvp_health_quiz_url_slug', array(
            'sanitize_callback' => array($this, 'sanitize_health_quiz_url_slug')
        ));
        
        
        // Page Design Settings
        register_setting('wvp_page_design_group', 'wvp_page_title_text');
        register_setting('wvp_page_design_group', 'wvp_page_subtitle_text');
        register_setting('wvp_page_design_group', 'wvp_page_description_text');
        register_setting('wvp_page_design_group', 'wvp_discount_table_title');
        register_setting('wvp_page_design_group', 'wvp_discount_explanation_title');
        register_setting('wvp_page_design_group', 'wvp_primary_color');
        register_setting('wvp_page_design_group', 'wvp_secondary_color');
        register_setting('wvp_page_design_group', 'wvp_accent_color');
        register_setting('wvp_page_design_group', 'wvp_text_color');
        register_setting('wvp_page_design_group', 'wvp_background_color');
        register_setting('wvp_page_design_group', 'wvp_border_radius');
        register_setting('wvp_page_design_group', 'wvp_font_size');
        register_setting('wvp_page_design_group', 'wvp_spacing');

        add_settings_section(
            'wvp_general_section',
            __('Opšte Postavke', 'woocommerce-vip-paketi'),
            array($this, 'general_section_callback'),
            'wvp_settings_general'
        );

        add_settings_section(
            'wvp_vip_section',
            __('VIP Podešavanja', 'woocommerce-vip-paketi'),
            array($this, 'vip_section_callback'),
            'wvp_settings_vip'
        );

        add_settings_section(
            'wvp_package_section',
            __('Podešavanja Paketa', 'woocommerce-vip-paketi'),
            array($this, 'package_section_callback'),
            'wvp_settings_packages'
        );

        add_settings_section(
            'wvp_integration_section',
            __('Podešavanja Integracija', 'woocommerce-vip-paketi'),
            array($this, 'integration_section_callback'),
            'wvp_settings_integrations'
        );

        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        add_settings_field(
            'wvp_enable_vip_pricing',
            __('Ukljucuje VIP Cene', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_general',
            'wvp_general_section',
            array('field' => 'wvp_enable_vip_pricing')
        );

        add_settings_field(
            'wvp_vip_role_enabled',
            __('Ukljucuje VIP Ulogu', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_vip',
            'wvp_vip_section',
            array('field' => 'wvp_vip_role_enabled')
        );

        add_settings_field(
            'wvp_vip_price_label',
            __('VIP Oznaka Cene', 'woocommerce-vip-paketi'),
            array($this, 'text_field_callback'),
            'wvp_settings_vip',
            'wvp_vip_section',
            array('field' => 'wvp_vip_price_label', 'placeholder' => 'VIP Cena')
        );

        add_settings_field(
            'wvp_non_vip_display_format',
            __('Format Prikaza za Ne-VIP', 'woocommerce-vip-paketi'),
            array($this, 'select_field_callback'),
            'wvp_settings_vip',
            'wvp_vip_section',
            array(
                'field' => 'wvp_non_vip_display_format',
                'options' => array(
                    'both' => __('Prikaži i redovne i VIP cene', 'woocommerce-vip-paketi'),
                    'regular_only' => __('Prikaži samo redovnu cenu', 'woocommerce-vip-paketi'),
                    'vip_teaser' => __('Prikaži VIP cenu kao najavu', 'woocommerce-vip-paketi')
                )
            )
        );

        add_settings_field(
            'wvp_enable_checkout_codes',
            __('Enable Checkout VIP Codes', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_vip',
            'wvp_vip_section',
            array('field' => 'wvp_enable_checkout_codes')
        );

        add_settings_field(
            'wvp_auto_registration',
            __('Auto-register with VIP codes', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_vip',
            'wvp_vip_section',
            array('field' => 'wvp_auto_registration')
        );

        add_settings_field(
            'wvp_enable_packages',
            __('Enable Packages', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_packages',
            'wvp_package_section',
            array('field' => 'wvp_enable_packages')
        );

        add_settings_field(
            'wvp_email_notifications',
            __('Enable Email Notifications', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_integrations',
            'wvp_integration_section',
            array('field' => 'wvp_email_notifications')
        );

        add_settings_field(
            'wvp_woodmart_integration',
            __('Enable Woodmart Integration', 'woocommerce-vip-paketi'),
            array($this, 'checkbox_field_callback'),
            'wvp_settings_integrations',
            'wvp_integration_section',
            array('field' => 'wvp_woodmart_integration')
        );

        add_settings_field(
            'wvp_health_quiz_url_slug',
            __('Health Quiz URL Slug', 'woocommerce-vip-paketi'),
            array($this, 'text_field_callback'),
            'wvp_settings_general',
            'wvp_general_section',
            array(
                'field' => 'wvp_health_quiz_url_slug',
                'placeholder' => 'analiza-zdravstvenog-stanja',
                'description' => __('URL slug za Health Quiz stranicu (npr. analiza-zdravstvenog-stanja)', 'woocommerce-vip-paketi')
            )
        );
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'woocommerce-vip-paketi') . '</p>';
    }

    public function vip_section_callback() {
        echo '<p>' . __('Configure VIP membership and pricing settings.', 'woocommerce-vip-paketi') . '</p>';
    }

    public function package_section_callback() {
        echo '<p>' . __('Configure package system settings.', 'woocommerce-vip-paketi') . '</p>';
    }

    public function integration_section_callback() {
        echo '<p>' . __('Configure integration with other plugins and themes.', 'woocommerce-vip-paketi') . '</p>';
    }

    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, 'no');
        ?>
        <input type="checkbox" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="yes" <?php checked($value, 'yes'); ?> />
        <label for="<?php echo esc_attr($field); ?>"><?php echo isset($args['label']) ? esc_html($args['label']) : __('Enable', 'woocommerce-vip-paketi'); ?></label>
        <?php if (isset($args['description'])): ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
        <?php
    }

    public function text_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        ?>
        <input type="text" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" class="regular-text" />
        <?php if (isset($args['description'])): ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function select_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        $options = isset($args['options']) ? $args['options'] : array();
        ?>
        <select id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>">
            <?php foreach ($options as $option_value => $option_label): ?>
            <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>><?php echo esc_html($option_label); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($args['description'])): ?>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    public function display_plugin_admin_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-settings.php';
    }

    public function display_vip_codes_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-vip-codes.php';
    }

    public function display_packages_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-packages.php';
    }

    public function display_products_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-products.php';
    }

    public function display_reports_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-reports.php';
    }

    public function display_page_design() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-page-design.php';
    }

    public function add_vip_price_field() {
        global $product_object;

        if (!$product_object || $product_object->is_type('variable')) {
            return;
        }

        $vip_price = get_post_meta($product_object->get_id(), '_wvp_vip_price', true);
        $enable_vip = get_post_meta($product_object->get_id(), '_wvp_enable_vip_pricing', true);

        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="_wvp_enable_vip_pricing">
                    <input type="checkbox" id="_wvp_enable_vip_pricing" name="_wvp_enable_vip_pricing" value="yes" <?php checked($enable_vip, 'yes'); ?> />
                    <?php _e('Enable VIP pricing for this product', 'woocommerce-vip-paketi'); ?>
                </label>
            </p>
            <p class="form-field _wvp_vip_price_field">
                <label for="_wvp_vip_price"><?php _e('VIP price', 'woocommerce-vip-paketi'); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
                <input type="text" class="short wc_input_price" name="_wvp_vip_price" id="_wvp_vip_price" value="<?php echo esc_attr($vip_price); ?>" placeholder="<?php _e('VIP price', 'woocommerce-vip-paketi'); ?>" />
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#_wvp_enable_vip_pricing').change(function() {
                if ($(this).is(':checked')) {
                    $('._wvp_vip_price_field').show();
                } else {
                    $('._wvp_vip_price_field').hide();
                }
            }).trigger('change');
        });
        </script>
        <?php
    }

    public function save_vip_price_field($post_id) {
        $enable_vip = isset($_POST['_wvp_enable_vip_pricing']) ? 'yes' : 'no';
        update_post_meta($post_id, '_wvp_enable_vip_pricing', $enable_vip);

        if (isset($_POST['_wvp_vip_price'])) {
            $vip_price = wc_format_decimal($_POST['_wvp_vip_price']);
            update_post_meta($post_id, '_wvp_vip_price', $vip_price);
        }
    }

    public function sanitize_package_url_slug($value) {
        // Get the old value
        $old_value = get_option('wvp_package_url_slug', 'konfiguracija-paketa');
        
        // Sanitize the new value
        $new_value = sanitize_title($value);
        
        // If the value changed, flush rewrite rules
        if ($old_value !== $new_value) {
            // Set a flag to flush rewrite rules on next page load
            update_option('wvp_flush_rewrite_rules', 'yes');
        }
        
        return $new_value;
    }

    public function sanitize_health_quiz_url_slug($value) {
        // Get the old value
        $old_value = get_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');

        // Sanitize the new value
        $new_value = sanitize_title($value);

        // If empty, use default
        if (empty($new_value)) {
            $new_value = 'analiza-zdravstvenog-stanja';
        }

        // If the value changed, flush rewrite rules
        if ($old_value !== $new_value) {
            // Set a flag to flush rewrite rules on next page load
            update_option('wvp_health_quiz_flush_rewrite_rules', 'yes');
        }

        return $new_value;
    }

    // Health Quiz Methods
    public function wvp_health_set_screen_option($status, $option, $value) {
        if ('wvp_health_quiz_results_per_page' === $option) {
            return (int) $value;
        }
        return $status;
    }

    public function display_health_quiz_questions_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-health-quiz-questions.php';
    }

    public function display_ai_integration_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-ai-integration.php';
    }

    public function display_health_quiz_results_page() {
        include_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-health-quiz-results.php';
    }

    public function health_quiz_results_screen_option() {
        $args = array(
            'label'   => 'Rezultata po strani',
            'default' => 20,
            'option'  => 'wvp_health_quiz_results_per_page',
        );
        add_screen_option('per_page', $args);
    }
}