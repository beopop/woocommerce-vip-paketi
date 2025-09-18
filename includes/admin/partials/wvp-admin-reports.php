<?php
/**
 * Provide a admin area view for reports and analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$db = WVP_Database::get_instance();
$vip_codes_admin = new WVP_Admin_VIP_Codes();

// Get date range from query params
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d'); // Today

// Get statistics
$codes_stats = $db->get_statistics();
$total_vip_users = count(get_users(array('role' => 'wvp_vip_member')));

// Get VIP codes usage over time
$codes_usage = $db->get_codes(array(
    'status' => 'used',
    'date_from' => $date_from,
    'date_to' => $date_to,
    'limit' => 0
));

// Get recent VIP activations
$recent_activations = get_users(array(
    'role' => 'wvp_vip_member',
    'number' => 10,
    'orderby' => 'registered',
    'order' => 'DESC'
));

// Calculate VIP savings (simplified)
$vip_savings_total = 0;
$orders_with_vip = get_posts(array(
    'post_type' => 'shop_order',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_wvp_total_vip_savings',
            'compare' => 'EXISTS'
        )
    ),
    'date_query' => array(
        array(
            'after' => $date_from,
            'before' => $date_to . ' 23:59:59',
            'inclusive' => true
        )
    )
));

foreach ($orders_with_vip as $order_post) {
    $savings = get_post_meta($order_post->ID, '_wvp_total_vip_savings', true);
    if ($savings) {
        $vip_savings_total += floatval($savings);
    }
}

// Get top VIP products
$top_vip_products = array();
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 5,
    'meta_query' => array(
        array(
            'key' => '_wvp_enable_vip_pricing',
            'value' => 'yes',
            'compare' => '='
        )
    )
);

$vip_products = get_posts($args);
foreach ($vip_products as $product_post) {
    $product = wc_get_product($product_post->ID);
    if ($product) {
        $regular_price = $product->get_regular_price();
        $vip_price = get_post_meta($product->ID, '_wvp_vip_price', true);
        if ($regular_price && $vip_price && $vip_price < $regular_price) {
            $savings = $regular_price - $vip_price;
            $savings_percent = round(($savings / $regular_price) * 100, 1);
            $top_vip_products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'regular_price' => $regular_price,
                'vip_price' => $vip_price,
                'savings' => $savings,
                'savings_percent' => $savings_percent
            );
        }
    }
}

// Sort by savings percentage
usort($top_vip_products, function($a, $b) {
    return $b['savings_percent'] <=> $a['savings_percent'];
});
?>

<div class="wrap wvp-admin-page">
    <div class="wvp-admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><?php _e('Izveštaji i Analitika', 'woocommerce-vip-paketi'); ?></h1>
                <p><?php _e('Prati korišćenje VIP kodova, aktivnost članova i prodajne performanse.', 'woocommerce-vip-paketi'); ?></p>
            </div>
            <div>
                <form method="get" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="wvp-reports" />
                    <label><?php _e('Od:', 'woocommerce-vip-paketi'); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
                    <label><?php _e('Do:', 'woocommerce-vip-paketi'); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
                    <button type="submit" class="button"><?php _e('Filtriraj', 'woocommerce-vip-paketi'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="wvp-stats-grid">
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($total_vip_users); ?></div>
            <p class="wvp-stat-label"><?php _e('Ukupno VIP Članova', 'woocommerce-vip-paketi'); ?></p>
            <small class="wvp-stat-change positive">+<?php echo count(array_filter($recent_activations, function($user) use ($date_from) { return strtotime($user->user_registered) >= strtotime($date_from); })); ?> <?php _e('u ovom periodu', 'woocommerce-vip-paketi'); ?></small>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['used']); ?></div>
            <p class="wvp-stat-label"><?php _e('Korišćenih Kodova', 'woocommerce-vip-paketi'); ?></p>
            <small class="wvp-stat-change"><?php echo count($codes_usage); ?> <?php _e('u izabranom periodu', 'woocommerce-vip-paketi'); ?></small>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo wc_price($vip_savings_total); ?></div>
            <p class="wvp-stat-label"><?php _e('Ukupne VIP Uštede', 'woocommerce-vip-paketi'); ?></p>
            <small class="wvp-stat-change positive"><?php _e('Uštede kupaca u periodu', 'woocommerce-vip-paketi'); ?></small>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['usage_rate']); ?>%</div>
            <p class="wvp-stat-label"><?php _e('Stopa Korišćenja Kodova', 'woocommerce-vip-paketi'); ?></p>
            <small class="wvp-stat-change"><?php echo $codes_stats['used']; ?>/<?php echo $codes_stats['total']; ?> <?php _e('kodova korišćeno', 'woocommerce-vip-paketi'); ?></small>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 30px;">
        
        <!-- Left Column -->
        <div class="reports-left-column">
            
            <!-- VIP Code Usage Chart -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Korišćenje VIP Kodova Tokom Vremena', 'woocommerce-vip-paketi'); ?></div>
                
                <?php if (empty($codes_usage)): ?>
                <div class="no-data-message" style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 10px;">📊</div>
                    <h3><?php _e('Nema korišćenja VIP kodova u izabranom periodu', 'woocommerce-vip-paketi'); ?></h3>
                    <p><?php _e('Pokušaj da izabereš drugi opseg datuma ili proveri da li su kodovi korišćeni nedavno.', 'woocommerce-vip-paketi'); ?></p>
                </div>
                <?php else: ?>
                <canvas id="vip-usage-chart" style="max-height: 300px;"></canvas>
                <?php endif; ?>
            </div>

            <!-- Top VIP Products -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Najbolji VIP Proizvodi po Uštedama', 'woocommerce-vip-paketi'); ?></div>
                
                <?php if (empty($top_vip_products)): ?>
                <div class="no-data-message" style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🏆</div>
                    <h3><?php _e('Nema podešenih VIP proizvoda', 'woocommerce-vip-paketi'); ?></h3>
                    <p><?php _e('Podesi VIP cene na svojim proizvodima da vidiš najbolje izvođače ovde.', 'woocommerce-vip-paketi'); ?></p>
                    <a href="?page=wvp-products" class="button button-primary"><?php _e('Podesi Proizvode', 'woocommerce-vip-paketi'); ?></a>
                </div>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Proizvod', 'woocommerce-vip-paketi'); ?></th>
                            <th><?php _e('Regularna Cena', 'woocommerce-vip-paketi'); ?></th>
                            <th><?php _e('VIP Cena', 'woocommerce-vip-paketi'); ?></th>
                            <th><?php _e('Ušteda', 'woocommerce-vip-paketi'); ?></th>
                            <th><?php _e('Popust %', 'woocommerce-vip-paketi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($top_vip_products, 0, 10) as $product): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($product['name']); ?></strong>
                                <div class="row-actions">
                                    <a href="<?php echo get_edit_post_link($product['id']); ?>"><?php _e('Uredi', 'woocommerce-vip-paketi'); ?></a> |
                                    <a href="<?php echo get_permalink($product['id']); ?>" target="_blank"><?php _e('Pogledaj', 'woocommerce-vip-paketi'); ?></a>
                                </div>
                            </td>
                            <td><?php echo wc_price($product['regular_price']); ?></td>
                            <td><span style="color: #d4a017; font-weight: bold;"><?php echo wc_price($product['vip_price']); ?></span></td>
                            <td><span style="color: #28a745; font-weight: bold;"><?php echo wc_price($product['savings']); ?></span></td>
                            <td>
                                <div class="savings-percentage">
                                    <span style="color: #28a745; font-weight: bold;"><?php echo $product['savings_percent']; ?>%</span>
                                    <div class="savings-bar" style="background: #ddd; height: 6px; border-radius: 3px; margin-top: 2px;">
                                        <div style="background: #28a745; height: 100%; width: <?php echo min($product['savings_percent'], 100); ?>%; border-radius: 3px;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- VIP Code Statistics Breakdown -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Detaljne Statistike VIP Kodova', 'woocommerce-vip-paketi'); ?></div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="mini-stat-card">
                        <div class="mini-stat-number" style="color: #28a745;"><?php echo number_format_i18n($codes_stats['active']); ?></div>
                        <div class="mini-stat-label"><?php _e('Aktivni Kodovi', 'woocommerce-vip-paketi'); ?></div>
                        <div class="mini-stat-bar">
                            <div style="width: <?php echo $codes_stats['total'] > 0 ? ($codes_stats['active'] / $codes_stats['total']) * 100 : 0; ?>%; background: #28a745;"></div>
                        </div>
                    </div>
                    
                    <div class="mini-stat-card">
                        <div class="mini-stat-number" style="color: #dc3232;"><?php echo number_format_i18n($codes_stats['used']); ?></div>
                        <div class="mini-stat-label"><?php _e('Korišćeni Kodovi', 'woocommerce-vip-paketi'); ?></div>
                        <div class="mini-stat-bar">
                            <div style="width: <?php echo $codes_stats['total'] > 0 ? ($codes_stats['used'] / $codes_stats['total']) * 100 : 0; ?>%; background: #dc3232;"></div>
                        </div>
                    </div>
                    
                    <div class="mini-stat-card">
                        <div class="mini-stat-number" style="color: #ffb900;"><?php echo number_format_i18n($codes_stats['expired']); ?></div>
                        <div class="mini-stat-label"><?php _e('Istekli Kodovi', 'woocommerce-vip-paketi'); ?></div>
                        <div class="mini-stat-bar">
                            <div style="width: <?php echo $codes_stats['total'] > 0 ? ($codes_stats['expired'] / $codes_stats['total']) * 100 : 0; ?>%; background: #ffb900;"></div>
                        </div>
                    </div>
                    
                    <div class="mini-stat-card">
                        <div class="mini-stat-number" style="color: #666;"><?php echo number_format_i18n($codes_stats['inactive']); ?></div>
                        <div class="mini-stat-label"><?php _e('Neaktivni Kodovi', 'woocommerce-vip-paketi'); ?></div>
                        <div class="mini-stat-bar">
                            <div style="width: <?php echo $codes_stats['total'] > 0 ? ($codes_stats['inactive'] / $codes_stats['total']) * 100 : 0; ?>%; background: #666;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="reports-right-column">
            
            <!-- Recent VIP Activations -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Nedavne VIP Aktivacije', 'woocommerce-vip-paketi'); ?></div>
                
                <?php if (empty($recent_activations)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    <?php _e('Nema nedavnih VIP aktivacija.', 'woocommerce-vip-paketi'); ?>
                </p>
                <?php else: ?>
                <div class="recent-activations-list">
                    <?php foreach (array_slice($recent_activations, 0, 8) as $user): ?>
                    <div class="activation-item" style="display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
                        <div class="user-avatar" style="margin-right: 10px;">
                            <?php echo get_avatar($user->ID, 32); ?>
                        </div>
                        <div class="user-info" style="flex: 1;">
                            <strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong>
                            <br><small style="color: #666;"><?php echo esc_html($user->user_email); ?></small>
                        </div>
                        <div class="activation-date" style="font-size: 11px; color: #999;">
                            <?php echo human_time_diff(strtotime($user->user_registered), current_time('timestamp')) . ' ' . __('ranije', 'woocommerce-vip-paketi'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo admin_url('users.php?role=wvp_vip_member'); ?>" class="button button-secondary">
                        <?php _e('Pogledaj Sve VIP Članove', 'woocommerce-vip-paketi'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Brze Akcije', 'woocommerce-vip-paketi'); ?></div>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="?page=wvp-vip-codes" class="button button-secondary" style="text-align: center;">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Dodaj VIP Kod', 'woocommerce-vip-paketi'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('post-new.php?post_type=wvp_package'); ?>" class="button button-secondary" style="text-align: center;">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('Kreiraj Paket', 'woocommerce-vip-paketi'); ?>
                    </a>
                    
                    <a href="?page=wvp-products" class="button button-secondary" style="text-align: center;">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Podesi Proizvode', 'woocommerce-vip-paketi'); ?>
                    </a>
                    
                    <button type="button" id="export-report" class="button" style="text-align: center;">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Izvezi Izveštaj', 'woocommerce-vip-paketi'); ?>
                    </button>
                </div>
            </div>

            <!-- System Health -->
            <div class="wvp-report-section">
                <div class="wvp-report-title"><?php _e('Zdravlje Sistema', 'woocommerce-vip-paketi'); ?></div>
                
                <div class="health-checks">
                    <div class="health-item">
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <span><?php _e('Plugin Aktivan', 'woocommerce-vip-paketi'); ?></span>
                    </div>
                    
                    <div class="health-item">
                        <?php if (class_exists('WooCommerce')): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <span><?php _e('WooCommerce Kompatibilan', 'woocommerce-vip-paketi'); ?></span>
                        <?php else: ?>
                        <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                        <span><?php _e('WooCommerce Nedostaje', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="health-item">
                        <?php if (class_exists('Woodmart_Theme')): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <span><?php _e('Woodmart Tema Otkrivena', 'woocommerce-vip-paketi'); ?></span>
                        <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                        <span><?php _e('Woodmart Tema Nije Otkrivena', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="health-item">
                        <?php if (wp_next_scheduled('wvp_cleanup_expired_codes')): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <span><?php _e('Zakazani Zadaci Aktivni', 'woocommerce-vip-paketi'); ?></span>
                        <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                        <span><?php _e('Zakazani Zadaci Neaktivni', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Initialize usage chart if data exists
    <?php if (!empty($codes_usage)): ?>
    const ctx = document.getElementById('vip-usage-chart').getContext('2d');
    
    // Process data for chart
    const usageData = <?php echo json_encode($codes_usage); ?>;
    const dates = {};
    
    // Group by date
    usageData.forEach(function(item) {
        const date = item.updated_at.split(' ')[0];
        dates[date] = (dates[date] || 0) + 1;
    });
    
    const chartLabels = Object.keys(dates).sort();
    const chartData = chartLabels.map(date => dates[date]);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: '<?php esc_js(_e('Korišćenih Kodova', 'woocommerce-vip-paketi')); ?>',
                data: chartData,
                borderColor: '#d4a017',
                backgroundColor: 'rgba(212, 160, 23, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>
    
    // Export report functionality
    $('#export-report').on('click', function() {
        const reportData = {
            date_from: '<?php echo esc_js($date_from); ?>',
            date_to: '<?php echo esc_js($date_to); ?>',
            codes_stats: <?php echo json_encode($codes_stats); ?>,
            vip_users: <?php echo $total_vip_users; ?>,
            vip_savings: '<?php echo esc_js($vip_savings_total); ?>',
            top_products: <?php echo json_encode($top_vip_products); ?>
        };
        
        const csvContent = generateCSVReport(reportData);
        downloadCSV(csvContent, 'wvp-report-' + reportData.date_from + '-to-' + reportData.date_to + '.csv');
    });
    
    function generateCSVReport(data) {
        let csv = 'WVP Report - ' + data.date_from + ' to ' + data.date_to + '\\n\\n';
        
        csv += 'Metric,Value\\n';
        csv += 'Total VIP Members,' + data.vip_users + '\\n';
        csv += 'Total Codes,' + data.codes_stats.total + '\\n';
        csv += 'Active Codes,' + data.codes_stats.active + '\\n';
        csv += 'Used Codes,' + data.codes_stats.used + '\\n';
        csv += 'Expired Codes,' + data.codes_stats.expired + '\\n';
        csv += 'Code Usage Rate,' + data.codes_stats.usage_rate + '%\\n';
        csv += 'Total VIP Savings,' + data.vip_savings + '\\n\\n';
        
        csv += 'Top VIP Products\\n';
        csv += 'Product,Regular Price,VIP Price,Savings,Discount %\\n';
        
        data.top_products.forEach(function(product) {
            csv += '"' + product.name + '",' + product.regular_price + ',' + product.vip_price + ',' + product.savings + ',' + product.savings_percent + '%\\n';
        });
        
        return csv;
    }
    
    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
});
</script>

<style>
.mini-stat-card {
    text-align: center;
    padding: 15px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    background: #fafafa;
}

.mini-stat-number {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 5px;
}

.mini-stat-label {
    font-size: 11px;
    color: #666;
    margin-bottom: 8px;
}

.mini-stat-bar {
    height: 4px;
    background: #e1e1e1;
    border-radius: 2px;
    overflow: hidden;
}

.mini-stat-bar div {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 2px;
}

.health-checks {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.health-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.wvp-stat-change {
    font-size: 11px;
    color: #666;
}

.wvp-stat-change.positive {
    color: #46b450;
}

.no-data-message h3 {
    color: #1d2327;
    margin-bottom: 8px;
}

.no-data-message p {
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .wrap .reports-columns {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 782px) {
    .wvp-admin-header form {
        flex-direction: column;
        gap: 10px;
    }
    
    .wvp-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>