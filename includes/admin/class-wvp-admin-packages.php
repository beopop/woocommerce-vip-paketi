<?php

if (!defined('ABSPATH')) {
    exit;
}

class WVP_Admin_Packages {

    public function __construct() {
        add_action('init', array($this, 'register_package_post_type'));
        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
    }

    public function maybe_flush_rewrite_rules() {
        // Flush rewrite rules if needed (when permalink structure changes or plugin is updated)
        if (get_option('wvp_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules(false);
            delete_option('wvp_flush_rewrite_rules');
        }
    }

    public function register_package_post_type() {
        $labels = array(
            'name'                  => _x('VIP Paketi', 'Post Type General Name', 'woocommerce-vip-paketi'),
            'singular_name'         => _x('VIP Paket', 'Post Type Singular Name', 'woocommerce-vip-paketi'),
            'menu_name'             => __('VIP Paketi', 'woocommerce-vip-paketi'),
            'name_admin_bar'        => __('VIP Paket', 'woocommerce-vip-paketi'),
            'archives'              => __('Arhiva Paketa', 'woocommerce-vip-paketi'),
            'attributes'            => __('Atributi Paketa', 'woocommerce-vip-paketi'),
            'parent_item_colon'     => __('Roditeljski Paket:', 'woocommerce-vip-paketi'),
            'all_items'             => __('Svi Paketi', 'woocommerce-vip-paketi'),
            'add_new_item'          => __('Dodaj Novi Paket', 'woocommerce-vip-paketi'),
            'add_new'               => __('Dodaj Novi', 'woocommerce-vip-paketi'),
            'new_item'              => __('Novi Paket', 'woocommerce-vip-paketi'),
            'edit_item'             => __('Uredi Paket', 'woocommerce-vip-paketi'),
            'update_item'           => __('Ažuriraj Paket', 'woocommerce-vip-paketi'),
            'view_item'             => __('Pogledaj Paket', 'woocommerce-vip-paketi'),
            'view_items'            => __('Pogledaj Pakete', 'woocommerce-vip-paketi'),
            'search_items'          => __('Pretraži Pakete', 'woocommerce-vip-paketi'),
        );

        $args = array(
            'label'                 => __('VIP Paket', 'woocommerce-vip-paketi'),
            'description'           => __('VIP varijabilni paketi', 'woocommerce-vip-paketi'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'rewrite'               => array('slug' => get_option('wvp_package_url_slug', 'konfiguracija-paketa')),
        );

        register_post_type('wvp_package', $args);
        
        // Check if we need to flush rewrite rules
        if (get_option('wvp_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules();
            delete_option('wvp_flush_rewrite_rules');
        }
    }


    public function add_package_meta_boxes() {
        add_meta_box(
            'wvp_package_configuration',
            __('Konfiguracija Paketa', 'woocommerce-vip-paketi'),
            array($this, 'package_configuration_meta_box'),
            'wvp_package',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wvp_package_seo',
            __('SEO Podešavanja', 'woocommerce-vip-paketi'),
            array($this, 'package_seo_meta_box'),
            'wvp_package',
            'normal',
            'default'
        );

        add_meta_box(
            'wvp_package_products',
            __('Dozvoljeni Proizvodi', 'woocommerce-vip-paketi'),
            array($this, 'package_products_meta_box'),
            'wvp_package',
            'normal',
            'high'
        );

        add_meta_box(
            'wvp_package_discounts',
            __('Pravila Popusta', 'woocommerce-vip-paketi'),
            array($this, 'package_discounts_meta_box'),
            'wvp_package',
            'normal',
            'high'
        );

        add_meta_box(
            'wvp_package_display',
            __('Postavke Prikaza', 'woocommerce-vip-paketi'),
            array($this, 'package_display_meta_box'),
            'wvp_package',
            'side',
            'default'
        );
    }

    public function package_configuration_meta_box($post) {
        wp_nonce_field('wvp_package_meta_box', 'wvp_package_meta_box_nonce');

        $min_items = get_post_meta($post->ID, '_wvp_min_items', true) ?: 2;
        $max_items = get_post_meta($post->ID, '_wvp_max_items', true) ?: 6;
        $package_sizes = get_post_meta($post->ID, '_wvp_package_sizes', true) ?: array(2, 3, 4, 5, 6);
        $allow_coupons = get_post_meta($post->ID, '_wvp_allow_coupons', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Minimum Stavki', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <input type="number" name="_wvp_min_items" value="<?php echo esc_attr($min_items); ?>" min="1" max="50" />
                    <p class="description"><?php _e('Minimalan broj stavki koji može biti izabran', 'woocommerce-vip-paketi'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Maksimum Stavki', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <input type="number" name="_wvp_max_items" value="<?php echo esc_attr($max_items); ?>" min="1" max="50" />
                    <p class="description"><?php _e('Maksimalan broj stavki koji može biti izabran', 'woocommerce-vip-paketi'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Veličine Paketa', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <input type="text" name="_wvp_package_sizes" value="<?php echo esc_attr(implode(', ', (array)$package_sizes)); ?>" />
                    <p class="description"><?php _e('Lista dostupnih veličina paketa odvojena zarezima (npr. 2, 3, 4, 5, 6)', 'woocommerce-vip-paketi'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Dozvoli Kupone', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <label for="_wvp_allow_coupons">
                        <input type="checkbox" id="_wvp_allow_coupons" name="_wvp_allow_coupons" value="yes" <?php checked($allow_coupons, 'yes'); ?> />
                        <?php _e('Dozvoli da se kuponi primenjuju na ovaj paket', 'woocommerce-vip-paketi'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public function package_products_meta_box($post) {
        $allowed_products = get_post_meta($post->ID, '_wvp_allowed_products', true) ?: array();
        $global_allowed = get_option('wvp_package_allowed_products', array());

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wvp_package_allowed',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );

        $products = get_posts($args);

        ?>
        <div class="wvp-products-selection">
            <?php if (empty($products)): ?>
            <p><?php _e('Trenutno nijedan proizvod nije omogućen za pakete. Molimo podesi proizvode na stranici Podešavanja Proizvoda.', 'woocommerce-vip-paketi'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=wvp-products'); ?>" class="button button-primary">
                <?php _e('Podesi Proizvode', 'woocommerce-vip-paketi'); ?>
            </a>
            <?php else: ?>
            <p><?php _e('Izaberi koji proizvodi mogu biti uključeni u ovaj paket:', 'woocommerce-vip-paketi'); ?></p>
            
            <div class="wvp-product-checkboxes">
                <?php foreach ($products as $product): ?>
                <label class="wvp-product-checkbox">
                    <input type="checkbox" name="_wvp_allowed_products[]" value="<?php echo esc_attr($product->ID); ?>" <?php checked(in_array($product->ID, $allowed_products)); ?> />
                    <span><?php echo esc_html($product->post_title); ?></span>
                    <?php
                    $regular_price = get_post_meta($product->ID, '_regular_price', true);
                    if ($regular_price):
                    ?>
                    <small class="price">(<?php echo wc_price($regular_price); ?>)</small>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="wvp-bulk-actions">
                <button type="button" class="button" id="wvp-select-all-products"><?php _e('Izaberi Sve', 'woocommerce-vip-paketi'); ?></button>
                <button type="button" class="button" id="wvp-deselect-all-products"><?php _e('Poništi Sve', 'woocommerce-vip-paketi'); ?></button>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#wvp-select-all-products').click(function() {
                $('.wvp-product-checkboxes input[type="checkbox"]').prop('checked', true);
            });

            $('#wvp-deselect-all-products').click(function() {
                $('.wvp-product-checkboxes input[type="checkbox"]').prop('checked', false);
            });
        });
        </script>
        <?php
    }

    public function package_discounts_meta_box($post) {
        $package_sizes = get_post_meta($post->ID, '_wvp_package_sizes', true) ?: array(2, 3, 4, 5, 6);
        $regular_discounts = get_post_meta($post->ID, '_wvp_regular_discounts', true) ?: array();
        $vip_discounts = get_post_meta($post->ID, '_wvp_vip_discounts', true) ?: array();

        ?>
        <p><?php _e('Podesi procente popusta za različite veličine paketa:', 'woocommerce-vip-paketi'); ?></p>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Veličina Paketa', 'woocommerce-vip-paketi'); ?></th>
                    <th><?php _e('Regularni Popust (%)', 'woocommerce-vip-paketi'); ?></th>
                    <th><?php _e('VIP Popust (%)', 'woocommerce-vip-paketi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($package_sizes as $size): ?>
                <tr>
                    <td><?php echo sprintf(_n('%d stavka', '%d stavki', $size, 'woocommerce-vip-paketi'), $size); ?></td>
                    <td>
                        <input type="number" 
                               name="_wvp_regular_discounts[<?php echo esc_attr($size); ?>]" 
                               value="<?php echo esc_attr(isset($regular_discounts[$size]) ? $regular_discounts[$size] : 0); ?>" 
                               min="0" 
                               max="100" 
                               step="0.01" 
                               style="width: 80px;" /> %
                    </td>
                    <td>
                        <input type="number" 
                               name="_wvp_vip_discounts[<?php echo esc_attr($size); ?>]" 
                               value="<?php echo esc_attr(isset($vip_discounts[$size]) ? $vip_discounts[$size] : 0); ?>" 
                               min="0" 
                               max="100" 
                               step="0.01" 
                               style="width: 80px;" /> %
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="description">
            <?php _e('VIP popusti se primenjuju dodatno na regularne popuste. Na primer: ako je regularni popust 10% a VIP popust 5%, ukupni VIP popust će biti 14.5% (10% + 5% od preostalih 90%).', 'woocommerce-vip-paketi'); ?>
        </p>
        <?php
    }

    public function package_display_meta_box($post) {
        $show_discount_table = get_post_meta($post->ID, '_wvp_show_discount_table', true) ?: 'yes';
        $show_for_non_vip = get_post_meta($post->ID, '_wvp_show_for_non_vip', true) ?: 'yes';
        $package_status = get_post_meta($post->ID, '_wvp_package_status', true) ?: 'active';

        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Status Paketa', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <select name="_wvp_package_status">
                        <option value="active" <?php selected($package_status, 'active'); ?>><?php _e('Aktivan', 'woocommerce-vip-paketi'); ?></option>
                        <option value="inactive" <?php selected($package_status, 'inactive'); ?>><?php _e('Neaktivan', 'woocommerce-vip-paketi'); ?></option>
                        <option value="draft" <?php selected($package_status, 'draft'); ?>><?php _e('Nacrt', 'woocommerce-vip-paketi'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Prikaži Tabelu Popusta', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <label for="_wvp_show_discount_table">
                        <input type="checkbox" id="_wvp_show_discount_table" name="_wvp_show_discount_table" value="yes" <?php checked($show_discount_table, 'yes'); ?> />
                        <?php _e('Prikaži tabelu popusta na stranici paketa', 'woocommerce-vip-paketi'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Prikaži Ne-VIP korisnicima', 'woocommerce-vip-paketi'); ?></th>
                <td>
                    <label for="_wvp_show_for_non_vip">
                        <input type="checkbox" id="_wvp_show_for_non_vip" name="_wvp_show_for_non_vip" value="yes" <?php checked($show_for_non_vip, 'yes'); ?> />
                        <?php _e('Prikaži paket korisnicima koji nisu VIP', 'woocommerce-vip-paketi'); ?>
                    </label>
                    <p class="description"><?php _e('Ako nije označeno, samo VIP članovi mogu videti ovaj paket', 'woocommerce-vip-paketi'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_package_meta($post_id) {
        if (!isset($_POST['wvp_package_meta_box_nonce']) || !wp_verify_nonce($_POST['wvp_package_meta_box_nonce'], 'wvp_package_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== 'wvp_package') {
            return;
        }

        $fields = array(
            '_wvp_min_items' => 'absint',
            '_wvp_max_items' => 'absint',
            '_wvp_package_sizes' => 'array',
            '_wvp_allow_coupons' => 'checkbox',
            '_wvp_allowed_products' => 'array',
            '_wvp_regular_discounts' => 'array',
            '_wvp_vip_discounts' => 'array',
            '_wvp_show_discount_table' => 'checkbox',
            '_wvp_show_for_non_vip' => 'checkbox',
            '_wvp_package_status' => 'sanitize_text_field',
            '_wvp_seo_title' => 'sanitize_text_field',
            '_wvp_seo_description' => 'sanitize_textarea_field',
            '_wvp_seo_keywords' => 'sanitize_text_field',
            '_wvp_seo_focus_keyword' => 'sanitize_text_field'
        );

        foreach ($fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];

                switch ($sanitize_function) {
                    case 'absint':
                        $value = absint($value);
                        break;
                    case 'array':
                        if ($field === '_wvp_package_sizes' && is_string($value)) {
                            $value = array_map('absint', array_map('trim', explode(',', $value)));
                        } elseif (is_array($value)) {
                            $value = array_map('sanitize_text_field', $value);
                        }
                        break;
                    case 'checkbox':
                        $value = $value === 'yes' ? 'yes' : 'no';
                        break;
                    case 'sanitize_text_field':
                        $value = sanitize_text_field($value);
                        break;
                    case 'sanitize_textarea_field':
                        $value = sanitize_textarea_field($value);
                        break;
                }

                update_post_meta($post_id, $field, $value);
            } else {
                if (in_array($sanitize_function, array('checkbox'))) {
                    update_post_meta($post_id, $field, 'no');
                }
            }
        }

        do_action('wvp_package_saved', $post_id);
    }

    public function get_package_data($package_id) {
        $package = get_post($package_id);
        
        if (!$package || $package->post_type !== 'wvp_package') {
            return false;
        }

        return array(
            'id' => $package->ID,
            'title' => $package->post_title,
            'content' => $package->post_content,
            'status' => get_post_meta($package->ID, '_wvp_package_status', true),
            'min_items' => get_post_meta($package->ID, '_wvp_min_items', true),
            'max_items' => get_post_meta($package->ID, '_wvp_max_items', true),
            'package_sizes' => get_post_meta($package->ID, '_wvp_package_sizes', true),
            'allowed_products' => get_post_meta($package->ID, '_wvp_allowed_products', true),
            'regular_discounts' => get_post_meta($package->ID, '_wvp_regular_discounts', true),
            'vip_discounts' => get_post_meta($package->ID, '_wvp_vip_discounts', true),
            'allow_coupons' => get_post_meta($package->ID, '_wvp_allow_coupons', true),
            'show_discount_table' => get_post_meta($package->ID, '_wvp_show_discount_table', true),
            'show_for_non_vip' => get_post_meta($package->ID, '_wvp_show_for_non_vip', true)
        );
    }

    public function is_package_available_for_user($package_id, $user_id = null) {
        $package_data = $this->get_package_data($package_id);
        
        if (!$package_data || $package_data['status'] !== 'active') {
            return false;
        }

        if ($package_data['show_for_non_vip'] === 'no') {
            $core = new WVP_Core();
            if (!$core->is_user_vip($user_id)) {
                return false;
            }
        }

        return true;
    }

    public function package_seo_meta_box($post) {
        $seo_title = get_post_meta($post->ID, '_wvp_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_wvp_seo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_wvp_seo_keywords', true);
        $seo_focus_keyword = get_post_meta($post->ID, '_wvp_seo_focus_keyword', true);
        
        wp_nonce_field('wvp_package_seo_nonce', 'wvp_package_seo_nonce');
        ?>
        
        <div class="wvp-seo-container">
            <p><strong><?php _e('Optimizuj SEO za ovaj paket. Ova podešavanja će biti korišćena od strane Yoast i RankMath plugin-a.', 'woocommerce-vip-paketi'); ?></strong></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="_wvp_seo_title"><?php _e('SEO Naslov', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="_wvp_seo_title" 
                               name="_wvp_seo_title" 
                               value="<?php echo esc_attr($seo_title); ?>" 
                               class="large-text" 
                               placeholder="<?php echo esc_attr(get_the_title($post->ID)) . ' - Konfiguriši Svoj Paket'; ?>"
                               maxlength="60" />
                        <p class="description">
                            <?php _e('Naslov stranice koji će se prikazati u Google rezultatima pretrage. Preporučeno je maksimalno 60 karaktera.', 'woocommerce-vip-paketi'); ?>
                            <span id="seo-title-counter">0/60</span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="_wvp_seo_description"><?php _e('Meta Opis', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <textarea id="_wvp_seo_description" 
                                  name="_wvp_seo_description" 
                                  rows="3" 
                                  class="large-text" 
                                  placeholder="Kreiraj svoj prilagođeni <?php echo esc_attr(get_the_title($post->ID)); ?> paket sa popustnim cenama..." 
                                  maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                        <p class="description">
                            <?php _e('Kratak opis koji će se prikazati u Google rezultatima pretrage. Preporučeno je 150-160 karaktera.', 'woocommerce-vip-paketi'); ?>
                            <span id="seo-description-counter">0/160</span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="_wvp_seo_focus_keyword"><?php _e('Fokus Ključna Reč', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="_wvp_seo_focus_keyword" 
                               name="_wvp_seo_focus_keyword" 
                               value="<?php echo esc_attr($seo_focus_keyword); ?>" 
                               class="large-text" 
                               placeholder="<?php echo strtolower(get_the_title($post->ID)); ?> paket" />
                        <p class="description">
                            <?php _e('Glavna ključna reč na koju želiš da optimizuješ ovu stranicu. Yoast/RankMath će analizirati sadržaj na osnovu ove reči.', 'woocommerce-vip-paketi'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="_wvp_seo_keywords"><?php _e('Dodatne Ključne Reči', 'woocommerce-vip-paketi'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="_wvp_seo_keywords" 
                               name="_wvp_seo_keywords" 
                               value="<?php echo esc_attr($seo_keywords); ?>" 
                               class="large-text" 
                               placeholder="paket, popust, VIP, konfiguracija, proizvodi" />
                        <p class="description">
                            <?php _e('Dodatne ključne reči odvojene zarezima. Ove reči će biti dodane u meta keywords tag.', 'woocommerce-vip-paketi'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('SEO Pregled', 'woocommerce-vip-paketi'); ?></h3>
            <div class="wvp-seo-preview">
                <div class="seo-preview-google">
                    <div class="preview-url"><?php echo esc_url(get_permalink($post->ID)); ?></div>
                    <div class="preview-title" id="preview-title">
                        <?php echo $seo_title ? esc_html($seo_title) : esc_html(get_the_title($post->ID)) . ' - Konfiguriši Svoj Paket'; ?>
                    </div>
                    <div class="preview-description" id="preview-description">
                        <?php 
                        if ($seo_description) {
                            echo esc_html($seo_description);
                        } else {
                            echo 'Kreiraj svoj prilagođeni ' . esc_html(get_the_title($post->ID)) . ' paket sa popustnim cenama. Izaberi proizvode i uštedi sa našim VIP paketima.';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .wvp-seo-container .form-table th {
            width: 200px;
            padding-left: 0;
        }
        
        .wvp-seo-preview {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .seo-preview-google {
            font-family: arial, sans-serif;
        }
        
        .preview-url {
            color: #006621;
            font-size: 14px;
            line-height: 1.3;
        }
        
        .preview-title {
            color: #1a0dab;
            font-size: 20px;
            line-height: 1.3;
            cursor: pointer;
            font-weight: 400;
            margin: 3px 0;
        }
        
        .preview-description {
            color: #545454;
            font-size: 14px;
            line-height: 1.4;
        }
        
        #seo-title-counter, #seo-description-counter {
            float: right;
            font-weight: bold;
            color: #666;
        }
        
        .over-limit {
            color: #d32f2f !important;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Character counters
            function updateCounter(field, counter, limit) {
                var count = $(field).val().length;
                $(counter).text(count + '/' + limit);
                
                if (count > limit) {
                    $(counter).addClass('over-limit');
                } else {
                    $(counter).removeClass('over-limit');
                }
            }
            
            // Update counters on input
            $('#_wvp_seo_title').on('input', function() {
                updateCounter(this, '#seo-title-counter', 60);
                $('#preview-title').text($(this).val() || '<?php echo esc_js(get_the_title($post->ID) . " - Konfiguriši Svoj Paket"); ?>');
            });
            
            $('#_wvp_seo_description').on('input', function() {
                updateCounter(this, '#seo-description-counter', 160);
                $('#preview-description').text($(this).val() || 'Kreiraj svoj prilagođeni <?php echo esc_js(get_the_title($post->ID)); ?> paket sa popustnim cenama. Izaberi proizvode i uštedi sa našim VIP paketima.');
            });
            
            // Initialize counters
            updateCounter('#_wvp_seo_title', '#seo-title-counter', 60);
            updateCounter('#_wvp_seo_description', '#seo-description-counter', 160);
        });
        </script>
        <?php
    }
}