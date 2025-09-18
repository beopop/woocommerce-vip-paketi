<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Database_Updater {
    
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'wvp_codes';
    }

    public function update_table_structure() {
        $result = array(
            'success' => true,
            'added_columns' => array(),
            'errors' => array()
        );

        // Check if table exists
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name;
        
        if (!$table_exists) {
            $result['success'] = false;
            $result['errors'][] = "Tabela {$this->table_name} ne postoji. Molimo aktivirajte plugin ponovo.";
            return $result;
        }

        // Get current table structure
        $current_columns = $this->get_current_columns();
        
        // Define new columns to add
        $new_columns = array(
            'first_name' => array(
                'sql' => "ADD COLUMN first_name varchar(100) DEFAULT NULL AFTER domain",
                'description' => 'Ime korisnika'
            ),
            'last_name' => array(
                'sql' => "ADD COLUMN last_name varchar(100) DEFAULT NULL AFTER first_name",
                'description' => 'Prezime korisnika'
            ),
            'company' => array(
                'sql' => "ADD COLUMN company varchar(100) DEFAULT NULL AFTER last_name",
                'description' => 'Kompanija'
            ),
            'address_1' => array(
                'sql' => "ADD COLUMN address_1 varchar(200) DEFAULT NULL AFTER company",
                'description' => 'Adresa 1'
            ),
            'address_2' => array(
                'sql' => "ADD COLUMN address_2 varchar(200) DEFAULT NULL AFTER address_1",
                'description' => 'Adresa 2'
            ),
            'city' => array(
                'sql' => "ADD COLUMN city varchar(100) DEFAULT NULL AFTER address_2",
                'description' => 'Grad'
            ),
            'state' => array(
                'sql' => "ADD COLUMN state varchar(100) DEFAULT NULL AFTER city",
                'description' => 'Oblast/Okrug'
            ),
            'postcode' => array(
                'sql' => "ADD COLUMN postcode varchar(20) DEFAULT NULL AFTER state",
                'description' => 'Poštanski broj'
            ),
            'country' => array(
                'sql' => "ADD COLUMN country varchar(10) DEFAULT NULL AFTER postcode",
                'description' => 'Zemlja'
            ),
            'phone' => array(
                'sql' => "ADD COLUMN phone varchar(50) DEFAULT NULL AFTER country",
                'description' => 'Telefon'
            ),
            'user_id' => array(
                'sql' => "ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER phone",
                'description' => 'ID korisnika'
            ),
            'purchase_count' => array(
                'sql' => "ADD COLUMN purchase_count int(11) NOT NULL DEFAULT 0 AFTER user_id",
                'description' => 'Broj kupovina'
            ),
            'total_spent' => array(
                'sql' => "ADD COLUMN total_spent decimal(10,2) NOT NULL DEFAULT 0.00 AFTER purchase_count",
                'description' => 'Ukupno potrošeno'
            ),
            'last_purchase_date' => array(
                'sql' => "ADD COLUMN last_purchase_date datetime DEFAULT NULL AFTER total_spent",
                'description' => 'Datum poslednje kupovine'
            ),
            'membership_expires_at' => array(
                'sql' => "ADD COLUMN membership_expires_at datetime DEFAULT NULL AFTER last_purchase_date",
                'description' => 'Datum isteka članstva'
            ),
            'auto_renewal' => array(
                'sql' => "ADD COLUMN auto_renewal tinyint(1) NOT NULL DEFAULT 0 AFTER membership_expires_at",
                'description' => 'Automatska obnova'
            ),
            'last_warning_sent' => array(
                'sql' => "ADD COLUMN last_warning_sent datetime DEFAULT NULL AFTER auto_renewal",
                'description' => 'Datum poslednjeg upozorenja'
            ),
            'used_count' => array(
                'sql' => "ADD COLUMN used_count int(11) NOT NULL DEFAULT 0 AFTER last_warning_sent",
                'description' => 'Broj korišćenja za auto-generirane kodove'
            ),
            'created_at' => array(
                'sql' => "ADD COLUMN created_at datetime DEFAULT NULL AFTER used_count",
                'description' => 'Datum kreiranja'
            ),
            'updated_at' => array(
                'sql' => "ADD COLUMN updated_at datetime DEFAULT NULL AFTER created_at",
                'description' => 'Datum poslednje izmene'
            )
        );

        // Add missing columns
        foreach ($new_columns as $column_name => $column_info) {
            if (!in_array($column_name, $current_columns)) {
                $sql = "ALTER TABLE {$this->table_name} {$column_info['sql']}";
                $alter_result = $this->wpdb->query($sql);
                
                if ($alter_result === false) {
                    $result['errors'][] = "Greška pri dodavanju kolone '{$column_name}': " . $this->wpdb->last_error;
                    $result['success'] = false;
                } else {
                    $result['added_columns'][] = $column_name . ' (' . $column_info['description'] . ')';
                }
            }
        }

        // Add indexes for better performance
        $indexes_to_add = array(
            'idx_user_id' => "ADD KEY idx_user_id (user_id)",
            'idx_membership_expires' => "ADD KEY idx_membership_expires (membership_expires_at)",
            'idx_last_purchase' => "ADD KEY idx_last_purchase (last_purchase_date)",
            'idx_auto_renewal' => "ADD KEY idx_auto_renewal (auto_renewal)"
        );

        $current_indexes = $this->get_current_indexes();

        foreach ($indexes_to_add as $index_name => $index_sql) {
            if (!in_array($index_name, $current_indexes)) {
                $sql = "ALTER TABLE {$this->table_name} {$index_sql}";
                $index_result = $this->wpdb->query($sql);
                
                if ($index_result === false) {
                    // Index errors are not critical, so just log them
                    error_log("WVP: Failed to add index '{$index_name}': " . $this->wpdb->last_error);
                }
            }
        }

        // Update version to mark that database is updated
        if ($result['success']) {
            update_option('wvp_db_version', '2.1');
            update_option('wvp_needs_db_update', false);
        }

        return $result;
    }

    private function get_current_columns() {
        $columns = $this->wpdb->get_results("DESCRIBE {$this->table_name}");
        $column_names = array();
        
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        return $column_names;
    }

    private function get_current_indexes() {
        $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$this->table_name}");
        $index_names = array();
        
        foreach ($indexes as $index) {
            if (!in_array($index->Key_name, $index_names) && $index->Key_name !== 'PRIMARY') {
                $index_names[] = $index->Key_name;
            }
        }
        
        return $index_names;
    }

    public function check_needs_update() {
        $current_version = get_option('wvp_db_version', '1.0');
        return version_compare($current_version, '2.1', '<');
    }

    public function get_table_info() {
        $columns = $this->wpdb->get_results("DESCRIBE {$this->table_name}");
        $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$this->table_name}");
        $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        return array(
            'table_name' => $this->table_name,
            'columns' => $columns,
            'indexes' => $indexes,
            'row_count' => $row_count,
            'version' => get_option('wvp_db_version', '1.0')
        );
    }
}