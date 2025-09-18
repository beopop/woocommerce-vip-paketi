<?php
/**
 * Provide a admin area view for the plugin settings
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap wvp-admin-page">
    <div class="wvp-admin-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p><?php _e('Podesi VIP cene, pakete i postavke integracije za svoju WooCommerce prodavnicu.', 'woocommerce-vip-paketi'); ?></p>
    </div>

    <div class="wvp-admin-tabs">
        <nav class="nav-tab-wrapper">
            <a href="?page=wvp-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Opšte', 'woocommerce-vip-paketi'); ?>
            </a>
            <a href="?page=wvp-settings&tab=vip" class="nav-tab <?php echo $active_tab == 'vip' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-star-filled"></span>
                <?php _e('VIP Podešavanja', 'woocommerce-vip-paketi'); ?>
            </a>
            <a href="?page=wvp-settings&tab=packages" class="nav-tab <?php echo $active_tab == 'packages' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-portfolio"></span>
                <?php _e('Paketi', 'woocommerce-vip-paketi'); ?>
            </a>
            <a href="?page=wvp-settings&tab=integrations" class="nav-tab <?php echo $active_tab == 'integrations' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php _e('Integracije', 'woocommerce-vip-paketi'); ?>
            </a>
        </nav>

        <?php if ($active_tab == 'general'): ?>
        <div class="wvp-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('wvp_settings_group');
                do_settings_sections('wvp_settings_general');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Uključi VIP Cene', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_enable_vip_pricing">
                                <input type="checkbox" id="wvp_enable_vip_pricing" name="wvp_enable_vip_pricing" value="yes" <?php checked(get_option('wvp_enable_vip_pricing'), 'yes'); ?> />
                                <?php _e('Uključi VIP sistem cena kroz prodavnicu', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Ovo uključuje osnovnu VIP funkcionalnost cena. Onemogudi da ugasiš sve VIP funkcije.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Uključi Pakete', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_enable_packages">
                                <input type="checkbox" id="wvp_enable_packages" name="wvp_enable_packages" value="yes" <?php checked(get_option('wvp_enable_packages'), 'yes'); ?> />
                                <?php _e('Uključi sistem varijabilnih paketa', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Dozvoli kupcima da kreiraju prilagođene pakete sa popustnim cenama.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Debug Režim', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_debug_mode">
                                <input type="checkbox" id="wvp_debug_mode" name="wvp_debug_mode" value="yes" <?php checked(get_option('wvp_debug_mode'), 'yes'); ?> />
                                <?php _e('Uključi debug logovanje', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Logiraj VIP kalkulacije cena i verifikacije kodova u WooCommerce logove.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Hidden fields to preserve settings from other tabs -->
                <input type="hidden" name="wvp_vip_price_label" value="<?php echo esc_attr(get_option('wvp_vip_price_label', 'VIP Cena')); ?>" />
                <input type="hidden" name="wvp_non_vip_display_format" value="<?php echo esc_attr(get_option('wvp_non_vip_display_format', 'both')); ?>" />
                <input type="hidden" name="wvp_enable_checkout_codes" value="<?php echo esc_attr(get_option('wvp_enable_checkout_codes', 'no')); ?>" />
                <input type="hidden" name="wvp_auto_registration" value="<?php echo esc_attr(get_option('wvp_auto_registration', 'no')); ?>" />
                <input type="hidden" name="wvp_email_notifications" value="<?php echo esc_attr(get_option('wvp_email_notifications', 'yes')); ?>" />
                <input type="hidden" name="wvp_vip_role_enabled" value="<?php echo esc_attr(get_option('wvp_vip_role_enabled', 'no')); ?>" />
                <input type="hidden" name="wvp_woodmart_integration" value="<?php echo esc_attr(get_option('wvp_woodmart_integration', 'no')); ?>" />
                <input type="hidden" name="wvp_package_url_slug" value="<?php echo esc_attr(get_option('wvp_package_url_slug', 'konfiguracija-paketa')); ?>" />
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($active_tab == 'vip'): ?>
        <div class="wvp-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('wvp_settings_group');
                do_settings_sections('wvp_settings_vip');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('VIP Oznaka Cene', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="text" id="wvp_vip_price_label" name="wvp_vip_price_label" value="<?php echo esc_attr(get_option('wvp_vip_price_label', 'VIP Cena')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Tekst koji se prikazuje pored VIP cena (npr. "VIP Cena", "Članska Cena").', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Format Prikaza za Ne-VIP', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <select id="wvp_non_vip_display_format" name="wvp_non_vip_display_format">
                                <option value="both" <?php selected(get_option('wvp_non_vip_display_format'), 'both'); ?>><?php _e('Prikaži i regularnu i VIP cenu', 'woocommerce-vip-paketi'); ?></option>
                                <option value="regular_only" <?php selected(get_option('wvp_non_vip_display_format'), 'regular_only'); ?>><?php _e('Prikaži samo regularnu cenu', 'woocommerce-vip-paketi'); ?></option>
                                <option value="vip_teaser" <?php selected(get_option('wvp_non_vip_display_format'), 'vip_teaser'); ?>><?php _e('Prikaži VIP uštede kao tizer', 'woocommerce-vip-paketi'); ?></option>
                            </select>
                            <p class="description"><?php _e('Kako se VIP cene prikazuju korisnicima koji nisu VIP.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Uključi VIP Kodove na Checkout-u', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_enable_checkout_codes">
                                <input type="checkbox" id="wvp_enable_checkout_codes" name="wvp_enable_checkout_codes" value="yes" <?php checked(get_option('wvp_enable_checkout_codes'), 'yes'); ?> />
                                <?php _e('Dozvoli unos VIP koda tokom narudžbe', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Prikazuje polje za unos VIP koda na stranici narudžbe za goste i ne-VIP korisnike.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Auto-Registracija', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_auto_registration">
                                <input type="checkbox" id="wvp_auto_registration" name="wvp_auto_registration" value="yes" <?php checked(get_option('wvp_auto_registration'), 'yes'); ?> />
                                <?php _e('Automatski registruj goste kad koriste VIP kodove', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Kreira korisnički nalog automatski za goste koji verifikuju VIP kodove.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('VIP Uloga', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_vip_role_enabled">
                                <input type="checkbox" id="wvp_vip_role_enabled" name="wvp_vip_role_enabled" value="yes" <?php checked(get_option('wvp_vip_role_enabled'), 'yes'); ?> />
                                <?php _e('Uključi WordPress VIP ulogu za VIP korisnike', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Automatski dodeli VIP ulogu korisnicima kada aktiviraju VIP kodove.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Email Obaveštenja', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_email_notifications">
                                <input type="checkbox" id="wvp_email_notifications" name="wvp_email_notifications" value="yes" <?php checked(get_option('wvp_email_notifications'), 'yes'); ?> />
                                <?php _e('Pošalji email obaveštenja za VIP aktivacije', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Pošalji email dobrodošlice kad se VIP status aktivira preko kodova.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Hidden fields to preserve settings from other tabs -->
                <input type="hidden" name="wvp_enable_vip_pricing" value="<?php echo esc_attr(get_option('wvp_enable_vip_pricing', 'no')); ?>" />
                <input type="hidden" name="wvp_enable_packages" value="<?php echo esc_attr(get_option('wvp_enable_packages', 'no')); ?>" />
                <input type="hidden" name="wvp_debug_mode" value="<?php echo esc_attr(get_option('wvp_debug_mode', 'no')); ?>" />
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($active_tab == 'packages'): ?>
        <div class="wvp-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('wvp_settings_group');
                do_settings_sections('wvp_settings_packages');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('URL Alias za Pakete', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="text" id="wvp_package_url_slug" name="wvp_package_url_slug" value="<?php echo esc_attr(get_option('wvp_package_url_slug', 'konfiguracija-paketa')); ?>" class="regular-text" />
                            <p class="description"><?php _e('URL alias koji se koristi za pakete (npr. "paketi", "konfiguracija-paketa"). Nakon izmene potrebno je sačuvati podešavanja.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Podrazumevane Veličine Paketa', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="text" id="wvp_default_package_sizes" name="wvp_default_package_sizes" value="<?php echo esc_attr(get_option('wvp_default_package_sizes', '2,3,4,5,6')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Lista podrazumevanih veličina paketa za nove pakete odvojena zarezima.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Podrazumevani Regularni Popust', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="number" id="wvp_default_regular_discount" name="wvp_default_regular_discount" value="<?php echo esc_attr(get_option('wvp_default_regular_discount', '5')); ?>" min="0" max="100" step="0.01" /> %
                            <p class="description"><?php _e('Podrazumevani procenat popusta za regularne korisnike na paketima.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Podrazumevani VIP Popust', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="number" id="wvp_default_vip_discount" name="wvp_default_vip_discount" value="<?php echo esc_attr(get_option('wvp_default_vip_discount', '10')); ?>" min="0" max="100" step="0.01" /> %
                            <p class="description"><?php _e('Podrazumevani dodatni VIP procenat popusta na paketima.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Dozvoli Kupone na Paketima', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_packages_allow_coupons">
                                <input type="checkbox" id="wvp_packages_allow_coupons" name="wvp_packages_allow_coupons" value="yes" <?php checked(get_option('wvp_packages_allow_coupons'), 'yes'); ?> />
                                <?php _e('Dozvoli da se WooCommerce kuponi primenjuju na pakete podrazumevano', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Pojedinačni paketi mogu da promene ovo podešavanje.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <!-- Hidden fields to preserve settings from other tabs -->
                <input type="hidden" name="wvp_enable_vip_pricing" value="<?php echo esc_attr(get_option('wvp_enable_vip_pricing', 'no')); ?>" />
                <input type="hidden" name="wvp_debug_mode" value="<?php echo esc_attr(get_option('wvp_debug_mode', 'no')); ?>" />
                <input type="hidden" name="wvp_vip_price_label" value="<?php echo esc_attr(get_option('wvp_vip_price_label', 'VIP Cena')); ?>" />
                <input type="hidden" name="wvp_non_vip_display_format" value="<?php echo esc_attr(get_option('wvp_non_vip_display_format', 'both')); ?>" />
                <input type="hidden" name="wvp_enable_checkout_codes" value="<?php echo esc_attr(get_option('wvp_enable_checkout_codes', 'no')); ?>" />
                <input type="hidden" name="wvp_auto_registration" value="<?php echo esc_attr(get_option('wvp_auto_registration', 'no')); ?>" />
                <input type="hidden" name="wvp_email_notifications" value="<?php echo esc_attr(get_option('wvp_email_notifications', 'yes')); ?>" />
                <input type="hidden" name="wvp_vip_role_enabled" value="<?php echo esc_attr(get_option('wvp_vip_role_enabled', 'no')); ?>" />
                <input type="hidden" name="wvp_woodmart_integration" value="<?php echo esc_attr(get_option('wvp_woodmart_integration', 'no')); ?>" />
                <input type="hidden" name="wvp_package_url_slug" value="<?php echo esc_attr(get_option('wvp_package_url_slug', 'konfiguracija-paketa')); ?>" />
                <input type="hidden" name="wvp_woodmart_vip_color" value="<?php echo esc_attr(get_option('wvp_woodmart_vip_color', '#d4a017')); ?>" />
                <input type="hidden" name="wvp_woodmart_badge_position" value="<?php echo esc_attr(get_option('wvp_woodmart_badge_position', 'top-right')); ?>" />
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($active_tab == 'integrations'): ?>
        <div class="wvp-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('wvp_settings_group');
                do_settings_sections('wvp_settings_integrations');
                ?>
                
                <h3><?php _e('Woodmart Tema Integracija', 'woocommerce-vip-paketi'); ?></h3>
                
                <?php if (class_exists('Woodmart_Theme')): ?>
                <div class="notice notice-success inline">
                    <p><strong><?php _e('Woodmart tema otkrivena!', 'woocommerce-vip-paketi'); ?></strong> <?php _e('Podešavanja integracije su dostupna.', 'woocommerce-vip-paketi'); ?></p>
                </div>
                <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><strong><?php _e('Woodmart tema nije otkrivena.', 'woocommerce-vip-paketi'); ?></strong> <?php _e('Ova podešavanja neće imati efekat.', 'woocommerce-vip-paketi'); ?></p>
                </div>
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Uključi Woodmart Integraciju', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label for="wvp_woodmart_integration">
                                <input type="checkbox" id="wvp_woodmart_integration" name="wvp_woodmart_integration" value="yes" <?php checked(get_option('wvp_woodmart_integration'), 'yes'); ?> />
                                <?php _e('Uključi specifičnu Woodmart tema kompatibilnost', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Primenjuje Woodmart-specifično stilizovanje i funkcionalnost za VIP elemente.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('VIP Šema Boja', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="text" id="wvp_woodmart_vip_color" name="wvp_woodmart_vip_color" value="<?php echo esc_attr(get_option('wvp_woodmart_vip_color', '#d4a017')); ?>" class="wvp-color-picker" />
                            <p class="description"><?php _e('Glavna boja za VIP značke, oznake i istaknutosti.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Pozicija Značke', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <select id="wvp_woodmart_badge_position" name="wvp_woodmart_badge_position">
                                <option value="top-left" <?php selected(get_option('wvp_woodmart_badge_position'), 'top-left'); ?>><?php _e('Gore Levo', 'woocommerce-vip-paketi'); ?></option>
                                <option value="top-right" <?php selected(get_option('wvp_woodmart_badge_position'), 'top-right'); ?>><?php _e('Gore Desno', 'woocommerce-vip-paketi'); ?></option>
                                <option value="bottom-left" <?php selected(get_option('wvp_woodmart_badge_position'), 'bottom-left'); ?>><?php _e('Dole Levo', 'woocommerce-vip-paketi'); ?></option>
                                <option value="bottom-right" <?php selected(get_option('wvp_woodmart_badge_position'), 'bottom-right'); ?>><?php _e('Dole Desno', 'woocommerce-vip-paketi'); ?></option>
                            </select>
                            <p class="description"><?php _e('Pozicija VIP znački na slikama proizvoda.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Ostale Integracije', 'woocommerce-vip-paketi'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('WooCommerce Članstva', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <?php if (class_exists('WC_Memberships')): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <strong><?php _e('Aktivno', 'woocommerce-vip-paketi'); ?></strong>
                            <p class="description"><?php _e('Korisnici sa aktivnim članstvima automatski dobijaju VIP status.', 'woocommerce-vip-paketi'); ?></p>
                            <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                            <?php _e('Nije otkriveno', 'woocommerce-vip-paketi'); ?>
                            <p class="description"><?php _e('Instaliraj WooCommerce Memberships za automatsku VIP integraciju.', 'woocommerce-vip-paketi'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('WooCommerce Pretplate', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <?php if (class_exists('WC_Subscriptions')): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <strong><?php _e('Aktivno', 'woocommerce-vip-paketi'); ?></strong>
                            <p class="description"><?php _e('Korisnici sa aktivnim pretplatama automatski dobijaju VIP status.', 'woocommerce-vip-paketi'); ?></p>
                            <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                            <?php _e('Nije otkriveno', 'woocommerce-vip-paketi'); ?>
                            <p class="description"><?php _e('Instaliraj WooCommerce Subscriptions za automatsku VIP integraciju.', 'woocommerce-vip-paketi'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <!-- Hidden fields to preserve settings from other tabs -->
                <input type="hidden" name="wvp_enable_vip_pricing" value="<?php echo esc_attr(get_option('wvp_enable_vip_pricing', 'no')); ?>" />
                <input type="hidden" name="wvp_enable_packages" value="<?php echo esc_attr(get_option('wvp_enable_packages', 'no')); ?>" />
                <input type="hidden" name="wvp_debug_mode" value="<?php echo esc_attr(get_option('wvp_debug_mode', 'no')); ?>" />
                <input type="hidden" name="wvp_vip_price_label" value="<?php echo esc_attr(get_option('wvp_vip_price_label', 'VIP Cena')); ?>" />
                <input type="hidden" name="wvp_non_vip_display_format" value="<?php echo esc_attr(get_option('wvp_non_vip_display_format', 'both')); ?>" />
                <input type="hidden" name="wvp_enable_checkout_codes" value="<?php echo esc_attr(get_option('wvp_enable_checkout_codes', 'no')); ?>" />
                <input type="hidden" name="wvp_auto_registration" value="<?php echo esc_attr(get_option('wvp_auto_registration', 'no')); ?>" />
                <input type="hidden" name="wvp_email_notifications" value="<?php echo esc_attr(get_option('wvp_email_notifications', 'yes')); ?>" />
                <input type="hidden" name="wvp_vip_role_enabled" value="<?php echo esc_attr(get_option('wvp_vip_role_enabled', 'no')); ?>" />
                <input type="hidden" name="wvp_default_package_sizes" value="<?php echo esc_attr(get_option('wvp_default_package_sizes', '2,3,4,5,6')); ?>" />
                <input type="hidden" name="wvp_default_regular_discount" value="<?php echo esc_attr(get_option('wvp_default_regular_discount', '5')); ?>" />
                <input type="hidden" name="wvp_default_vip_discount" value="<?php echo esc_attr(get_option('wvp_default_vip_discount', '10')); ?>" />
                <input type="hidden" name="wvp_packages_allow_coupons" value="<?php echo esc_attr(get_option('wvp_packages_allow_coupons', 'no')); ?>" />
                <input type="hidden" name="wvp_package_url_slug" value="<?php echo esc_attr(get_option('wvp_package_url_slug', 'konfiguracija-paketa')); ?>" />
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="wvp-messages"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize color picker
    if ($.fn.wpColorPicker) {
        $('.wvp-color-picker').wpColorPicker();
    }
    
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const tab = $(this).attr('href').split('tab=')[1];
        window.location.href = '?page=wvp-settings&tab=' + tab;
    });
});
</script>