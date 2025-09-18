<?php
/**
 * Provide a admin area view for VIP codes management
 */

if (!defined('ABSPATH')) {
    exit;
}

$vip_codes_admin = new WVP_Admin_VIP_Codes();
$codes_stats = $vip_codes_admin->get_codes_statistics();

// Check if database needs update
$db_updater = new WVP_Database_Updater();
$needs_update = $db_updater->check_needs_update();

// Debug: Show table structure
if (isset($_GET['debug']) && $_GET['debug'] === 'table' && current_user_can('manage_options')) {
    echo '<div class="notice notice-info">';
    echo '<h3>Debug: Struktura tabele</h3>';
    global $wpdb;
    $table_info = $db_updater->get_table_info();
    echo '<p><strong>Tabela:</strong> ' . $table_info['table_name'] . '</p>';
    echo '<p><strong>Broj redova:</strong> ' . $table_info['row_count'] . '</p>';
    echo '<p><strong>Verzija baze:</strong> ' . $table_info['version'] . '</p>';
    echo '<h4>Kolone:</h4><ul>';
    foreach ($table_info['columns'] as $column) {
        echo '<li><strong>' . $column->Field . '</strong> (' . $column->Type . ')</li>';
    }
    echo '</ul></div>';
}

// Handle CSV import
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if (!wp_verify_nonce($_POST['wvp_nonce'], 'wvp_admin_nonce')) {
        wp_die(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
    }
    
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
    }
    
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>' . __('Greška pri upload-u fajla.', 'woocommerce-vip-paketi') . '</p></div>';
    } else {
        $file_path = $_FILES['csv_file']['tmp_name'];
        $csv_data = $vip_codes_admin->parse_csv_file($file_path);
        
        if (empty($csv_data)) {
            echo '<div class="notice notice-error"><p>' . __('CSV fajl je prazan ili neispravna.', 'woocommerce-vip-paketi') . '</p></div>';
        } else {
            global $wpdb;
            $db = WVP_Database::get_instance();
            $result = $db->bulk_insert_codes($csv_data);
            
            if ($result && isset($result['inserted'])) {
                echo '<div class="notice notice-success"><p>' . sprintf(
                    __('Uspešno uvezeno %d VIP kodova.', 'woocommerce-vip-paketi'),
                    $result['inserted']
                ) . '</p></div>';
                
                // Refresh stats
                $codes_stats = $vip_codes_admin->get_codes_statistics();
            } else {
                echo '<div class="notice notice-error"><p>' . __('Greška pri uvoz kodova.', 'woocommerce-vip-paketi') . '</p></div>';
            }
        }
    }
}

// Handle bulk actions
if (isset($_GET['action']) || isset($_GET['action2'])) {
    $bulk_action = '';
    if (isset($_GET['action']) && $_GET['action'] != '-1') {
        $bulk_action = $_GET['action'];
    } elseif (isset($_GET['action2']) && $_GET['action2'] != '-1') {
        $bulk_action = $_GET['action2'];
    }
    
    if ($bulk_action === 'delete' && isset($_GET['code_ids']) && is_array($_GET['code_ids'])) {
        // Verify nonce for bulk actions
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'bulk-vip-codes')) {
            wp_die(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
        }
        
        $deleted_count = 0;
        $db = WVP_Database::get_instance();
        
        foreach ($_GET['code_ids'] as $code_id) {
            $code_id = absint($code_id);
            if ($code_id > 0) {
                $result = $db->delete_code($code_id);
                if ($result) {
                    $deleted_count++;
                }
            }
        }
        
        if ($deleted_count > 0) {
            $message = sprintf(
                _n('Obrisano %d kod.', 'Obrisano %d kodova.', $deleted_count, 'woocommerce-vip-paketi'),
                $deleted_count
            );
            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Nijedan kod nije obrisan.', 'woocommerce-vip-paketi') . '</p></div>';
        }
    } elseif ($bulk_action === 'activate' && isset($_GET['code_ids']) && is_array($_GET['code_ids'])) {
        // Verify nonce for bulk actions
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'bulk-vip-codes')) {
            wp_die(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Nemate dozvolu za ovu akciju.', 'woocommerce-vip-paketi'));
        }
        
        $activated_count = 0;
        $db = WVP_Database::get_instance();
        
        foreach ($_GET['code_ids'] as $code_id) {
            $code_id = absint($code_id);
            if ($code_id > 0) {
                $result = $db->update_code($code_id, array('status' => 'active'));
                if ($result) {
                    $activated_count++;
                }
            }
        }
        
        if ($activated_count > 0) {
            $message = sprintf(
                _n('Aktiviran %d kod.', 'Aktivirano %d kodova.', $activated_count, 'woocommerce-vip-paketi'),
                $activated_count
            );
            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Nijedan kod nije aktiviran.', 'woocommerce-vip-paketi') . '</p></div>';
        }
    }
}

// Handle pagination
$per_page_options = array(20, 50, 100);
$per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], $per_page_options) ? absint($_GET['per_page']) : 20;
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($current_page - 1) * $per_page;

// Handle search and filters
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$args = array(
    'limit' => $per_page,
    'offset' => $offset
);

if ($search) {
    $args['search'] = $search;
}

if ($status_filter) {
    $args['status'] = $status_filter;
}

$codes = $vip_codes_admin->get_codes_list($args);
$total_codes = $vip_codes_admin->get_codes_count($args);
$total_pages = ceil($total_codes / $per_page);

// Handle CSV export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && current_user_can('manage_woocommerce')) {
    $vip_codes_admin->export_codes_csv();
    exit;
}

// Handle database update
if (isset($_GET['action']) && $_GET['action'] === 'update_db' && current_user_can('manage_woocommerce')) {
    if (!wp_verify_nonce($_GET['nonce'], 'wvp_update_db')) {
        wp_die(__('Bezbednosna provera neuspešna.', 'woocommerce-vip-paketi'));
    }
    
    $db_updater = new WVP_Database_Updater();
    $result = $db_updater->update_table_structure();
    
    if ($result['success']) {
        echo '<div class="notice notice-success"><p><strong>✓ Baza podataka je uspešno ažurirana!</strong></p>';
        if (!empty($result['added_columns'])) {
            echo '<p>Dodane kolone: ' . implode(', ', $result['added_columns']) . '</p>';
        }
        echo '</div>';
    } else {
        echo '<div class="notice notice-error"><p><strong>Greška pri ažuriranju baze:</strong></p>';
        foreach ($result['errors'] as $error) {
            echo '<p>• ' . esc_html($error) . '</p>';
        }
        echo '</div>';
    }
}




// Handle marking existing code as used
if (isset($_GET['action']) && $_GET['action'] === 'mark_as_used' && isset($_GET['code']) && current_user_can('manage_woocommerce')) {
    $db = WVP_Database::get_instance();
    $code_to_mark = sanitize_text_field($_GET['code']);
    
    $existing = $db->get_code($code_to_mark);
    if ($existing) {
        // Update the code to mark it as used with sample data
        $result = $db->update_code($existing->id, array(
            'status' => 'used',
            'current_uses' => $existing->max_uses, // Set to max uses
            'email' => $existing->email ?: 'test@example.com',
            'first_name' => $existing->first_name ?: 'Test',
            'last_name' => $existing->last_name ?: 'Korisnik',
            'phone' => $existing->phone ?: '+381123456789',
            'address_1' => $existing->address_1 ?: 'Test Adresa 123',
            'city' => $existing->city ?: 'Beograd',
            'postcode' => $existing->postcode ?: '11000',
            'country' => $existing->country ?: 'RS',
            'user_id' => 1 // Link to admin user
        ));
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>Greška pri označavanju koda kao korišćenog: ' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Kod <strong>' . $code_to_mark . '</strong> je označen kao korišćen sa test podacima!</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Kod <strong>' . $code_to_mark . '</strong> nije pronađen u bazi.</p></div>';
    }
}
?>

<div class="wrap wvp-admin-page">
    <div class="wvp-admin-header">
        <h1><?php _e('Upravljanje VIP Kodovima', 'woocommerce-vip-paketi'); ?></h1>
        <p><?php _e('Kreiranje, upravljanje i praćenje VIP kodova za aktivaciju na naplati.', 'woocommerce-vip-paketi'); ?></p>
    </div>

    <?php if ($needs_update): ?>
        <div class="notice notice-warning is-dismissible" style="margin: 20px 0;">
            <p><strong>⚠️ Potrebno je ažuriranje baze podataka!</strong></p>
            <p>Dodana su nova polja za VIP kodove (ime, prezime, adresa, članstvo, itd.). Kliknite na dugme <strong>"⚠️ Ažuriraj Bazu"</strong> ispod da biste dodali nova polja.</p>
            <p><em>Napomena: Ovo je sigurna operacija koja neće obrisati postojeće podatke.</em></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="wvp-stats-grid">
        <div class="wvp-stat-card" data-stat="total">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['total']); ?></div>
            <p class="wvp-stat-label"><?php _e('Ukupno Kodova', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card" data-stat="active">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['active']); ?></div>
            <p class="wvp-stat-label"><?php _e('Aktivni Kodovi', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card" data-stat="used">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['used']); ?></div>
            <p class="wvp-stat-label"><?php _e('Iskorišćeni Kodovi', 'woocommerce-vip-paketi'); ?></p>
        </div>
        <div class="wvp-stat-card" data-stat="expired">
            <div class="wvp-stat-number"><?php echo number_format_i18n($codes_stats['expired']); ?></div>
            <p class="wvp-stat-label"><?php _e('Istekli Kodovi', 'woocommerce-vip-paketi'); ?></p>
        </div>
    </div>


    <div class="wvp-messages"></div>

    <!-- Add New Code Form -->
    <div class="wvp-code-form">
        <h2><?php _e('Dodaj Novi VIP Kod', 'woocommerce-vip-paketi'); ?></h2>
        <form id="wvp-add-code-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="code"><?php _e('VIP Kod', 'woocommerce-vip-paketi'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="code" name="code" required maxlength="50" pattern="[A-Z0-9\-]+" style="text-transform: uppercase;" />
                        <p class="description"><?php _e('Jedinstveni VIP kod (samo slova, brojevi i crtice).', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email"><?php _e('Email', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="email" name="email" />
                        <p class="description"><?php _e('Email adresa korisnika.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 20px 0 10px 0; padding: 10px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                            <?php _e('Podaci o korisniku', 'woocommerce-vip-paketi'); ?>
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="first_name"><?php _e('Ime', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="first_name" name="first_name" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="last_name"><?php _e('Prezime', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="last_name" name="last_name" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="company"><?php _e('Kompanija', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="company" name="company" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="phone"><?php _e('Telefon', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="tel" id="phone" name="phone" />
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 20px 0 10px 0; padding: 10px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                            <?php _e('Adresa', 'woocommerce-vip-paketi'); ?>
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="address_1"><?php _e('Adresa 1', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="address_1" name="address_1" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="address_2"><?php _e('Adresa 2', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="address_2" name="address_2" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="city"><?php _e('Grad', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="city" name="city" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="state"><?php _e('Oblast/Okrug', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="state" name="state" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="postcode"><?php _e('Poštanski broj', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="postcode" name="postcode" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="country"><?php _e('Zemlja', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="country" name="country" value="RS" />
                        <p class="description"><?php _e('Dvoslovni kod zemlje (RS, US, DE, itd.).', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2">
                        <h3 style="margin: 20px 0 10px 0; padding: 10px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                            <?php _e('Članstvo i ograničenja', 'woocommerce-vip-paketi'); ?>
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="membership_expires_at"><?php _e('Članstvo ističe', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="membership_expires_at" name="membership_expires_at" />
                        <p class="description"><?php _e('Kada ističe VIP članstvo.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="auto_renewal"><?php _e('Automatska obnova', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="auto_renewal" name="auto_renewal" value="1" />
                        <p class="description"><?php _e('Omogućiti automatsku obnovu članstva.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="domain"><?php _e('Ograničavanje Domena', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="domain" name="domain" placeholder="example.com" />
                        <p class="description"><?php _e('Opcionalno: Ograniči kod na email adrese sa specifičnog domena.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_uses"><?php _e('Maksimalno Korišćenja', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_uses" name="max_uses" value="1" min="1" max="999" />
                        <p class="description"><?php _e('Koliko puta ovaj kod može biti korišćen.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="expires_at"><?php _e('Datum Isteka', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="expires_at" name="expires_at" />
                        <p class="description"><?php _e('Opcionalno: Kod će isteći nakon ovog datuma i vremena.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="status"><?php _e('Status', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <select id="status" name="status">
                            <option value="active"><?php _e('Aktivni', 'woocommerce-vip-paketi'); ?></option>
                            <option value="inactive"><?php _e('Neaktivni', 'woocommerce-vip-paketi'); ?></option>
                        </select>
                        <p class="description"><?php _e('Podesi status koda.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="submit">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Dodaj VIP Kod', 'woocommerce-vip-paketi'); ?>
                </button>
                <button type="button" id="generate-random-code" class="button">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php _e('Generiši Nasumični Kod', 'woocommerce-vip-paketi'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Import -->
    <div class="wvp-bulk-import">
        <h2><?php _e('Masovni Uvoz VIP Kodova', 'woocommerce-vip-paketi'); ?></h2>
        <div class="wvp-csv-template">
            <p><strong><?php _e('CSV Format:', 'woocommerce-vip-paketi'); ?></strong></p>
            <p class="description"><?php _e('Sva dostupna polja (obavezno polje samo "code"):', 'woocommerce-vip-paketi'); ?></p>
            <code>code,email,domain,first_name,last_name,company,phone,address_1,address_2,city,state,postcode,country,max_uses,membership_expires_at,expires_at,auto_renewal,status</code><br>
            <code>VIP123,user@example.com,example.com,Marko,Petrović,ABC DOO,065123456,Kraljevića Marka 15,stan 12,Beograd,Srbija,11000,RS,5,2025-12-31 23:59:59,2024-12-31 23:59:59,1,active</code><br>
            <code>PREMIUM456,ana@test.com,,Ana,Jovanović,,064987654,Njegoševa 20,,Novi Sad,Vojvodina,21000,RS,10,,,0,active</code>
            <p class="description"><?php _e('Preuzmi primer CSV šablona:', 'woocommerce-vip-paketi'); ?> <a href="#" id="download-csv-template"><?php _e('primer.csv', 'woocommerce-vip-paketi'); ?></a></p>
        </div>
        
        <form id="wvp-bulk-import-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('wvp_admin_nonce', 'wvp_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_file"><?php _e('Odaberi CSV fajl', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" class="regular-text" />
                        <p class="description"><?php _e('Odaberite CSV fajl sa VIP kodovima za uvoz.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="submit">
                <button type="submit" name="import_csv" class="button button-secondary">
                    <span class="dashicons dashicons-database-import"></span>
                    <?php _e('Uvezi Kodove', 'woocommerce-vip-paketi'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Codes Table -->
    <div class="wvp-codes-table">
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get">
                    <input type="hidden" name="page" value="wvp-vip-codes" />
                    <input type="search" id="wvp-codes-search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Pretraži kodove...', 'woocommerce-vip-paketi'); ?>" />
                    
                    <select id="wvp-status-filter" name="status">
                        <option value=""><?php _e('Svi Statusi', 'woocommerce-vip-paketi'); ?></option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Aktivni', 'woocommerce-vip-paketi'); ?></option>
                        <option value="used" <?php selected($status_filter, 'used'); ?>><?php _e('Korišćeni', 'woocommerce-vip-paketi'); ?></option>
                        <option value="expired" <?php selected($status_filter, 'expired'); ?>><?php _e('Istekli', 'woocommerce-vip-paketi'); ?></option>
                        <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('Neaktivni', 'woocommerce-vip-paketi'); ?></option>
                    </select>
                    
                    <button type="submit" class="button"><?php _e('Filtriraj', 'woocommerce-vip-paketi'); ?></button>
                </form>
            </div>
            
            <div class="alignright actions">
                <a href="?page=wvp-vip-codes&action=export_csv" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Izvezi CSV', 'woocommerce-vip-paketi'); ?>
                </a>
                <a href="?page=wvp-vip-codes&action=update_db&nonce=<?php echo wp_create_nonce('wvp_update_db'); ?>" 
                   class="button button-secondary" style="margin-left: 10px; color: #d63384;" 
                   onclick="return confirm('Da li ste sigurni da želite da ažurirate strukturu baze podataka? Ovo će dodati nova polja u tabelu.')">
                    <span class="dashicons dashicons-database"></span>
                    <?php _e('⚠️ Ažuriraj Bazu', 'woocommerce-vip-paketi'); ?>
                </a>
                <a href="?page=wvp-vip-codes&debug=table" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Debug Tabela', 'woocommerce-vip-paketi'); ?>
                </a>
            </div>
        </div>

        <!-- VIP Codes List Form -->
        <form id="wvp-codes-list-form" method="get">
            <input type="hidden" name="page" value="wvp-vip-codes" />
            <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>" />
            <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>" />
            <?php wp_nonce_field('bulk-vip-codes'); ?>
            
        <!-- Top pagination -->
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Izaberi masovnu akciju', 'woocommerce-vip-paketi'); ?></label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Masovne akcije', 'woocommerce-vip-paketi'); ?></option>
                    <option value="delete"><?php _e('Obriši', 'woocommerce-vip-paketi'); ?></option>
                    <option value="activate"><?php _e('Aktiviraj', 'woocommerce-vip-paketi'); ?></option>
                    <option value="deactivate"><?php _e('Deaktiviraj', 'woocommerce-vip-paketi'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Primeni', 'woocommerce-vip-paketi'); ?>" />
            </div>
            
            <div class="alignleft actions">
                <label for="per-page-selector" class="screen-reader-text"><?php _e('Po stranici', 'woocommerce-vip-paketi'); ?></label>
                <select name="per_page" id="per-page-selector" onchange="document.getElementById('wvp-codes-list-form').submit();">
                    <?php foreach ($per_page_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php selected($per_page, $option); ?>>
                            <?php printf(__('%d stavki', 'woocommerce-vip-paketi'), $option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s stavka', '%s stavki', $total_codes, 'woocommerce-vip-paketi'),
                        number_format_i18n($total_codes)
                    ); ?>
                </span>
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'show_all' => false,
                    'end_size' => 1,
                    'mid_size' => 2,
                    'type' => 'plain',
                    'add_args' => array(
                        's' => $search,
                        'status' => $status_filter,
                        'per_page' => $per_page
                    )
                );
                echo paginate_links($pagination_args);
                ?>
            </div>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" />
                    </th>
                    <th scope="col" class="manage-column column-code"><?php _e('Kod', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-user"><?php _e('Korisnik', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-email"><?php _e('Email', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-phone"><?php _e('Telefon', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-address"><?php _e('Adresa', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-membership"><?php _e('Članstvo', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-uses"><?php _e('Korišćenja', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'woocommerce-vip-paketi'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Akcije', 'woocommerce-vip-paketi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($codes)): ?>
                <tr class="no-items">
                    <td colspan="10"><?php _e('Nema pronađenih VIP kodova.', 'woocommerce-vip-paketi'); ?></td>
                </tr>
                <?php else: ?>
                    <?php foreach ($codes as $code): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="code_ids[]" value="<?php echo esc_attr($code->id); ?>" />
                        </th>
                        <td class="code column-code">
                            <strong><?php echo esc_html($code->code); ?></strong>
                            <?php if ($code->domain): ?>
                                <br><small>@<?php echo esc_html($code->domain); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="user column-user">
                            <?php if ($code->first_name || $code->last_name): ?>
                                <strong><?php echo esc_html(trim($code->first_name . ' ' . $code->last_name)); ?></strong>
                                <?php if ($code->company): ?>
                                    <br><small><?php echo esc_html($code->company); ?></small>
                                <?php endif; ?>
                            <?php elseif ($code->user_id): ?>
                                <?php 
                                $user = get_user_by('id', $code->user_id);
                                if ($user): ?>
                                    <strong><?php echo esc_html($user->display_name); ?></strong>
                                    <br><small>ID: <?php echo esc_html($code->user_id); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="email column-email">
                            <?php echo esc_html($code->email ?: '—'); ?>
                        </td>
                        <td class="phone column-phone">
                            <?php echo esc_html($code->phone ?: '—'); ?>
                        </td>
                        <td class="address column-address">
                            <?php if ($code->address_1): ?>
                                <?php echo esc_html($code->address_1); ?>
                                <?php if ($code->address_2): ?>
                                    <br><?php echo esc_html($code->address_2); ?>
                                <?php endif; ?>
                                <?php if ($code->city): ?>
                                    <br><small><?php echo esc_html($code->city); ?>
                                    <?php if ($code->postcode): ?>
                                        <?php echo esc_html($code->postcode); ?>
                                    <?php endif; ?>
                                    <?php if ($code->country): ?>
                                        , <?php echo esc_html($code->country); ?>
                                    <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="membership column-membership">
                            <?php if ($code->membership_expires_at): ?>
                                <strong><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($code->membership_expires_at))); ?></strong>
                                <?php if ($code->auto_renewal): ?>
                                    <br><span class="wvp-auto-renewal">🔄 Auto-obnova</span>
                                <?php endif; ?>
                                <?php if ($code->purchase_count > 0): ?>
                                    <br><small>Kupovina: <?php echo esc_html($code->purchase_count); ?></small>
                                    <br><small>Ukupno: <?php echo wc_price($code->total_spent); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="uses column-uses">
                            <span class="current-uses"><?php echo esc_html($code->current_uses); ?></span>
                            /
                            <span class="max-uses"><?php echo esc_html($code->max_uses); ?></span>
                            <?php if ($code->expires_at): ?>
                                <br><small>Ističe: <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($code->expires_at))); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="status column-status">
                            <span class="wvp-code-status <?php echo esc_attr($code->status); ?>" data-status="<?php echo esc_attr($code->status); ?>">
                                <?php 
                                $status_labels = array(
                                    'active' => 'Aktivni',
                                    'inactive' => 'Neaktivni', 
                                    'used' => 'Korišćen',
                                    'expired' => 'Istekao'
                                );
                                echo esc_html($status_labels[$code->status] ?? ucfirst($code->status)); 
                                ?>
                            </span>
                            <br><small><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($code->created_at))); ?></small>
                        </td>
                        <td class="actions column-actions">
                            <div class="wvp-code-actions">
                                <button type="button" class="button button-small wvp-edit-code" data-code-id="<?php echo esc_attr($code->id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Izmeni', 'woocommerce-vip-paketi'); ?>
                                </button>
                                <button type="button" class="button button-small wvp-delete-code" data-code-id="<?php echo esc_attr($code->id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Obriši', 'woocommerce-vip-paketi'); ?>
                                </button>
                                <?php if ($code->status !== 'used'): ?>
                                    <br><a href="?page=wvp-vip-codes&action=mark_as_used&code=<?php echo urlencode($code->code); ?>" 
                                       class="button button-small" style="background: #dc3545; color: white;" 
                                       onclick="return confirm('Da li ste sigurni da želite da označite ovaj kod kao korišćen?')">
                                        <span class="dashicons dashicons-yes"></span>
                                        Označi kao korišćen
                                    </a>
                                <?php endif; ?>
                                <?php if ($code->user_id): ?>
                                    <br><a href="<?php echo admin_url('user-edit.php?user_id=' . $code->user_id); ?>" class="button button-small" target="_blank">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        Korisnik
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Bottom pagination -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Izaberi masovnu akciju', 'woocommerce-vip-paketi'); ?></label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Masovne akcije', 'woocommerce-vip-paketi'); ?></option>
                    <option value="delete"><?php _e('Obriši', 'woocommerce-vip-paketi'); ?></option>
                    <option value="activate"><?php _e('Aktiviraj', 'woocommerce-vip-paketi'); ?></option>
                    <option value="deactivate"><?php _e('Deaktiviraj', 'woocommerce-vip-paketi'); ?></option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="<?php _e('Primeni', 'woocommerce-vip-paketi'); ?>" />
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s stavka', '%s stavki', $total_codes, 'woocommerce-vip-paketi'),
                        number_format_i18n($total_codes)
                    ); ?>
                </span>
                <?php
                echo paginate_links($pagination_args);
                ?>
            </div>
            <?php endif; ?>
        </div>
        </form> <!-- End VIP Codes List Form -->
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('VIP Codes admin script loading...');
    
    // Generate random VIP code
    $('#generate-random-code').on('click', function(e) {
        e.preventDefault();
        const randomCode = 'VIP' + Math.random().toString(36).substr(2, 8).toUpperCase();
        $('#code').val(randomCode);
        console.log('Generated code:', randomCode);
    });

    // Simple file validation
    $('#csv_file').on('change', function() {
        const file = this.files[0];
        if (file && !file.name.endsWith('.csv')) {
            alert('Molimo odaberite CSV fajl.');
            $(this).val('');
        }
    });

    // Add new VIP code form
    $('#wvp-add-code-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Add code form submitted');
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Dodajem...');
        
        // Check if wvp_admin_ajax is available
        if (typeof wvp_admin_ajax === 'undefined') {
            console.error('wvp_admin_ajax not available');
            alert('AJAX objekat nije dostupan. Molimo osvežite stranicu.');
            $submitBtn.prop('disabled', false).html(originalBtnText);
            return;
        }
        
        const formData = {
            action: 'wvp_add_code',
            nonce: wvp_admin_ajax.nonce,
            code: $('#code').val().toUpperCase(),
            email: $('#email').val(),
            domain: $('#domain').val(),
            max_uses: $('#max_uses').val(),
            expires_at: $('#expires_at').val(),
            status: $('#status').val()
        };
        
        console.log('Sending form data:', formData);
        
        $.post(wvp_admin_ajax.ajax_url, formData)
            .done(function(response) {
                console.log('Add code response:', response);
                if (response.success) {
                    alert('VIP kod je uspešno dodat!');
                    $form[0].reset();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Greška: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Add code error:', status, error);
                alert('Došlo je do greške. Molimo pokušajte ponovo.');
            })
            .always(function() {
                $submitBtn.prop('disabled', false).html(originalBtnText);
            });
    });

    // Download CSV template
    $('#download-csv-template').on('click', function(e) {
        e.preventDefault();
        console.log('Downloading CSV template');
        
        const csvContent = 'code,email,domain,first_name,last_name,company,phone,address_1,address_2,city,state,postcode,country,max_uses,membership_expires_at,expires_at,auto_renewal,status\n' +
                          'VIP123,user@example.com,example.com,Marko,Petrović,ABC DOO,065123456,Kraljevića Marka 15,stan 12,Beograd,Srbija,11000,RS,5,2025-12-31 23:59:59,2024-12-31 23:59:59,1,active\n' +
                          'PREMIUM456,ana@test.com,,Ana,Jovanović,,064987654,Njegoševa 20,,Novi Sad,Vojvodina,21000,RS,10,,,0,active';
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'vip-codes-template.csv';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    });

    console.log('VIP Codes admin script loaded successfully');
});
</script>

<style>
.wvp-bulk-import {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.wvp-bulk-import h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.wvp-csv-template {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 15px;
    margin-bottom: 20px;
}

.wvp-csv-template code {
    display: block;
    margin: 5px 0;
    padding: 5px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 2px;
}

/* Enhanced VIP Codes Table Styles */
.wp-list-table .column-code { 
    width: 120px; 
    font-weight: bold;
}

.wp-list-table .column-user { 
    width: 150px; 
}

.wp-list-table .column-email { 
    width: 130px; 
    word-break: break-all;
}

.wp-list-table .column-phone { 
    width: 110px; 
}

.wp-list-table .column-address { 
    width: 180px; 
    font-size: 12px;
    line-height: 1.4;
}

.wp-list-table .column-membership { 
    width: 140px; 
    font-size: 12px;
    line-height: 1.4;
}

.wp-list-table .column-uses { 
    width: 90px; 
    text-align: center;
}

.wp-list-table .column-status { 
    width: 100px; 
    text-align: center;
}

.wp-list-table .column-actions { 
    width: 120px; 
}

.wvp-code-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.wvp-code-status.active {
    background: #d4edda;
    color: #155724;
}

.wvp-code-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.wvp-code-status.used {
    background: #d1ecf1;
    color: #0c5460;
}

.wvp-code-status.expired {
    background: #f5f5f5;
    color: #6c757d;
}

.wvp-auto-renewal {
    color: #28a745;
    font-size: 10px;
    font-weight: bold;
}

.wvp-code-actions {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.wvp-code-actions .button {
    font-size: 11px;
    height: 24px;
    line-height: 22px;
}

/* Responsive adjustments */
@media screen and (max-width: 1400px) {
    .wp-list-table .column-address {
        width: 140px;
    }
    
    .wp-list-table .column-membership {
        width: 120px;
    }
}

@media screen and (max-width: 1200px) {
    .wp-list-table .column-phone,
    .wp-list-table .column-address {
        display: none;
    }
}

/* Form sections styling */
.form-table h3 {
    margin: 20px 0 10px 0 !important;
}

.wvp-section-header {
    margin: 20px 0 10px 0;
    padding: 10px;
    background: #f1f1f1;
    border-left: 4px solid #0073aa;
}

/* Modal tabs styling */
.wvp-modal-tabs .nav-tab-wrapper {
    border-bottom: 1px solid #ccc;
    margin-bottom: 20px;
}

.wvp-modal-tabs .tab-content {
    display: none;
}

.wvp-modal-tabs .tab-content.active {
    display: block;
}

.wvp-modal-content {
    width: 90%;
    max-width: 800px;
}

.wvp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wvp-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.wvp-modal-content {
    background: white;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.wvp-modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wvp-modal-body {
    padding: 20px;
}

.wvp-modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.wvp-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.wvp-modal-close:hover {
    color: #000;
}
</style>
<!-- Edit Code Modal -->
<div id="wvp-edit-code-modal" class="wvp-modal" style="display: none;">
    <div class="wvp-modal-backdrop"></div>
    <div class="wvp-modal-content">
        <div class="wvp-modal-header">
            <h2><?php _e('Izmeni VIP Kod', 'woocommerce-vip-paketi'); ?></h2>
            <button class="wvp-modal-close">&times;</button>
        </div>
        <div class="wvp-modal-body">
            <form id="wvp-edit-code-form">
                <input type="hidden" id="edit-code-id" name="code_id" />
                <div class="wvp-modal-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#edit-basic" class="nav-tab nav-tab-active">Osnovni podaci</a>
                        <a href="#edit-user-info" class="nav-tab">Korisnik</a>
                        <a href="#edit-address" class="nav-tab">Adresa</a>
                        <a href="#edit-membership" class="nav-tab">Članstvo</a>
                    </nav>
                    
                    <!-- Basic tab -->
                    <div id="edit-basic" class="tab-content active">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit-code">VIP Kod</label></th>
                                <td><input type="text" id="edit-code" name="code" required maxlength="50" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-email">Email</label></th>
                                <td><input type="email" id="edit-email" name="email" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-domain">Domain ograničenje</label></th>
                                <td><input type="text" id="edit-domain" name="domain" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-max-uses">Maksimalno korišćenja</label></th>
                                <td><input type="number" id="edit-max-uses" name="max_uses" min="1" max="999" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-status">Status</label></th>
                                <td>
                                    <select id="edit-status" name="status">
                                        <option value="active">Aktivni</option>
                                        <option value="inactive">Neaktivni</option>
                                        <option value="expired">Istekli</option>
                                        <option value="used">Korišćeni</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- User info tab -->
                    <div id="edit-user-info" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit-first-name">Ime</label></th>
                                <td><input type="text" id="edit-first-name" name="first_name" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-last-name">Prezime</label></th>
                                <td><input type="text" id="edit-last-name" name="last_name" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-company">Kompanija</label></th>
                                <td><input type="text" id="edit-company" name="company" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-phone">Telefon</label></th>
                                <td><input type="tel" id="edit-phone" name="phone" /></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Address tab -->
                    <div id="edit-address" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit-address-1">Adresa 1</label></th>
                                <td><input type="text" id="edit-address-1" name="address_1" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-address-2">Adresa 2</label></th>
                                <td><input type="text" id="edit-address-2" name="address_2" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-city">Grad</label></th>
                                <td><input type="text" id="edit-city" name="city" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-state">Oblast</label></th>
                                <td><input type="text" id="edit-state" name="state" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-postcode">Poštanski broj</label></th>
                                <td><input type="text" id="edit-postcode" name="postcode" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-country">Zemlja</label></th>
                                <td><input type="text" id="edit-country" name="country" /></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Membership tab -->
                    <div id="edit-membership" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit-membership-expires">Članstvo ističe</label></th>
                                <td><input type="datetime-local" id="edit-membership-expires" name="membership_expires_at" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-auto-renewal">Auto-obnova</label></th>
                                <td><input type="checkbox" id="edit-auto-renewal" name="auto_renewal" value="1" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-purchase-count">Broj kupovina</label></th>
                                <td><input type="number" id="edit-purchase-count" name="purchase_count" min="0" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-total-spent">Ukupno potrošeno</label></th>
                                <td><input type="number" id="edit-total-spent" name="total_spent" step="0.01" min="0" /></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="wvp-modal-footer">
                    <button type="submit" class="button button-primary">
                        <?php _e('Sačuvaj Izmene', 'woocommerce-vip-paketi'); ?>
                    </button>
                    <button type="button" class="button wvp-modal-close">
                        <?php _e('Otkaži', 'woocommerce-vip-paketi'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('WVP Admin VIP Codes loaded');
    
    // Basic tab switching
    $('.wvp-modal-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('href');
        $('.wvp-modal-tabs .nav-tab').removeClass('nav-tab-active');
        $('.wvp-modal-tabs .tab-content').removeClass('active');
        $(this).addClass('nav-tab-active');
        $(targetTab).addClass('active');
    });
    
    // Show modal on edit click and load code data
    $('.wvp-edit-code').on('click', function() {
        const codeId = $(this).data('code-id');
        console.log('Edit code clicked for ID:', codeId);
        
        if (!codeId) {
            alert('Greška: ID koda nije valjan');
            return;
        }
        
        // Show modal first
        $('#wvp-edit-code-modal').show();
        
        // Load code data via AJAX
        loadCodeData(codeId);
    });
    
    // Load code data from database
    function loadCodeData(codeId) {
        console.log('Loading code data for ID:', codeId);
        
        // Check if AJAX object exists
        if (typeof wvp_admin_ajax === 'undefined') {
            console.error('wvp_admin_ajax not available');
            alert('AJAX objekat nije dostupan. Molimo osvežite stranicu.');
            return;
        }
        
        $.post(wvp_admin_ajax.ajax_url, {
            action: 'wvp_get_code_data',
            code_id: codeId,
            nonce: wvp_admin_ajax.nonce
        })
        .done(function(response) {
            console.log('Code data loaded:', response);
            
            if (response.success && response.data) {
                populateEditModal(response.data);
            } else {
                console.error('Error loading code data:', response.data);
                alert('Greška pri učitavanju podataka: ' + (response.data || 'Nepoznata greška'));
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading code data:', status, error);
            alert('Došlo je do greške prilikom učitavanja podataka.');
        });
    }
    
    // Populate edit modal with code data
    function populateEditModal(codeData) {
        console.log('Populating modal with data:', codeData);
        
        // Set code ID for form submission
        $('#edit-code-id').val(codeData.id);
        
        // Basic fields
        $('#edit-code').val(codeData.code || '');
        $('#edit-email').val(codeData.email || '');
        $('#edit-domain').val(codeData.domain || '');
        $('#edit-max-uses').val(codeData.max_uses || 1);
        $('#edit-status').val(codeData.status || 'active');
        
        // User info fields
        $('#edit-first-name').val(codeData.first_name || '');
        $('#edit-last-name').val(codeData.last_name || '');
        $('#edit-company').val(codeData.company || '');
        $('#edit-phone').val(codeData.phone || '');
        
        // Address fields
        $('#edit-address-1').val(codeData.address_1 || '');
        $('#edit-address-2').val(codeData.address_2 || '');
        $('#edit-city').val(codeData.city || '');
        $('#edit-state').val(codeData.state || '');
        $('#edit-postcode').val(codeData.postcode || '');
        $('#edit-country').val(codeData.country || '');
        
        // Membership fields
        if (codeData.membership_expires_at) {
            // Convert MySQL datetime to datetime-local format
            const membershipDate = new Date(codeData.membership_expires_at);
            const formattedDate = membershipDate.toISOString().slice(0, 16);
            $('#edit-membership-expires').val(formattedDate);
        } else {
            $('#edit-membership-expires').val('');
        }
        
        $('#edit-auto-renewal').prop('checked', codeData.auto_renewal == 1);
        $('#edit-purchase-count').val(codeData.purchase_count || 0);
        $('#edit-total-spent').val(codeData.total_spent || 0);
        
        console.log('Modal populated successfully');
    }
    
    // Handle edit form submission
    $('#wvp-edit-code-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Edit form submitted');
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.text();
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Čuvam...');
        
        // Prepare form data
        const formData = {
            action: 'wvp_update_code',
            nonce: wvp_admin_ajax.nonce,
            code_id: $('#edit-code-id').val(),
            code: $('#edit-code').val(),
            email: $('#edit-email').val(),
            domain: $('#edit-domain').val(),
            max_uses: $('#edit-max-uses').val(),
            status: $('#edit-status').val(),
            first_name: $('#edit-first-name').val(),
            last_name: $('#edit-last-name').val(),
            company: $('#edit-company').val(),
            phone: $('#edit-phone').val(),
            address_1: $('#edit-address-1').val(),
            address_2: $('#edit-address-2').val(),
            city: $('#edit-city').val(),
            state: $('#edit-state').val(),
            postcode: $('#edit-postcode').val(),
            country: $('#edit-country').val(),
            membership_expires_at: $('#edit-membership-expires').val(),
            auto_renewal: $('#edit-auto-renewal').is(':checked') ? 1 : 0,
            purchase_count: $('#edit-purchase-count').val(),
            total_spent: $('#edit-total-spent').val()
        };
        
        console.log('Sending update data:', formData);
        
        $.post(wvp_admin_ajax.ajax_url, formData)
            .done(function(response) {
                console.log('Update response:', response);
                
                if (response.success) {
                    alert('VIP kod je uspešno ažuriran!');
                    $('#wvp-edit-code-modal').hide();
                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Greška: ' + (response.data || 'Nepoznata greška'));
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Update error:', status, error);
                alert('Došlo je do greške prilikom ažuriranja.');
            })
            .always(function() {
                $submitBtn.prop('disabled', false).text(originalBtnText);
            });
    });
    
    // Hide modal
    $('.wvp-modal-close, .wvp-modal-backdrop').on('click', function() {
        $('#wvp-edit-code-modal').hide();
    });
    
    // Delete code functionality
    $('.wvp-delete-code').on('click', function() {
        const codeId = $(this).data('code-id');
        
        if (!codeId) {
            alert('Greška: ID koda nije valjan');
            return;
        }
        
        if (!confirm('Da li ste sigurni da želite da obrišete ovaj VIP kod?')) {
            return;
        }
        
        console.log('Deleting code ID:', codeId);
        
        $.post(wvp_admin_ajax.ajax_url, {
            action: 'wvp_delete_code',
            code_id: codeId,
            nonce: wvp_admin_ajax.nonce
        })
        .done(function(response) {
            console.log('Delete response:', response);
            
            if (response.success) {
                alert('VIP kod je uspešno obrisan!');
                location.reload();
            } else {
                alert('Greška: ' + (response.data || 'Nepoznata greška'));
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Delete error:', status, error);
            alert('Došlo je do greške prilikom brisanja.');
        });
    });
});
</script>

