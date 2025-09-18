<?php
/**
 * Package configuration section template
 */

if (!defined('ABSPATH')) {
    exit;
}

$package_id = get_the_ID();
$packages_admin = new WVP_Admin_Packages();
$package_data = $packages_admin->get_package_data($package_id);
$core = new WVP_Core();
$is_vip = $core->is_user_vip();

if (!$package_data) {
    return;
}
?>

<div class="wvp-package-configuration" id="wvp-package-config">
    <h3><?php echo esc_html(get_option('wvp_page_title_text', __('PODESI SVOJ PAKET', 'woocommerce-vip-paketi'))); ?></h3>
    
    <div class="package-info">
        <p class="package-description">
            <?php 
            $description_text = get_option('wvp_page_description_text', __('Izaberi između %d i %d proizvoda iz našeg kuriranog izbora. Što više dodaš, više uštetiš!', 'woocommerce-vip-paketi'));
            printf(
                $description_text,
                $package_data['min_items'],
                $package_data['max_items']
            ); 
            ?>
        </p>
        
        <?php if (!$is_vip && $package_data['show_for_non_vip'] === 'yes'): ?>
        <div class="vip-upgrade-notice">
            <span class="vip-icon">⭐</span>
            <span class="vip-message">
                <?php _e('VIP članovi dobijaju dodatne popuste na sve pakete!', 'woocommerce-vip-paketi'); ?>
                <a href="#" class="vip-learn-more"><?php _e('Saznaj više', 'woocommerce-vip-paketi'); ?></a>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <div class="package-size-selection">
        <h4><?php echo esc_html(get_option('wvp_page_subtitle_text', __('IZABERI VELIČINU PAKETA', 'woocommerce-vip-paketi'))); ?></h4>
        <div class="wvp-package-sizes" data-min="<?php echo esc_attr($package_data['min_items']); ?>" data-max="<?php echo esc_attr($package_data['max_items']); ?>">
            <?php foreach ($package_data['package_sizes'] as $size): ?>
                <?php 
                $regular_discount = isset($package_data['regular_discounts'][$size]) ? $package_data['regular_discounts'][$size] : 0;
                $vip_discount = isset($package_data['vip_discounts'][$size]) ? $package_data['vip_discounts'][$size] : 0;
                ?>
                <div class="wvp-package-size" data-size="<?php echo esc_attr($size); ?>" data-regular-discount="<?php echo esc_attr($regular_discount); ?>" data-vip-discount="<?php echo esc_attr($vip_discount); ?>">
                    <div class="size-header">
                        <span class="wvp-package-size-label">
                            <?php printf(_n('%d Proizvod', '%d Proizvoda', $size, 'woocommerce-vip-paketi'), $size); ?>
                        </span>
                        <span class="size-discount">
                            <?php if ($is_vip && $vip_discount > 0): ?>
                                <span class="vip-discount-badge">
                                    <?php printf(__('-%d%% VIP', 'woocommerce-vip-paketi'), $regular_discount + $vip_discount); ?>
                                </span>
                            <?php elseif ($regular_discount > 0): ?>
                                <span class="regular-discount-badge">
                                    <?php printf(__('-%d%%', 'woocommerce-vip-paketi'), $regular_discount); ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($vip_discount > 0 && !$is_vip): ?>
                    <div class="vip-additional-discount">
                        <small><?php printf(__('VIP članovi dobijaju dodatnih %d%% popusta', 'woocommerce-vip-paketi'), $vip_discount); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="size-selection-help">
            <small>
                <span class="dashicons dashicons-info"></span>
                <?php _e('Izaberi željenu veličinu paketa da vidiš dostupne proizvode i cene.', 'woocommerce-vip-paketi'); ?>
            </small>
        </div>
    </div>

    <div class="selected-size-info" style="display: none;">
        <div class="size-info-content">
            <h4><?php _e('Detalji Paketa', 'woocommerce-vip-paketi'); ?></h4>
            <div class="size-details">
                <div class="detail-item">
                    <strong><?php _e('Proizvodi za izbor:', 'woocommerce-vip-paketi'); ?></strong>
                    <span class="selected-size-count">0</span>
                </div>
                <div class="detail-item">
                    <strong><?php _e('Tvoj popust:', 'woocommerce-vip-paketi'); ?></strong>
                    <span class="selected-discount">0%</span>
                </div>
                <?php if ($is_vip): ?>
                <div class="detail-item vip-bonus">
                    <strong><?php _e('VIP bonus popust:', 'woocommerce-vip-paketi'); ?></strong>
                    <span class="vip-bonus-discount">0%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const $packageSizes = $('.wvp-package-size');
    const $selectedInfo = $('.selected-size-info');
    const isVip = <?php echo $is_vip ? 'true' : 'false'; ?>;
    
    // Package size selection
    $packageSizes.on('click', function() {
        const $this = $(this);
        
        // Remove selection from others
        $packageSizes.removeClass('selected');
        
        // Add selection to current
        $this.addClass('selected');
        
        // Get selected size data
        const size = $this.data('size');
        const regularDiscount = $this.data('regular-discount');
        const vipDiscount = $this.data('vip-discount');
        const totalDiscount = isVip ? regularDiscount + vipDiscount : regularDiscount;
        
        // Update info display
        $('.selected-size-count').text(size);
        $('.selected-discount').text(totalDiscount + '%');
        
        if (isVip) {
            $('.vip-bonus-discount').text(vipDiscount + '%');
        }
        
        // Show selected info
        $selectedInfo.slideDown();
        
        // Trigger custom event for other components
        $(document).trigger('wvp_package_size_selected', {
            size: size,
            regularDiscount: regularDiscount,
            vipDiscount: vipDiscount,
            isVip: isVip
        });
        
        // Scroll to discount table section
        setTimeout(function() {
            const $discountSection = $('#wvp-discount-table');
            if ($discountSection.length) {
                $('html, body').animate({
                    scrollTop: $discountSection.offset().top - 100
                }, 800);
            }
        }, 500);
    });
    
    // VIP learn more
    $('.vip-learn-more').on('click', function(e) {
        e.preventDefault();
        
        // Show VIP info modal or redirect to VIP info page
        const vipInfoHtml = `
            <div class="wvp-vip-info-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php echo esc_js(get_option('wvp_modal_title', 'Pogodnosti VIP Članstva')); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="vip-benefits">
                            <div class="benefit-item">
                                <span class="benefit-icon">💰</span>
                                <div>
                                    <strong>Dodatni Popusti na Pakete</strong>
                                    <p>Dobij dodatne uštede na vrh redovnih paket popusta</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-icon">⭐</span>
                                <div>
                                    <strong>Specijalne Cene Proizvoda</strong>
                                    <p>Pristup VIP cenama na pojedinačne proizvode</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-icon">🎁</span>
                                <div>
                                    <strong>Ekskluzivan Pristup</strong>
                                    <p>Prioritetan pristup novim proizvodima i specijalnim ponudama</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <p>Zainteresovan si da postaneš VIP član? <a href="https://eliksirvitalnosti.com/postanite-vip-clan/" target="_blank">Saznaj više o VIP članstvu</a></p>
                            <div class="modal-cta">
                                <a href="https://eliksirvitalnosti.com/postanite-vip-clan/" class="wvp-become-vip-btn" target="_blank">
                                    Postani VIP Član
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(vipInfoHtml);
        $('.wvp-vip-info-modal').fadeIn();
    });
    
    // Close VIP info modal
    $(document).on('click', '.wvp-vip-info-modal .modal-close, .wvp-vip-info-modal .modal-backdrop', function() {
        $('.wvp-vip-info-modal').fadeOut(function() {
            $(this).remove();
        });
    });
});
</script>

<style>
.wvp-package-configuration {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wvp-package-configuration h3 {
    margin-top: 0;
    color: #1d2327;
    font-size: 24px;
    text-align: center;
}

.package-info {
    margin-bottom: 30px;
}

.package-description {
    font-size: 16px;
    color: #646970;
    margin-bottom: 20px;
    line-height: 1.6;
}

.vip-upgrade-notice {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 1px solid #ffc107;
    border-radius: 6px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.vip-icon {
    font-size: 20px;
    color: #856404;
}

.vip-message {
    color: #856404;
    font-weight: 500;
}

.vip-learn-more {
    color: #007cba;
    text-decoration: none;
    font-weight: bold;
    margin-left: 8px;
}

.vip-learn-more:hover {
    text-decoration: underline;
}

.package-size-selection h4 {
    margin-bottom: 20px;
    color: #1d2327;
    text-align: center;
}

.wvp-package-sizes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.wvp-package-size {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    position: relative;
}

.wvp-package-size:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.wvp-package-size.selected {
    background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    border-color: #b8860b;
    color: #fff;
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(212, 160, 23, 0.3);
}

.size-header {
    margin-bottom: 10px;
}

.wvp-package-size-label {
    font-size: 18px;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
}

.size-discount {
    display: block;
}

.regular-discount-badge,
.vip-discount-badge {
    background: #28a745;
    color: #fff;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.wvp-package-size.selected .regular-discount-badge,
.wvp-package-size.selected .vip-discount-badge {
    background: rgba(255,255,255,0.2);
}

.vip-additional-discount {
    margin-top: 8px;
    font-style: italic;
}

.wvp-package-size.selected .vip-additional-discount {
    opacity: 0.9;
}

.size-selection-help {
    color: #646970;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.selected-size-info {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    border: 1px solid #28a745;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
}

.size-info-content h4 {
    margin-top: 0;
    color: #155724;
}

.size-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: rgba(255,255,255,0.6);
    border-radius: 4px;
}

.detail-item.vip-bonus {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 1px solid #ffc107;
}

.wvp-vip-info-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wvp-vip-info-modal .modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.wvp-vip-info-modal .modal-header {
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wvp-vip-info-modal .modal-header h3 {
    margin: 0;
}

.wvp-vip-info-modal .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.wvp-vip-info-modal .modal-body {
    padding: 20px;
}

.vip-benefits {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 20px;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.benefit-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.benefit-item strong {
    display: block;
    margin-bottom: 5px;
    color: #1d2327;
}

.benefit-item p {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

.modal-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.modal-cta {
    margin-top: 15px;
}

.wvp-become-vip-btn {
    display: inline-block;
    background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    color: #fff;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.wvp-become-vip-btn:hover {
    background: linear-gradient(135deg, #b8860b 0%, #996f0a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212, 160, 23, 0.3);
    color: #fff;
    text-decoration: none;
}

@media (max-width: 768px) {
    .wvp-package-configuration {
        padding: 20px;
    }
    
    .wvp-package-sizes {
        grid-template-columns: 1fr;
    }
    
    .size-details {
        grid-template-columns: 1fr;
    }
    
    .detail-item {
        flex-direction: column;
        text-align: center;
        gap: 5px;
    }
}
</style>