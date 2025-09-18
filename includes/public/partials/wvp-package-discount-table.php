<?php
/**
 * Package discount table template
 */

if (!defined('ABSPATH')) {
    exit;
}

$package_id = get_the_ID();
$packages_admin = new WVP_Admin_Packages();
$package_data = $packages_admin->get_package_data($package_id);
$core = new WVP_Core();
$is_vip = $core->is_user_vip();

if (!$package_data || empty($package_data['package_sizes'])) {
    return;
}
?>

<div class="wvp-discount-table" id="wvp-discount-table">
    <div class="table-header">
        <h3><?php echo esc_html(get_option('wvp_discount_table_title', __('POPUSTI ZA PAKETE', 'woocommerce-vip-paketi'))); ?></h3>
        <p class="table-description">
            <?php _e('Uštedi više kad kupiš više! Pogledaj naše popuste za pakete dole.', 'woocommerce-vip-paketi'); ?>
        </p>
    </div>

    <div class="table-wrapper">
        <table class="discount-table">
            <thead>
                <tr>
                    <th class="size-column"><?php _e('Veličina Paketa', 'woocommerce-vip-paketi'); ?></th>
                    <th class="regular-column"><?php _e('Redovan Popust', 'woocommerce-vip-paketi'); ?></th>
                    <th class="vip-column <?php echo $is_vip ? 'vip-active' : 'vip-teaser'; ?>">
                        <?php _e('VIP Dodatni Popust', 'woocommerce-vip-paketi'); ?>
                        <?php if (!$is_vip): ?>
                        <span class="vip-only-badge"><?php _e('Samo VIP', 'woocommerce-vip-paketi'); ?></span>
                        <?php endif; ?>
                    </th>
                    <th class="total-column"><?php _e('Ukupna Ušteda', 'woocommerce-vip-paketi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($package_data['package_sizes'] as $size): ?>
                    <?php 
                    $regular_discount = isset($package_data['regular_discounts'][$size]) ? floatval($package_data['regular_discounts'][$size]) : 0;
                    $vip_discount = isset($package_data['vip_discounts'][$size]) ? floatval($package_data['vip_discounts'][$size]) : 0;
                    $total_discount = $is_vip ? $regular_discount + $vip_discount : $regular_discount;
                    $has_vip_bonus = $vip_discount > 0;
                    ?>
                    <tr class="discount-row" data-size="<?php echo esc_attr($size); ?>" data-regular-discount="<?php echo esc_attr($regular_discount); ?>" data-vip-discount="<?php echo esc_attr($vip_discount); ?>">
                        <td class="size-cell">
                            <div class="size-info">
                                <span class="size-number"><?php echo esc_html($size); ?></span>
                                <span class="size-label"><?php printf(_n('proizvod', 'proizvoda', $size, 'woocommerce-vip-paketi')); ?></span>
                            </div>
                        </td>
                        <td class="regular-cell">
                            <?php if ($regular_discount > 0): ?>
                                <div class="discount-value regular-discount">
                                    <span class="percentage"><?php echo number_format($regular_discount, 1); ?>%</span>
                                    <span class="discount-label"><?php _e('popust', 'woocommerce-vip-paketi'); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="no-discount">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="vip-cell <?php echo $is_vip ? 'user-vip' : 'user-regular'; ?>">
                            <?php if ($has_vip_bonus): ?>
                                <div class="discount-value vip-discount">
                                    <span class="percentage">+<?php echo number_format($vip_discount, 1); ?>%</span>
                                    <span class="discount-label"><?php _e('dodatno', 'woocommerce-vip-paketi'); ?></span>
                                </div>
                                <?php if (!$is_vip): ?>
                                <div class="vip-overlay">
                                    <span class="unlock-text"><?php _e('Otključaj VIP', 'woocommerce-vip-paketi'); ?></span>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="no-discount">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="total-cell">
                            <div class="total-discount <?php echo $is_vip && $has_vip_bonus ? 'vip-total' : 'regular-total'; ?>">
                                <span class="total-percentage"><?php echo number_format($total_discount, 1); ?>%</span>
                                <span class="total-label"><?php _e('ukupno', 'woocommerce-vip-paketi'); ?></span>
                                <?php if ($is_vip && $has_vip_bonus): ?>
                                <span class="vip-badge"><?php _e('VIP', 'woocommerce-vip-paketi'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$is_vip && $has_vip_bonus): ?>
                            <div class="potential-savings">
                                <small><?php printf(__('Možeš uštedeti %s%% ukupno!', 'woocommerce-vip-paketi'), number_format($regular_discount + $vip_discount, 1)); ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$is_vip): ?>
    <div class="vip-upgrade-cta">
        <div class="cta-content">
            <div class="cta-icon">⭐</div>
            <div class="cta-text">
                <h4>Otključaj VIP Popuste</h4>
                <p>Postani VIP član da pristupiš dodatnim popustima na sve pakete i ekskluzivnim cenama na pojedinačne proizvode. <a href="https://eliksirvitalnosti.com/postanite-vip-clan/" target="_blank">Saznaj više o VIP članstvu</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="discount-explanation">
        <h4><?php echo esc_html(get_option('wvp_discount_explanation_title', __('KAKO FUNKCIONIŠU POPUSTI ZA PAKETE', 'woocommerce-vip-paketi'))); ?></h4>
        <ul>
            <li><?php _e('Redovni popusti važe za sve kupce pri kupovini paketa', 'woocommerce-vip-paketi'); ?></li>
            <li><?php _e('VIP članovi dobijaju dodatne popuste na vrh redovnih ušteda', 'woocommerce-vip-paketi'); ?></li>
            <li><?php _e('Popusti se kalkulišu na osnovu ukupne vrednosti izabranih proizvoda', 'woocommerce-vip-paketi'); ?></li>
            <li><?php _e('Veći paketi pružaju bolju vrednost sa višim procentima popusta', 'woocommerce-vip-paketi'); ?></li>
        </ul>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const isVip = <?php echo $is_vip ? 'true' : 'false'; ?>;
    
    // Highlight selected package size
    $(document).on('wvp_package_size_selected', function(event, data) {
        $('.discount-row').removeClass('selected-size');
        $('.discount-row[data-size="' + data.size + '"]').addClass('selected-size');
        
        // Scroll to discount table
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('#wvp-discount-table').offset().top - 100
            }, 500);
        }, 200);
    });
    
    // VIP upgrade button
    $('.vip-upgrade-btn').on('click', function(e) {
        // If it's a button (not a link), show modal
        if ($(this).is('button')) {
            e.preventDefault();
            showVipUpgradeModal();
        }
        // If it's a link, let it navigate normally
    });
    
    // Animate table rows on scroll
    function animateTableRows() {
        $('.discount-row').each(function(index) {
            const $row = $(this);
            const elementTop = $row.offset().top;
            const elementBottom = elementTop + $row.outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();

            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                setTimeout(function() {
                    $row.addClass('visible');
                }, index * 100);
            }
        });
    }
    
    // Initialize animations
    $(window).on('scroll', throttle(animateTableRows, 100));
    animateTableRows(); // Initial check
    
    function showVipUpgradeModal() {
        const modalHtml = `
            <div class="wvp-vip-upgrade-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php esc_js(_e('Pogodnosti VIP Članstva', 'woocommerce-vip-paketi')); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="vip-benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon">💰</div>
                                <h4><?php esc_js(_e('Popusti za Pakete', 'woocommerce-vip-paketi')); ?></h4>
                                <p><?php esc_js(_e('Dobij dodatne popuste na sve veličine paketa, kumulativno sa redovnim uštedama', 'woocommerce-vip-paketi')); ?></p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">⭐</div>
                                <h4><?php esc_js(_e('VIP Cene Proizvoda', 'woocommerce-vip-paketi')); ?></h4>
                                <p><?php esc_js(_e('Pristup specijalnim VIP cenama na pojedinačne proizvode kroz celu prodavnicu', 'woocommerce-vip-paketi')); ?></p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">🎁</div>
                                <h4><?php esc_js(_e('Ekskluzivan Pristup', 'woocommerce-vip-paketi')); ?></h4>
                                <p><?php esc_js(_e('Prioritetan pristup novim proizvodima, rasprodajama i paketima samo za VIP', 'woocommerce-vip-paketi')); ?></p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">🚀</div>
                                <h4><?php esc_js(_e('Prioritetna Podrška', 'woocommerce-vip-paketi')); ?></h4>
                                <p><?php esc_js(_e('Brza korisnička podrška i posvećena VIP pomoć', 'woocommerce-vip-paketi')); ?></p>
                            </div>
                        </div>
                        <div class="modal-cta">
                            <p><strong><?php esc_js(_e('Spreman si da otključaš VIP pogodnosti?', 'woocommerce-vip-paketi')); ?></strong></p>
                            <p><?php esc_js(_e('Kontaktiraj nas da saznaš o VIP opcijama članstva i počni da štediš danas.', 'woocommerce-vip-paketi')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('.wvp-vip-upgrade-modal').fadeIn();
    }
    
    // Close modal
    $(document).on('click', '.wvp-vip-upgrade-modal .modal-close, .wvp-vip-upgrade-modal .modal-backdrop', function() {
        $('.wvp-vip-upgrade-modal').fadeOut(function() {
            $(this).remove();
        });
    });
    
    function throttle(func, delay) {
        let timeoutId;
        let lastExecTime = 0;
        
        return function() {
            const currentTime = Date.now();
            
            if (currentTime - lastExecTime > delay) {
                func.apply(this, arguments);
                lastExecTime = currentTime;
            } else {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    func.apply(this, arguments);
                    lastExecTime = Date.now();
                }, delay - (currentTime - lastExecTime));
            }
        };
    }
});
</script>

<style>
.wvp-discount-table {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 30px;
    text-align: center;
    border-bottom: 1px solid #dee2e6;
}

.table-header h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 24px;
}

.table-description {
    margin: 0;
    color: #646970;
    font-size: 16px;
}

.table-wrapper {
    overflow-x: auto;
}

.discount-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.discount-table th,
.discount-table td {
    padding: 20px 15px;
    text-align: center;
    border-bottom: 1px solid #f0f0f1;
}

.discount-table th {
    background: #fafbfc;
    font-weight: 600;
    color: #1d2327;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vip-column.vip-teaser {
    position: relative;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
}

.vip-only-badge {
    background: #ffc107;
    color: #856404;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    margin-left: 8px;
}

.discount-row {
    transition: all 0.3s ease;
    opacity: 0.7;
    transform: translateX(-10px);
}

.discount-row.visible {
    opacity: 1;
    transform: translateX(0);
}

.discount-row:hover {
    background: #f8f9fa;
}

.discount-row.selected-size {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    border-left: 4px solid #28a745;
}

.size-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.size-number {
    font-size: 24px;
    font-weight: bold;
    color: #1d2327;
    line-height: 1;
}

.size-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
}

.discount-value {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.percentage {
    font-size: 20px;
    font-weight: bold;
    line-height: 1;
}

.discount-label {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
}

.regular-discount .percentage {
    color: #007cba;
}

.vip-discount .percentage {
    color: #d4a017;
}

.vip-cell {
    position: relative;
}

.vip-cell.user-regular {
    filter: blur(1px);
}

.vip-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.unlock-text {
    background: #d4a017;
    color: #fff;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
}

.total-discount {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    position: relative;
}

.total-percentage {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
}

.total-label {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
}

.regular-total .total-percentage {
    color: #007cba;
}

.vip-total .total-percentage {
    color: #d4a017;
}

.vip-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #d4a017;
    color: #fff;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 8px;
    font-weight: bold;
    text-transform: uppercase;
}

.potential-savings {
    margin-top: 8px;
}

.potential-savings small {
    color: #28a745;
    font-weight: 500;
}

.no-discount {
    color: #999;
    font-size: 18px;
}

.vip-upgrade-cta {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-top: 1px solid #ffc107;
    padding: 30px;
}

.cta-content {
    display: flex;
    align-items: center;
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.cta-icon {
    font-size: 48px;
    flex-shrink: 0;
}

.cta-text {
    flex: 1;
}

.cta-text h4 {
    margin: 0 0 8px 0;
    color: #856404;
    font-size: 20px;
}

.cta-text p {
    margin: 0;
    color: #856404;
    font-size: 14px;
}

.vip-upgrade-btn {
    background: #d4a017;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.vip-upgrade-btn:hover {
    background: #b8860b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212, 160, 23, 0.3);
}

.discount-explanation {
    background: #f8f9fa;
    padding: 30px;
    border-top: 1px solid #dee2e6;
}

.discount-explanation h4 {
    margin: 0 0 15px 0;
    color: #1d2327;
}

.discount-explanation ul {
    margin: 0;
    padding-left: 20px;
    color: #646970;
}

.discount-explanation li {
    margin-bottom: 8px;
    line-height: 1.5;
}

/* VIP Upgrade Modal */
.wvp-vip-upgrade-modal {
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

.wvp-vip-upgrade-modal .modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 700px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.wvp-vip-upgrade-modal .modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wvp-vip-upgrade-modal .modal-header h3 {
    margin: 0;
    color: #1d2327;
}

.wvp-vip-upgrade-modal .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.wvp-vip-upgrade-modal .modal-body {
    padding: 30px;
}

.vip-benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.benefit-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.benefit-icon {
    font-size: 32px;
    margin-bottom: 15px;
}

.benefit-card h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 16px;
}

.benefit-card p {
    margin: 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.5;
}

.modal-cta {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.modal-cta p {
    margin-bottom: 10px;
    color: #646970;
}

@media (max-width: 768px) {
    .cta-content {
        flex-direction: column;
        text-align: center;
    }
    
    .discount-table th,
    .discount-table td {
        padding: 15px 8px;
        font-size: 13px;
    }
    
    .size-number {
        font-size: 20px;
    }
    
    .percentage {
        font-size: 16px;
    }
    
    .total-percentage {
        font-size: 20px;
    }
    
    .vip-benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .discount-explanation {
        padding: 20px;
    }
}
</style>