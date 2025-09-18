<?php
/**
 * Provide a admin area view for packages management
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if custom post type exists
if (!post_type_exists('wvp_package')) {
    ?>
    <div class="wrap">
        <h1><?php _e('Upravljanje Paketima', 'woocommerce-vip-paketi'); ?></h1>
        <div class="notice notice-error">
            <p><?php _e('Package post type nije registrovan. Molimo aktiviraj plugin pravilno.', 'woocommerce-vip-paketi'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Get packages
$packages = get_posts(array(
    'post_type' => 'wvp_package',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC'
));

// Ensure packages is an array
if (!is_array($packages)) {
    $packages = array();
}

// Get package stats
$total_packages = count($packages);
$active_packages = 0;
$draft_packages = 0;

// Only calculate stats if we have packages
if (!empty($packages)) {
    $active_packages = count(array_filter($packages, function($p) {
        return get_post_meta($p->ID, '_wvp_package_status', true) === 'active';
    }));
    $draft_packages = count(array_filter($packages, function($p) {
        return get_post_meta($p->ID, '_wvp_package_status', true) === 'draft';
    }));
}

// Handle bulk actions
if (isset($_POST['action']) && $_POST['action'] !== -1 && isset($_POST['package_ids'])) {
    $action = sanitize_text_field($_POST['action']);
    $package_ids = array_map('intval', $_POST['package_ids']);
    
    switch ($action) {
        case 'activate':
            foreach ($package_ids as $id) {
                update_post_meta($id, '_wvp_package_status', 'active');
            }
            echo '<div class="notice notice-success"><p>' . sprintf(__('Aktivirano %d paketa.', 'woocommerce-vip-paketi'), count($package_ids)) . '</p></div>';
            break;
        case 'deactivate':
            foreach ($package_ids as $id) {
                update_post_meta($id, '_wvp_package_status', 'inactive');
            }
            echo '<div class="notice notice-success"><p>' . sprintf(__('Deaktivirano %d paketa.', 'woocommerce-vip-paketi'), count($package_ids)) . '</p></div>';
            break;
        case 'delete':
            foreach ($package_ids as $id) {
                wp_delete_post($id, true);
            }
            echo '<div class="notice notice-success"><p>' . sprintf(__('Obrisano %d paketa.', 'woocommerce-vip-paketi'), count($package_ids)) . '</p></div>';
            // Refresh packages list
            $packages = get_posts(array(
                'post_type' => 'wvp_package',
                'posts_per_page' => -1,
                'post_status' => 'any'
            ));
            break;
    }
}
?>

<div class="wrap wvp-admin-page">
    <div class="wvp-admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><?php _e('Upravljanje VIP Paketima', 'woocommerce-vip-paketi'); ?></h1>
                <p><?php _e('Kreiranje i upravljanje variabilnim paketima sa VIP popust cenama.', 'woocommerce-vip-paketi'); ?></p>
            </div>
            <div>
                <a href="<?php echo admin_url('post-new.php?post_type=wvp_package'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Dodaj Novi Paket', 'woocommerce-vip-paketi'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="wvp-stats-grid">
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($total_packages); ?></div>
            <p class="wvp-stat-label"><?php _e('Ukupno Paketa', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($active_packages); ?></div>
            <p class="wvp-stat-label"><?php _e('Aktivni Paketi', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php echo number_format_i18n($draft_packages); ?></div>
            <p class="wvp-stat-label"><?php _e('Nacrti Paketa', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card">
            <div class="wvp-stat-number"><?php 
                $allowed_products_count = get_option('wvp_package_allowed_products', array());
                echo number_format_i18n(is_array($allowed_products_count) ? count($allowed_products_count) : 0); 
            ?></div>
            <p class="wvp-stat-label"><?php _e('Dozvoljeni Proizvodi', 'woocommerce-vip-paketi'); ?></p>
        </div>
    </div>

    <?php if (empty($packages)): ?>
    <!-- Empty State -->
    <div class="wvp-empty-state" style="text-align: center; padding: 60px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
        <div style="font-size: 48px; margin-bottom: 20px;">📦</div>
        <h2><?php _e('Još nema kreiranih paketa', 'woocommerce-vip-paketi'); ?></h2>
        <p><?php _e('Kreiraj svoj prvi VIP paket da počneš da nudis paket proizvode sa popustom svojim kupcima.', 'woocommerce-vip-paketi'); ?></p>
        <p>
            <a href="<?php echo admin_url('post-new.php?post_type=wvp_package'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Kreiraj Svoj Prvi Paket', 'woocommerce-vip-paketi'); ?>
            </a>
        </p>
        <p>
            <a href="?page=wvp-products" class="button button-secondary">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Podesi Prvo Podešavanja Proizvoda', 'woocommerce-vip-paketi'); ?>
            </a>
        </p>
    </div>
    
    <?php else: ?>
    <!-- Packages Table -->
    <form method="post" id="packages-form">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Izaberi grupnu akciju', 'woocommerce-vip-paketi'); ?></label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Grupne Akcije', 'woocommerce-vip-paketi'); ?></option>
                    <option value="activate"><?php _e('Aktiviraj', 'woocommerce-vip-paketi'); ?></option>
                    <option value="deactivate"><?php _e('Deaktiviraj', 'woocommerce-vip-paketi'); ?></option>
                    <option value="delete"><?php _e('Obriši', 'woocommerce-vip-paketi'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Primeni', 'woocommerce-vip-paketi'); ?>" />
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" />
                    </td>
                    <th scope="col" class="manage-column column-title"><?php _e('Naziv Paketa', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-sizes"><?php _e('Veličine', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-products"><?php _e('Proizvodi', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-discounts"><?php _e('Popusti', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php _e('Kreiran', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Akcije', 'woocommerce-vip-paketi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $package): 
                    $package_status = get_post_meta($package->ID, '_wvp_package_status', true) ?: 'draft';
                    $package_sizes = get_post_meta($package->ID, '_wvp_package_sizes', true) ?: array();
                    $allowed_products = get_post_meta($package->ID, '_wvp_allowed_products', true) ?: array();
                    $regular_discounts = get_post_meta($package->ID, '_wvp_regular_discounts', true) ?: array();
                    $vip_discounts = get_post_meta($package->ID, '_wvp_vip_discounts', true) ?: array();
                    $show_for_non_vip = get_post_meta($package->ID, '_wvp_show_for_non_vip', true) === 'yes';
                ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="package_ids[]" value="<?php echo esc_attr($package->ID); ?>" />
                    </th>
                    <td class="title column-title">
                        <strong>
                            <a href="<?php echo get_edit_post_link($package->ID); ?>"><?php echo esc_html($package->post_title); ?></a>
                        </strong>
                        <?php if (!$show_for_non_vip): ?>
                            <br><small style="color: #d4a017;"><?php _e('Samo VIP', 'woocommerce-vip-paketi'); ?></small>
                        <?php endif; ?>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php echo get_edit_post_link($package->ID); ?>"><?php _e('Izmeni', 'woocommerce-vip-paketi'); ?></a> |
                            </span>
                            <span class="view">
                                <a href="<?php echo get_permalink($package->ID); ?>" target="_blank"><?php _e('Prikaži', 'woocommerce-vip-paketi'); ?></a> |
                            </span>
                            <span class="trash">
                                <a href="<?php echo get_delete_post_link($package->ID); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Da li ste sigurni da želite da obrišete ovaj paket?', 'woocommerce-vip-paketi'); ?>')"><?php _e('Obriši', 'woocommerce-vip-paketi'); ?></a>
                            </span>
                        </div>
                    </td>
                    <td class="sizes column-sizes">
                        <?php if (!empty($package_sizes)): ?>
                            <div class="wvp-package-sizes">
                                <?php foreach ($package_sizes as $size): ?>
                                    <span class="wvp-package-size-badge"><?php echo esc_html($size); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="description"><?php _e('Nije podešeno', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="products column-products">
                        <strong><?php echo count($allowed_products); ?></strong>
                        <?php _e('proizvoda', 'woocommerce-vip-paketi'); ?>
                        <?php if (count($allowed_products) > 0): ?>
                            <br><small>
                                <?php 
                                $product_names = array();
                                foreach (array_slice($allowed_products, 0, 3) as $product_id) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        $product_names[] = $product->get_name();
                                    }
                                }
                                echo esc_html(implode(', ', $product_names));
                                if (count($allowed_products) > 3) {
                                    echo '...';
                                }
                                ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="discounts column-discounts">
                        <?php if (!empty($regular_discounts) || !empty($vip_discounts)): ?>
                            <div class="discount-preview">
                                <?php if (!empty($regular_discounts)): ?>
                                    <div class="regular-discount">
                                        <strong><?php _e('Regularni:', 'woocommerce-vip-paketi'); ?></strong>
                                        <?php echo implode('%, ', array_values($regular_discounts)) . '%'; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($vip_discounts)): ?>
                                    <div class="vip-discount" style="color: #d4a017;">
                                        <strong><?php _e('VIP:', 'woocommerce-vip-paketi'); ?></strong>
                                        +<?php echo implode('%, +', array_values($vip_discounts)) . '%'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="description"><?php _e('Bez popusta', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="status column-status">
                        <span class="wvp-package-status wvp-status-<?php echo esc_attr($package_status); ?>">
                            <?php 
                            switch ($package_status) {
                                case 'active':
                                    echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Aktivan', 'woocommerce-vip-paketi');
                                    break;
                                case 'inactive':
                                    echo '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('Neaktivan', 'woocommerce-vip-paketi');
                                    break;
                                case 'draft':
                                default:
                                    echo '<span class="dashicons dashicons-edit" style="color: #72aee6;"></span> ' . __('Nacrt', 'woocommerce-vip-paketi');
                                    break;
                            }
                            ?>
                        </span>
                    </td>
                    <td class="date column-date">
                        <?php echo get_the_date('M j, Y', $package->ID); ?>
                        <br><small><?php echo get_the_date('g:i a', $package->ID); ?></small>
                    </td>
                    <td class="actions column-actions">
                        <div class="package-actions">
                            <?php if ($package_status !== 'active'): ?>
                                <button type="button" class="button button-small wvp-activate-package" data-package-id="<?php echo esc_attr($package->ID); ?>">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Aktiviraj', 'woocommerce-vip-paketi'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="button button-small wvp-deactivate-package" data-package-id="<?php echo esc_attr($package->ID); ?>">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Deaktiviraj', 'woocommerce-vip-paketi'); ?>
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo get_edit_post_link($package->ID); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Uredi', 'woocommerce-vip-paketi'); ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    <?php endif; ?>

    <!-- Quick Setup Guide -->
    <div class="wvp-setup-guide" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-top: 20px;">
        <h3><?php _e('Vodič za Podešavanje Paketa', 'woocommerce-vip-paketi'); ?></h3>
        <div class="setup-steps" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="step">
                <div class="step-number" style="background: #007cba; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px;">1</div>
                <h4><?php _e('Podesi Proizvode', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Prvo, uključi proizvode za korišćenje u paketima u Podešavanjima proizvoda.', 'woocommerce-vip-paketi'); ?></p>
                <a href="?page=wvp-products" class="button button-secondary"><?php _e('Podešavanja Proizvoda', 'woocommerce-vip-paketi'); ?></a>
            </div>
            <div class="step">
                <div class="step-number" style="background: #007cba; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px;">2</div>
                <h4><?php _e('Kreiraj Paket', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Kreiraj novi paket sa veličinama, proizvodima i pravilima za popust.', 'woocommerce-vip-paketi'); ?></p>
                <a href="<?php echo admin_url('post-new.php?post_type=wvp_package'); ?>" class="button button-primary"><?php _e('Dodaj Novi Paket', 'woocommerce-vip-paketi'); ?></a>
            </div>
            <div class="step">
                <div class="step-number" style="background: #007cba; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px;">3</div>
                <h4><?php _e('Podesi VIP Cene', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Podesi pojedinačne VIP cene proizvoda i VIP popuste za pakete.', 'woocommerce-vip-paketi'); ?></p>
                <a href="?page=wvp-settings&tab=vip" class="button button-secondary"><?php _e('VIP Podešavanja', 'woocommerce-vip-paketi'); ?></a>
            </div>
            <div class="step">
                <div class="step-number" style="background: #007cba; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px;">4</div>
                <h4><?php _e('Testiraj i Pokreni', 'woocommerce-vip-paketi'); ?></h4>
                <p><?php _e('Testiraj svoje pakete sa VIP kodovima i pokreni za kupce.', 'woocommerce-vip-paketi'); ?></p>
                <a href="?page=wvp-vip-codes" class="button button-secondary"><?php _e('VIP Kodovi', 'woocommerce-vip-paketi'); ?></a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Individual package activation/deactivation
    $('.wvp-activate-package, .wvp-deactivate-package').on('click', function() {
        const $button = $(this);
        const packageId = $button.data('package-id');
        const action = $button.hasClass('wvp-activate-package') ? 'activate' : 'deactivate';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wvp_toggle_package_status',
                package_id: packageId,
                status: action === 'activate' ? 'active' : 'inactive',
                nonce: '<?php echo wp_create_nonce("wvp_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // Bulk actions confirmation
    $('#packages-form').on('submit', function(e) {
        const action = $('#bulk-action-selector-top').val();
        const selected = $('input[name="package_ids[]"]:checked').length;
        
        if (action === 'delete' && selected > 0) {
            if (!confirm('<?php echo esc_js(__('Da li ste sigurni da želite da obrišete izabrane pakete? Ovo ne može biti poništeno.', 'woocommerce-vip-paketi')); ?>')) {
                e.preventDefault();
                return false;
            }
        }
        
        if (action === '-1') {
            e.preventDefault();
            alert('<?php echo esc_js(__('Molimo izaberite grupnu akciju.', 'woocommerce-vip-paketi')); ?>');
            return false;
        }
        
        if (selected === 0) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Molimo izaberite najmanje jedan paket.', 'woocommerce-vip-paketi')); ?>');
            return false;
        }
    });
});
</script>

<style>
.wvp-package-size-badge {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    padding: 2px 6px;
    font-size: 11px;
    margin-right: 4px;
    margin-bottom: 2px;
    display: inline-block;
}

.discount-preview {
    font-size: 12px;
}

.discount-preview .regular-discount,
.discount-preview .vip-discount {
    margin-bottom: 2px;
}

.package-actions {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.package-actions .button {
    font-size: 11px;
    padding: 2px 6px;
    height: auto;
    line-height: 1.4;
}

.wvp-package-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

@media (max-width: 782px) {
    .package-actions {
        flex-direction: column;
    }
    
    .setup-steps {
        grid-template-columns: 1fr;
    }
}
</style>