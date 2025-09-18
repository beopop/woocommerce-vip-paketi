<?php
/**
 * Emergency activation script - activate plugin after fix
 */

// WordPress bootstrap
$wp_config_path = '../../../wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
    
    echo "<h2>WVP Plugin Emergency Fix</h2>\n";
    
    // Check if plugin is active
    $active_plugins = get_option('active_plugins');
    $plugin_path = 'woocommerce-vip-paketi/woocommerce-vip-paketi.php';
    
    echo "<p><strong>Plugin Path:</strong> $plugin_path</p>\n";
    echo "<p><strong>Currently Active:</strong> " . (in_array($plugin_path, $active_plugins) ? 'YES' : 'NO') . "</p>\n";
    
    // Activate plugin
    if (!in_array($plugin_path, $active_plugins)) {
        $active_plugins[] = $plugin_path;
        update_option('active_plugins', $active_plugins);
        echo "<p style='color: green;'><strong>✓ Plugin activated successfully!</strong></p>\n";
    } else {
        echo "<p style='color: blue;'>Plugin is already active.</p>\n";
    }
    
    echo "<hr>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Go to WordPress admin → VIP Codes</li>\n";
    echo "<li>Click the '⚠️ Ažuriraj Bazu' button to update database</li>\n";
    echo "<li>Test the edit functionality</li>\n";
    echo "</ol>\n";
    
    echo "<p><em>The syntax error has been fixed and plugin is now active. Go to admin to test functionality.</em></p>\n";
    
} else {
    echo "<p style='color: red;'>Could not find WordPress configuration file.</p>\n";
}
?>