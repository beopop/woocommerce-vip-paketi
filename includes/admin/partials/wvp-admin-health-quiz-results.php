<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include the results table class
require_once WVP_PLUGIN_DIR . 'includes/health-quiz/results-table.php';

// Handle export
if (!empty($_GET['export']) && current_user_can('manage_woocommerce')) {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " ORDER BY created_at DESC", ARRAY_A);
    $header = array('ID','First Name','Last Name','Email','Phone','Birth Year','Location','Country','Answers','AI Score','AI Analysis','AI Products','AI Packages','Product','Order ID','User ID','Date');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="wvp-health-quiz-results.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $header);
    $countries = array('RS' => 'Srbija', 'HU' => 'Mađarska');
    foreach ($rows as $row) {
        $country_name = isset($countries[$row['country']]) ? $countries[$row['country']] : $row['country'];
        $user_name = '';
        if (!empty($row['user_id'])) {
            $user = get_user_by('id', $row['user_id']);
            if ($user) {
                $user_name = $user->display_name;
            }
        }

        // Process AI data
        $ai_analysis = maybe_unserialize($row['ai_analysis']);
        $ai_analysis_text = '';
        if (!empty($ai_analysis) && is_array($ai_analysis)) {
            $parts = array();
            if (isset($ai_analysis['stanje_organizma'])) $parts[] = 'Stanje: ' . $ai_analysis['stanje_organizma'];
            if (isset($ai_analysis['preporuke'])) $parts[] = 'Preporuke: ' . $ai_analysis['preporuke'];
            $ai_analysis_text = implode(' | ', $parts);
        }

        $ai_products = maybe_unserialize($row['ai_recommended_products']);
        $ai_products_text = is_array($ai_products) ? implode(',', $ai_products) : '';

        $ai_packages = maybe_unserialize($row['ai_recommended_packages']);
        $ai_packages_text = is_array($ai_packages) ? implode(',', $ai_packages) : '';

        fputcsv($out, array(
            $row['id'],
            isset($row['first_name']) ? $row['first_name'] : '',
            isset($row['last_name']) ? $row['last_name'] : '',
            $row['email'],
            $row['phone'],
            $row['birth_year'],
            $row['location'],
            $country_name,
            implode(',', maybe_unserialize($row['answers'])),
            $row['ai_score'],
            $ai_analysis_text,
            $ai_products_text,
            $ai_packages_text,
            $row['product_id'] ? get_the_title($row['product_id']) : '',
            $row['order_id'],
            $user_name,
            $row['created_at'],
        ));
    }
    fclose($out);
    exit;
}

// Handle view action - show detailed report
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $result_id = intval($_GET['id']);
    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . WVP_HEALTH_QUIZ_TABLE . " WHERE id = %d",
        $result_id
    ), ARRAY_A);

    if ($result) {
        // Check if bulletproof view is requested
        if (isset($_GET['bulletproof']) && $_GET['bulletproof'] === '1') {
            require_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-bulletproof-report.php';
        } else {
            require_once WVP_PLUGIN_DIR . 'includes/admin/partials/wvp-admin-health-quiz-detailed-report.php';
        }
        return;
    }
}

$table = new WVP_Health_Quiz_Results_Table();
$table->process_bulk_action();
$table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Health Quiz Rezultati', 'woocommerce-vip-paketi'); ?></h1>
    <a href="?page=wvp-health-quiz-results&export=1" class="page-title-action"><?php _e('Export CSV', 'woocommerce-vip-paketi'); ?></a>

    <form method="post">
        <?php
        wp_nonce_field('bulk-' . $table->_args['plural']);
        $table->search_box(__('Pretraga', 'woocommerce-vip-paketi'), 'wvp-health-quiz-search');
        $table->display();
        ?>
    </form>
</div>