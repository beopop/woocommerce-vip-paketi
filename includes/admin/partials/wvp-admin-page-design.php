<?php
/**
 * Package Page Design Customization Admin Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('wvp_page_design_nonce')) {
    // Text Settings
    update_option('wvp_page_title_text', sanitize_text_field($_POST['wvp_page_title_text']));
    update_option('wvp_page_subtitle_text', sanitize_text_field($_POST['wvp_page_subtitle_text']));
    update_option('wvp_page_description_text', sanitize_textarea_field($_POST['wvp_page_description_text']));
    update_option('wvp_discount_table_title', sanitize_text_field($_POST['wvp_discount_table_title']));
    update_option('wvp_discount_explanation_title', sanitize_text_field($_POST['wvp_discount_explanation_title']));
    
    // Color Settings
    update_option('wvp_primary_color', sanitize_hex_color($_POST['wvp_primary_color']));
    update_option('wvp_secondary_color', sanitize_hex_color($_POST['wvp_secondary_color']));
    update_option('wvp_accent_color', sanitize_hex_color($_POST['wvp_accent_color']));
    update_option('wvp_text_color', sanitize_hex_color($_POST['wvp_text_color']));
    update_option('wvp_background_color', sanitize_hex_color($_POST['wvp_background_color']));
    
    // Style Settings
    update_option('wvp_border_radius', intval($_POST['wvp_border_radius']));
    update_option('wvp_font_size', intval($_POST['wvp_font_size']));
    update_option('wvp_spacing', intval($_POST['wvp_spacing']));
    
    echo '<div class="notice notice-success"><p>' . __('Podešavanja su uspešno sačuvana!', 'woocommerce-vip-paketi') . '</p></div>';
}

// Get current values
$page_title_text = get_option('wvp_page_title_text', 'PODESI SVOJ PAKET');
$page_subtitle_text = get_option('wvp_page_subtitle_text', 'IZABERI VELIČINU PAKETA');
$page_description_text = get_option('wvp_page_description_text', 'Izaberi između 2 i 6 proizvoda iz našeg kuriranog izbora. Što više dodaš, više uštetiš!');
$discount_table_title = get_option('wvp_discount_table_title', 'POPUSTI ZA PAKETE');
$discount_explanation_title = get_option('wvp_discount_explanation_title', 'KAKO FUNKCIONIŠU POPUSTI ZA PAKETE');

$primary_color = get_option('wvp_primary_color', '#d4a017');
$secondary_color = get_option('wvp_secondary_color', '#28a745');
$accent_color = get_option('wvp_accent_color', '#007cba');
$text_color = get_option('wvp_text_color', '#1d2327');
$background_color = get_option('wvp_background_color', '#ffffff');

$border_radius = get_option('wvp_border_radius', 8);
$font_size = get_option('wvp_font_size', 16);
$spacing = get_option('wvp_spacing', 30);
?>

<div class="wrap">
    <h1><?php _e('Dizajn Stranice Paketa', 'woocommerce-vip-paketi'); ?></h1>
    <p><?php _e('Ovde možete da prilagodite tekstove, boje i stil stranice paketa.', 'woocommerce-vip-paketi'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('wvp_page_design_nonce'); ?>
        
        <div class="wvp-design-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#texts" class="nav-tab nav-tab-active"><?php _e('Tekstovi', 'woocommerce-vip-paketi'); ?></a>
                <a href="#colors" class="nav-tab"><?php _e('Boje', 'woocommerce-vip-paketi'); ?></a>
                <a href="#styles" class="nav-tab"><?php _e('Stilovi', 'woocommerce-vip-paketi'); ?></a>
                <a href="#preview" class="nav-tab"><?php _e('Pregled', 'woocommerce-vip-paketi'); ?></a>
            </nav>

            <!-- Text Settings Tab -->
            <div id="texts" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wvp_page_title_text"><?php _e('Glavni naslov stranice', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wvp_page_title_text" name="wvp_page_title_text" value="<?php echo esc_attr($page_title_text); ?>" class="regular-text" />
                            <p class="description"><?php _e('Glavni naslov koji se prikazuje na vrhu stranice paketa.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_page_subtitle_text"><?php _e('Podnaslov stranice', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wvp_page_subtitle_text" name="wvp_page_subtitle_text" value="<?php echo esc_attr($page_subtitle_text); ?>" class="regular-text" />
                            <p class="description"><?php _e('Podnaslov za sekciju izbora veličine paketa.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_page_description_text"><?php _e('Opis stranice', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <textarea id="wvp_page_description_text" name="wvp_page_description_text" rows="3" class="large-text"><?php echo esc_textarea($page_description_text); ?></textarea>
                            <p class="description"><?php _e('Kratki opis koji objašnjava kako funkcionišu paketi.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_discount_table_title"><?php _e('Naslov tabele popusta', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wvp_discount_table_title" name="wvp_discount_table_title" value="<?php echo esc_attr($discount_table_title); ?>" class="regular-text" />
                            <p class="description"><?php _e('Naslov sekcije sa tabelom popusta.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_discount_explanation_title"><?php _e('Naslov objašnjenja popusta', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wvp_discount_explanation_title" name="wvp_discount_explanation_title" value="<?php echo esc_attr($discount_explanation_title); ?>" class="regular-text" />
                            <p class="description"><?php _e('Naslov sekcije koja objašnjava kako funkcionišu popusti.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Color Settings Tab -->
            <div id="colors" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wvp_primary_color"><?php _e('Primarna boja', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="wvp_primary_color" name="wvp_primary_color" value="<?php echo esc_attr($primary_color); ?>" class="color-picker" />
                            <p class="description"><?php _e('Glavna boja za dugmad i akcente.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_secondary_color"><?php _e('Sekundarna boja', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="wvp_secondary_color" name="wvp_secondary_color" value="<?php echo esc_attr($secondary_color); ?>" class="color-picker" />
                            <p class="description"><?php _e('Boja za popuste i pozitivne akcije.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_accent_color"><?php _e('Naglasna boja', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="wvp_accent_color" name="wvp_accent_color" value="<?php echo esc_attr($accent_color); ?>" class="color-picker" />
                            <p class="description"><?php _e('Boja za linkove i posebne elemente.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_text_color"><?php _e('Boja teksta', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="wvp_text_color" name="wvp_text_color" value="<?php echo esc_attr($text_color); ?>" class="color-picker" />
                            <p class="description"><?php _e('Glavna boja teksta.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_background_color"><?php _e('Boja pozadine', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="wvp_background_color" name="wvp_background_color" value="<?php echo esc_attr($background_color); ?>" class="color-picker" />
                            <p class="description"><?php _e('Boja pozadine glavnih sekcija.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Style Settings Tab -->
            <div id="styles" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wvp_border_radius"><?php _e('Zaobljavanje uglova (px)', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wvp_border_radius" name="wvp_border_radius" value="<?php echo esc_attr($border_radius); ?>" min="0" max="50" />
                            <p class="description"><?php _e('Stepen zaobljanja uglova elemenata.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_font_size"><?php _e('Veličina fonta (px)', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wvp_font_size" name="wvp_font_size" value="<?php echo esc_attr($font_size); ?>" min="12" max="24" />
                            <p class="description"><?php _e('Osnovna veličina fonta.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="wvp_spacing"><?php _e('Razmak između sekcija (px)', 'woocommerce-vip-paketi'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wvp_spacing" name="wvp_spacing" value="<?php echo esc_attr($spacing); ?>" min="10" max="50" />
                            <p class="description"><?php _e('Razmak između glavnih sekcija stranice.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Preview Tab -->
            <div id="preview" class="tab-content" style="display: none;">
                <div class="wvp-preview-container">
                    <h3><?php _e('Pregled kako će stranica izgledati', 'woocommerce-vip-paketi'); ?></h3>
                    <div class="wvp-preview-section" style="background: <?php echo esc_attr($background_color); ?>; color: <?php echo esc_attr($text_color); ?>; border-radius: <?php echo esc_attr($border_radius); ?>px; padding: <?php echo esc_attr($spacing); ?>px; font-size: <?php echo esc_attr($font_size); ?>px;">
                        <h2 style="color: <?php echo esc_attr($primary_color); ?>; text-align: center;"><?php echo esc_html($page_title_text); ?></h2>
                        <p style="text-align: center; margin-bottom: <?php echo esc_attr($spacing); ?>px;"><?php echo esc_html($page_description_text); ?></p>
                        <h4 style="color: <?php echo esc_attr($primary_color); ?>; text-align: center;"><?php echo esc_html($page_subtitle_text); ?></h4>
                        
                        <div style="margin: <?php echo esc_attr($spacing); ?>px 0;">
                            <h3 style="color: <?php echo esc_attr($primary_color); ?>;"><?php echo esc_html($discount_table_title); ?></h3>
                            <h4 style="color: <?php echo esc_attr($text_color); ?>;"><?php echo esc_html($discount_explanation_title); ?></h4>
                        </div>
                        
                        <div class="wvp-preview-elements">
                            <span class="preview-button" style="background: <?php echo esc_attr($primary_color); ?>; color: white; padding: 10px 20px; border-radius: <?php echo esc_attr($border_radius); ?>px; display: inline-block; margin-right: 10px;">Dugme</span>
                            <span class="preview-discount" style="background: <?php echo esc_attr($secondary_color); ?>; color: white; padding: 5px 10px; border-radius: <?php echo esc_attr($border_radius); ?>px; display: inline-block; margin-right: 10px;">-20% Popust</span>
                            <a href="#" style="color: <?php echo esc_attr($accent_color); ?>;">Link tekst</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button(__('Sačuvaj podešavanja', 'woocommerce-vip-paketi')); ?>
    </form>
</div>

<style>
.wvp-design-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.color-picker {
    width: 60px;
    height: 40px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.wvp-preview-container {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.wvp-preview-section {
    border: 2px dashed #ddd;
    margin-top: 15px;
}

.wvp-preview-elements {
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preview-button, .preview-discount {
    text-decoration: none;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        var target = $(this).attr('href');
        $(target).show();
    });
    
    // Live preview updates
    function updatePreview() {
        var titleText = $('#wvp_page_title_text').val();
        var subtitleText = $('#wvp_page_subtitle_text').val();
        var descriptionText = $('#wvp_page_description_text').val();
        var tableTitle = $('#wvp_discount_table_title').val();
        var explanationTitle = $('#wvp_discount_explanation_title').val();
        
        var primaryColor = $('#wvp_primary_color').val();
        var secondaryColor = $('#wvp_secondary_color').val();
        var accentColor = $('#wvp_accent_color').val();
        var textColor = $('#wvp_text_color').val();
        var backgroundColor = $('#wvp_background_color').val();
        
        var borderRadius = $('#wvp_border_radius').val();
        var fontSize = $('#wvp_font_size').val();
        var spacing = $('#wvp_spacing').val();
        
        // Update preview section
        $('.wvp-preview-section').css({
            'background': backgroundColor,
            'color': textColor,
            'border-radius': borderRadius + 'px',
            'padding': spacing + 'px',
            'font-size': fontSize + 'px'
        });
        
        $('.wvp-preview-section h2').css('color', primaryColor).text(titleText);
        $('.wvp-preview-section p').text(descriptionText);
        $('.wvp-preview-section h4').css('color', primaryColor).text(subtitleText);
        $('.wvp-preview-section h3').css('color', primaryColor).text(tableTitle);
        $('.wvp-preview-section h4:last').css('color', textColor).text(explanationTitle);
        
        $('.preview-button').css({
            'background': primaryColor,
            'border-radius': borderRadius + 'px'
        });
        
        $('.preview-discount').css({
            'background': secondaryColor,
            'border-radius': borderRadius + 'px'
        });
        
        $('.wvp-preview-section a').css('color', accentColor);
    }
    
    // Bind live preview to all inputs
    $('input, textarea').on('input change', updatePreview);
    
    // Initial preview update
    updatePreview();
});
</script>