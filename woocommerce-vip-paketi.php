<?php
/**
 * Plugin Name: WooCommerce VIP Paketi
 * Plugin URI: https://example.com
 * Description: WooCommerce plugin za VIP članstvo sa dinamičkim cenama i variabilne pakete sa popustima
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-vip-paketi
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * Woo: 12345:abcdef
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WVP_VERSION')) {
    define('WVP_VERSION', '1.0.0');
}

if (!defined('WVP_PLUGIN_FILE')) {
    define('WVP_PLUGIN_FILE', __FILE__);
}

if (!defined('WVP_PLUGIN_DIR')) {
    define('WVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WVP_PLUGIN_URL')) {
    define('WVP_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('WVP_PLUGIN_BASENAME')) {
    define('WVP_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Define table name for WVP Health Quiz
if (!defined('WVP_HEALTH_QUIZ_TABLE')) {
    define('WVP_HEALTH_QUIZ_TABLE', $GLOBALS['wpdb']->prefix . 'wvp_health_quiz_results');
}

function wvp_check_woocommerce_dependency() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wvp_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

function wvp_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce VIP Paketi plugin requires WooCommerce to be installed and active.', 'woocommerce-vip-paketi'); ?></p>
    </div>
    <?php
}

function wvp_init() {
    if (!wvp_check_woocommerce_dependency()) {
        return;
    }

    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-activator.php';
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-deactivator.php';
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-loader.php';
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-core.php';
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-fixed-price.php';
    require_once WVP_PLUGIN_DIR . 'includes/wvp-serbian-translations.php';

    // Health Quiz includes
    require_once WVP_PLUGIN_DIR . 'includes/health-quiz/utils.php';
    require_once WVP_PLUGIN_DIR . 'includes/health-quiz/data-handler.php';
    require_once WVP_PLUGIN_DIR . 'includes/health-quiz/shortcodes.php';
    require_once WVP_PLUGIN_DIR . 'includes/health-quiz/openai-integration.php';

    $core = new WVP_Core();
    $core->run();
}

function wvp_activate() {
    if (!wvp_check_woocommerce_dependency()) {
        return;
    }
    
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-activator.php';
    WVP_Activator::activate();
}

function wvp_deactivate() {
    require_once WVP_PLUGIN_DIR . 'includes/class-wvp-deactivator.php';
    WVP_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'wvp_activate');
register_deactivation_hook(__FILE__, 'wvp_deactivate');

add_action('plugins_loaded', 'wvp_init');

function wvp_add_action_links($links) {
    $settings_link = '<a href="admin.php?page=wvp-settings">' . __('Podešavanja', 'woocommerce-vip-paketi') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wvp_add_action_links');

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Health Quiz table creation and management
add_action('plugins_loaded', 'wvp_health_quiz_maybe_create_table');
add_action('init', 'wvp_health_quiz_maybe_create_table');
function wvp_health_quiz_maybe_create_table() {
    global $wpdb;
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", WVP_HEALTH_QUIZ_TABLE));
    if ($table_exists != WVP_HEALTH_QUIZ_TABLE) {
        wvp_health_quiz_create_table();
    } else {
        // Check if all required columns exist and add if missing
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . WVP_HEALTH_QUIZ_TABLE . "`");
        $existing_columns = array();
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }

        $required_columns = array('id', 'first_name', 'last_name', 'email', 'phone', 'birth_year', 'location', 'country', 'answers', 'intensity_data', 'ai_analysis', 'ai_recommended_products', 'ai_recommended_packages', 'ai_score', 'product_id', 'order_id', 'user_id', 'public_analysis_id', 'created_at');

        foreach ($required_columns as $col) {
            if (!in_array($col, $existing_columns)) {
                switch ($col) {
                    case 'first_name':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `first_name` varchar(200) NOT NULL AFTER `id`");
                        break;
                    case 'last_name':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `last_name` varchar(200) NOT NULL AFTER `first_name`");
                        break;
                    case 'country':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `country` varchar(100) DEFAULT '' AFTER `location`");
                        break;
                    case 'product_id':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `product_id` bigint(20) NOT NULL DEFAULT 0 AFTER `answers`");
                        break;
                    case 'order_id':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `order_id` bigint(20) NOT NULL DEFAULT 0 AFTER `product_id`");
                        break;
                    case 'intensity_data':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `intensity_data` text DEFAULT '' AFTER `answers`");
                        break;
                    case 'ai_analysis':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `ai_analysis` text DEFAULT '' AFTER `intensity_data`");
                        break;
                    case 'ai_recommended_products':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `ai_recommended_products` text DEFAULT '' AFTER `ai_analysis`");
                        break;
                    case 'ai_recommended_packages':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `ai_recommended_packages` text DEFAULT '' AFTER `ai_recommended_products`");
                        break;
                    case 'ai_score':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `ai_score` int(3) DEFAULT 0 AFTER `ai_recommended_packages`");
                        break;
                    case 'user_id':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `user_id` bigint(20) DEFAULT 0 AFTER `order_id`");
                        break;
                    case 'public_analysis_id':
                        $wpdb->query("ALTER TABLE `" . WVP_HEALTH_QUIZ_TABLE . "` ADD `public_analysis_id` varchar(32) DEFAULT '' AFTER `ai_score`");
                        break;
                }
            }
        }
    }
}

function wvp_health_quiz_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS " . WVP_HEALTH_QUIZ_TABLE . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        first_name varchar(200) NOT NULL,
        last_name varchar(200) NOT NULL,
        email varchar(200) NOT NULL,
        phone varchar(100) NOT NULL,
        birth_year int(4) NOT NULL,
        location varchar(200) DEFAULT '',
        country varchar(100) DEFAULT '',
        answers text NOT NULL,
        intensity_data text DEFAULT '',
        ai_analysis text DEFAULT '',
        ai_recommended_products text DEFAULT '',
        ai_recommended_packages text DEFAULT '',
        ai_custom_packages text DEFAULT '',
        ai_score int(3) DEFAULT 0,
        public_analysis_id varchar(32) DEFAULT '',
        product_id bigint(20) NOT NULL,
        order_id bigint(20) NOT NULL DEFAULT 0,
        user_id bigint(20) DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Add new columns if they don't exist (for existing installations)
    $ai_custom_packages_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM " . WVP_HEALTH_QUIZ_TABLE . " LIKE %s", 'ai_custom_packages'));
    if (empty($ai_custom_packages_exists)) {
        $wpdb->query("ALTER TABLE " . WVP_HEALTH_QUIZ_TABLE . " ADD COLUMN ai_custom_packages text DEFAULT ''");
    }

    $session_id_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM " . WVP_HEALTH_QUIZ_TABLE . " LIKE %s", 'session_id'));
    if (empty($session_id_exists)) {
        $wpdb->query("ALTER TABLE " . WVP_HEALTH_QUIZ_TABLE . " ADD COLUMN session_id varchar(36) DEFAULT '' AFTER public_analysis_id");
    }
}

// Health Quiz checkout fill script
add_action('wp_enqueue_scripts', 'wvp_health_quiz_enqueue_checkout_fill_script');
function wvp_health_quiz_enqueue_checkout_fill_script() {
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received')) {
        wp_enqueue_script('wvp-health-quiz-checkout-fill', WVP_PLUGIN_URL . 'assets/js/health-quiz-checkout-fill.js', array('jquery'), WVP_VERSION, true);
    }
}

// Health Quiz order tracking and user creation
add_action('woocommerce_checkout_order_processed', 'wvp_health_quiz_save_order_to_result', 10, 3);
function wvp_health_quiz_save_order_to_result($order_id, $posted_data, $order) {
    if (empty($_COOKIE['wvp_result_id'])) {
        return;
    }
    $result_id = intval($_COOKIE['wvp_result_id']);
    if ($result_id > 0) {
        global $wpdb;

        // Get health quiz data
        $quiz_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d", $result_id));

        if ($quiz_data) {
            $created_user_id = 0;

            // Create user only if not logged in and doesn't exist
            if (!$quiz_data->user_id || $quiz_data->user_id == 0) {
                $existing_user = get_user_by('email', $quiz_data->email);

                if (!$existing_user) {
                    // Create new user
                    $username = sanitize_user($quiz_data->email);
                    $password = wp_generate_password();

                    $user_data = array(
                        'user_login' => $username,
                        'user_email' => $quiz_data->email,
                        'user_pass' => $password,
                        'first_name' => $quiz_data->first_name,
                        'last_name' => $quiz_data->last_name,
                        'display_name' => $quiz_data->first_name . ' ' . $quiz_data->last_name,
                        'role' => 'customer'
                    );

                    $created_user_id = wp_insert_user($user_data);

                    if (!is_wp_error($created_user_id)) {
                        // Save additional user meta
                        update_user_meta($created_user_id, 'billing_first_name', $quiz_data->first_name);
                        update_user_meta($created_user_id, 'billing_last_name', $quiz_data->last_name);
                        update_user_meta($created_user_id, 'billing_email', $quiz_data->email);
                        update_user_meta($created_user_id, 'billing_phone', $quiz_data->phone);
                        update_user_meta($created_user_id, 'billing_city', $quiz_data->location);
                        update_user_meta($created_user_id, 'billing_country', $quiz_data->country);
                        update_user_meta($created_user_id, 'wvp_birth_year', $quiz_data->birth_year);

                        // Set shipping same as billing
                        update_user_meta($created_user_id, 'shipping_first_name', $quiz_data->first_name);
                        update_user_meta($created_user_id, 'shipping_last_name', $quiz_data->last_name);
                        update_user_meta($created_user_id, 'shipping_city', $quiz_data->location);
                        update_user_meta($created_user_id, 'shipping_country', $quiz_data->country);

                        // Send new user notification
                        wp_new_user_notification($created_user_id, null, 'user');
                    }
                } else {
                    $created_user_id = $existing_user->ID;
                }
            } else {
                $created_user_id = $quiz_data->user_id;
            }

            // Update quiz record with order and user info
            $wpdb->update(
                WVP_HEALTH_QUIZ_TABLE,
                array(
                    'order_id' => $order_id,
                    'user_id' => $created_user_id
                ),
                array('id' => $result_id)
            );

            // Update order with user if created
            if ($created_user_id && !$order->get_user_id()) {
                $order->set_customer_id($created_user_id);
                $order->save();
            }
        }

        setcookie('wvp_result_id', '', time() - DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }
}

// Health Quiz URL handling
add_action('init', 'wvp_health_quiz_add_rewrite_rules');
function wvp_health_quiz_add_rewrite_rules() {
    $health_quiz_slug = get_option('wvp_health_quiz_url_slug', 'analiza-zdravstvenog-stanja');
    add_rewrite_rule('^' . $health_quiz_slug . '/?$', 'index.php?wvp_health_quiz=main', 'top');
    add_rewrite_rule('^' . $health_quiz_slug . '/zavrsena-anketa/?$', 'index.php?wvp_health_quiz=completed', 'top');

    // Dynamic step URLs: pitanja1, pitanja2, pitanja3...
    add_rewrite_rule('^' . $health_quiz_slug . '/pitanja([0-9]+)/?$', 'index.php?wvp_health_quiz=main&wvp_quiz_step=$matches[1]', 'top');

    // Report page
    add_rewrite_rule('^' . $health_quiz_slug . '/izvestaj/?$', 'index.php?wvp_health_quiz=main&wvp_quiz_step=report', 'top');

    // Flush rewrite rules if needed
    if (get_option('wvp_health_quiz_flush_rewrite_rules') === 'yes') {
        flush_rewrite_rules();
        delete_option('wvp_health_quiz_flush_rewrite_rules');
    }

    // Force flush rewrite rules on first load after URL changes
    if (!get_option('wvp_health_quiz_routes_added_v2')) {
        flush_rewrite_rules();
        update_option('wvp_health_quiz_routes_added_v2', '1');
    }
}

add_filter('query_vars', 'wvp_health_quiz_add_query_vars');
function wvp_health_quiz_add_query_vars($vars) {
    $vars[] = 'wvp_health_quiz';
    $vars[] = 'wvp_quiz_step';
    return $vars;
}

add_action('template_redirect', 'wvp_health_quiz_template_redirect');
function wvp_health_quiz_template_redirect() {
    $health_quiz = get_query_var('wvp_health_quiz');
    $quiz_step = get_query_var('wvp_quiz_step');

    // Debug logging for URL routing
    if (strpos($_SERVER['REQUEST_URI'], 'analiza-zdravstvenog-stanja') !== false) {
        error_log('WVP Health Quiz Template Redirect: health_quiz = "' . $health_quiz . '", quiz_step = "' . $quiz_step . '", URI = ' . $_SERVER['REQUEST_URI']);
    }

    if ($health_quiz) {
        // Load the health quiz template
        include WVP_PLUGIN_DIR . 'includes/health-quiz/template.php';
        exit;
    }
}

