<?php
/*
 * WVP Health Quiz Debug Information
 * Access via: http://testni-sajt.local/wp-content/plugins/woocommerce-vip-paketi/debug-info.php
 */

// Load WordPress
require_once('../../../wp-config.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

echo "<h1>WVP Health Quiz Debug Information</h1>";

// 1. Check if plugin files exist
echo "<h2>1. Plugin Files Check</h2>";
$files_to_check = [
    'Main Plugin' => WVP_PLUGIN_DIR . 'woocommerce-vip-paketi.php',
    'Data Handler' => WVP_PLUGIN_DIR . 'includes/health-quiz/data-handler.php',
    'Shortcodes' => WVP_PLUGIN_DIR . 'includes/health-quiz/shortcodes.php',
    'JavaScript' => WVP_PLUGIN_DIR . 'assets/js/health-quiz.js'
];

foreach ($files_to_check as $name => $file) {
    $exists = file_exists($file);
    echo "<p><strong>$name:</strong> " . ($exists ? "✅ EXISTS" : "❌ MISSING") . " ($file)</p>";
}

// 2. Check WordPress hooks
echo "<h2>2. WordPress Hooks Check</h2>";
global $wp_filter;

$hooks_to_check = [
    'wp_ajax_wvp_save_answers',
    'wp_ajax_nopriv_wvp_save_answers',
    'wp_ajax_wvp_save_quiz',
    'wp_ajax_nopriv_wvp_save_quiz'
];

foreach ($hooks_to_check as $hook) {
    $exists = isset($wp_filter[$hook]);
    echo "<p><strong>$hook:</strong> " . ($exists ? "✅ REGISTERED" : "❌ NOT REGISTERED") . "</p>";
    if ($exists) {
        $callbacks = $wp_filter[$hook]->callbacks;
        foreach ($callbacks as $priority => $functions) {
            foreach ($functions as $function) {
                echo "<ul><li>Priority $priority: " . print_r($function['function'], true) . "</li></ul>";
            }
        }
    }
}

// 3. Check database
echo "<h2>3. Database Check</h2>";
global $wpdb;

if (!defined('WVP_HEALTH_QUIZ_TABLE')) {
    define('WVP_HEALTH_QUIZ_TABLE', $wpdb->prefix . 'wvp_health_quiz_results');
}

$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", WVP_HEALTH_QUIZ_TABLE));
echo "<p><strong>Table Name:</strong> " . WVP_HEALTH_QUIZ_TABLE . "</p>";
echo "<p><strong>Table Exists:</strong> " . ($table_exists ? "✅ YES" : "❌ NO") . "</p>";

if ($table_exists) {
    $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . WVP_HEALTH_QUIZ_TABLE . "`");
    echo "<p><strong>Columns:</strong></p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column->Field . " (" . $column->Type . ")</li>";
    }
    echo "</ul>";

    $count = $wpdb->get_var("SELECT COUNT(*) FROM `" . WVP_HEALTH_QUIZ_TABLE . "`");
    echo "<p><strong>Record Count:</strong> $count</p>";

    if ($count > 0) {
        $recent = $wpdb->get_results("SELECT * FROM `" . WVP_HEALTH_QUIZ_TABLE . "` ORDER BY created_at DESC LIMIT 3");
        echo "<p><strong>Recent Records:</strong></p>";
        foreach ($recent as $record) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
            echo "<p>ID: {$record->id}, Name: {$record->first_name} {$record->last_name}, Email: {$record->email}, Created: {$record->created_at}</p>";
            echo "</div>";
        }
    }
}

// 4. Check WordPress configuration
echo "<h2>4. WordPress Configuration</h2>";
echo "<p><strong>AJAX URL:</strong> " . admin_url('admin-ajax.php') . "</p>";
echo "<p><strong>WP_DEBUG:</strong> " . (WP_DEBUG ? "✅ ENABLED" : "❌ DISABLED") . "</p>";
echo "<p><strong>WP_DEBUG_LOG:</strong> " . (WP_DEBUG_LOG ? "✅ ENABLED" : "❌ DISABLED") . "</p>";

// 5. Check functions
echo "<h2>5. Function Check</h2>";
$functions_to_check = [
    'wvp_save_answers',
    'wvp_save_quiz',
    'wp_create_nonce',
    'check_ajax_referer'
];

foreach ($functions_to_check as $func) {
    $exists = function_exists($func);
    echo "<p><strong>$func:</strong> " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
}

// 6. Test nonce
echo "<h2>6. Nonce Test</h2>";
$test_nonce = wp_create_nonce('wvp_health_quiz_nonce');
echo "<p><strong>Generated Nonce:</strong> $test_nonce</p>";
echo "<p><strong>Verification:</strong> " . (wp_verify_nonce($test_nonce, 'wvp_health_quiz_nonce') ? "✅ VALID" : "❌ INVALID") . "</p>";

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='test-ajax.php'>📄 Open AJAX Test Page</a></p>";
echo "<p><a href='http://testni-sajt.local/analiza-zdravstvenog-stanja'>🩺 Open Health Quiz</a></p>";
echo "<p><a href='http://testni-sajt.local/wp-admin/admin.php?page=wvp-health-quiz-results'>📊 View Results Page</a></p>";
?>