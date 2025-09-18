<?php
/**
 * VIP status display for user account page
 */

if (!defined('ABSPATH')) {
    exit;
}

$core = new WVP_Core();
$is_vip = $core->is_user_vip();
$user_id = get_current_user_id();

if (!$user_id) {
    return;
}

// Get VIP-related data
$vip_code_used = get_user_meta($user_id, '_wvp_vip_code_used', true);
$vip_since = get_user_meta($user_id, '_wvp_vip_since', true);
$membership_source = get_user_meta($user_id, '_wvp_membership_source', true);

// Get VIP usage statistics - temporarily disable until method is implemented
$vip_stats = null;
// $database = new WVP_Database();
// $vip_stats = $database->get_user_vip_stats($user_id);
?>

<div class="wvp-account-vip-status">
    <h3><?php _e('Status VIP Članstva', 'woocommerce-vip-paketi'); ?></h3>
    
    <div class="vip-status-card">
        <?php if ($is_vip): ?>
            <div class="vip-active-status">
                <div class="status-header">
                    <div class="status-icon vip-active">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="status-info">
                        <h4 class="status-title"><?php _e('VIP Član', 'woocommerce-vip-paketi'); ?></h4>
                        <p class="status-subtitle">
                            <?php 
                            if ($vip_since) {
                                printf(
                                    __('Član od %s', 'woocommerce-vip-paketi'),
                                    date_i18n(get_option('date_format'), strtotime($vip_since))
                                );
                            } else {
                                _e('Aktivni VIP Član', 'woocommerce-vip-paketi');
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="membership-details">
                    <?php if ($membership_source): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Tip članstva:', 'woocommerce-vip-paketi'); ?></span>
                            <span class="detail-value">
                                <?php
                                switch ($membership_source) {
                                    case 'wc_memberships':
                                        _e('WooCommerce Članstvo', 'woocommerce-vip-paketi');
                                        break;
                                    case 'wc_subscriptions':
                                        _e('WooCommerce Pretplata', 'woocommerce-vip-paketi');
                                        break;
                                    case 'vip_code':
                                        _e('VIP Kod', 'woocommerce-vip-paketi');
                                        break;
                                    case 'manual':
                                        _e('Ručno Dodeljeno', 'woocommerce-vip-paketi');
                                        break;
                                    default:
                                        _e('VIP Uloga', 'woocommerce-vip-paketi');
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($vip_code_used): ?>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Korišćeni VIP Kod:', 'woocommerce-vip-paketi'); ?></span>
                            <span class="detail-value vip-code"><?php echo esc_html($vip_code_used); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="vip-benefits">
                    <h5><?php _e('Vaše VIP Pogodnosti:', 'woocommerce-vip-paketi'); ?></h5>
                    <ul class="benefits-list">
                        <li>
                            <span class="benefit-icon">💰</span>
                            <?php _e('Specijalne cene na VIP proizvode', 'woocommerce-vip-paketi'); ?>
                        </li>
                        <li>
                            <span class="benefit-icon">📦</span>
                            <?php _e('Dodatni popusti na pakete', 'woocommerce-vip-paketi'); ?>
                        </li>
                        <li>
                            <span class="benefit-icon">⭐</span>
                            <?php _e('Prioritetan pristup novim proizvodima', 'woocommerce-vip-paketi'); ?>
                        </li>
                        <li>
                            <span class="benefit-icon">🎁</span>
                            <?php _e('Ekskluzivne VIP ponude i promocije', 'woocommerce-vip-paketi'); ?>
                        </li>
                    </ul>
                </div>
                
                <?php if ($vip_stats && isset($vip_stats['total_savings'])): ?>
                <div class="vip-savings-summary">
                    <h5><?php _e('Rezime VIP Ušteda:', 'woocommerce-vip-paketi'); ?></h5>
                    <div class="savings-grid">
                        <div class="savings-item">
                            <span class="savings-amount"><?php echo wc_price($vip_stats['total_savings']); ?></span>
                            <span class="savings-label"><?php _e('Ukupno Uštede', 'woocommerce-vip-paketi'); ?></span>
                        </div>
                        <div class="savings-item">
                            <span class="savings-amount"><?php echo esc_html($vip_stats['vip_orders']); ?></span>
                            <span class="savings-label"><?php _e('VIP Porudžbine', 'woocommerce-vip-paketi'); ?></span>
                        </div>
                        <div class="savings-item">
                            <span class="savings-amount"><?php echo esc_html($vip_stats['packages_purchased']); ?></span>
                            <span class="savings-label"><?php _e('Kupljeni Paketi', 'woocommerce-vip-paketi'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="vip-inactive-status">
                <div class="status-header">
                    <div class="status-icon vip-inactive">
                        <span class="dashicons dashicons-star-empty"></span>
                    </div>
                    <div class="status-info">
                        <h4 class="status-title"><?php _e('Regularni Član', 'woocommerce-vip-paketi'); ?></h4>
                        <p class="status-subtitle"><?php _e('Postanite VIP član za ekskluzivne pogodnosti', 'woocommerce-vip-paketi'); ?></p>
                    </div>
                </div>
                
                <div class="upgrade-benefits">
                    <h5><?php _e('Pogodnosti VIP Članstva:', 'woocommerce-vip-paketi'); ?></h5>
                    <ul class="benefits-list">
                        <li>
                            <span class="benefit-icon">💰</span>
                            <div>
                                <strong><?php _e('Specijalne VIP Cene', 'woocommerce-vip-paketi'); ?></strong>
                                <p><?php _e('Pristup ekskluzivnim VIP cenama na odabrane proizvode', 'woocommerce-vip-paketi'); ?></p>
                            </div>
                        </li>
                        <li>
                            <span class="benefit-icon">📦</span>
                            <div>
                                <strong><?php _e('Poboljšani Popusti na Pakete', 'woocommerce-vip-paketi'); ?></strong>
                                <p><?php _e('Dodatni popusti uz već postojeće popuste na pakete', 'woocommerce-vip-paketi'); ?></p>
                            </div>
                        </li>
                        <li>
                            <span class="benefit-icon">⭐</span>
                            <div>
                                <strong><?php _e('Prioritetan Pristup', 'woocommerce-vip-paketi'); ?></strong>
                                <p><?php _e('Budite prvi koji će pristupiti novim proizvodima i ograničenim ponudama', 'woocommerce-vip-paketi'); ?></p>
                            </div>
                        </li>
                        <li>
                            <span class="benefit-icon">🎁</span>
                            <div>
                                <strong><?php _e('Ekskluzivne Ponude', 'woocommerce-vip-paketi'); ?></strong>
                                <p><?php _e('Primajte promocije i specijalne ponude samo za VIP članove', 'woocommerce-vip-paketi'); ?></p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="vip-code-section">
                    <h5><?php _e('Imate VIP Kod?', 'woocommerce-vip-paketi'); ?></h5>
                    <form class="vip-code-form" id="vip-code-activation">
                        <div class="form-row">
                            <input type="text" 
                                   id="vip-code-input" 
                                   name="vip_code" 
                                   placeholder="<?php esc_attr_e('Unesite vaš VIP kod', 'woocommerce-vip-paketi'); ?>"
                                   required>
                            <button type="submit" class="activate-code-btn" id="activate-vip-code">
                                <?php _e('Aktiviraj', 'woocommerce-vip-paketi'); ?>
                            </button>
                        </div>
                        <div class="form-message" id="vip-code-message" style="display: none;"></div>
                    </form>
                </div>
                
                <div class="upgrade-actions">
                    <button type="button" class="learn-more-btn" id="learn-more-vip">
                        <?php _e('Saznajte Više o VIP-u', 'woocommerce-vip-paketi'); ?>
                    </button>
                    <button type="button" class="contact-us-btn" id="contact-for-vip">
                        <?php _e('Kontaktirajte Nas za VIP Pristup', 'woocommerce-vip-paketi'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // VIP code activation
    $('#vip-code-activation').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#activate-vip-code');
        const $message = $('#vip-code-message');
        const vipCode = $('#vip-code-input').val().trim();
        
        if (!vipCode) {
            showMessage('Molimo unesite VIP kod.', 'error');
            return;
        }
        
        $btn.prop('disabled', true).text('Aktiviram...');
        $message.hide();
        
        $.ajax({
            url: wvp_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wvp_activate_vip_code',
                nonce: wvp_public_ajax.nonce,
                vip_code: vipCode
            },
            success: function(response) {
                if (response.success) {
                    showMessage('VIP kod uspešno aktiviran! Molimo osvežite stranicu.', 'success');
                    
                    // Refresh page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage(response.data || 'Neispravan ili istekao VIP kod.', 'error');
                }
            },
            error: function() {
                showMessage('Greška prilikom aktivacije VIP koda. Molimo pokušajte ponovo.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Aktiviraj');
            }
        });
    });
    
    // Learn more about VIP
    $('#learn-more-vip').on('click', function() {
        // Scroll to packages or show VIP info modal
        const packagesUrl = wvp_public_ajax.packages_url;
        if (packagesUrl) {
            window.location.href = packagesUrl;
        } else {
            showVIPInfoModal();
        }
    });
    
    // Contact for VIP
    $('#contact-for-vip').on('click', function() {
        // Open contact form or redirect to contact page
        const contactUrl = wvp_public_ajax.contact_url;
        if (contactUrl) {
            window.location.href = contactUrl;
        } else {
            // Fallback to email
            window.location.href = 'mailto:' + (wvp_public_ajax.contact_email || 'info@example.com') + '?subject=' + encodeURIComponent('Upit o VIP Članstvu');
        }
    });
    
    function showMessage(message, type) {
        const $message = $('#vip-code-message');
        $message.removeClass('success error warning').addClass(type);
        $message.text(message).show();
        
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut();
            }, 3000);
        }
    }
    
    function showVIPInfoModal() {
        const vipInfoHtml = `
            <div class="wvp-vip-info-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Pogodnosti VIP Članstva</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="vip-benefits">
                            <div class="benefit-item">
                                <span class="benefit-icon">💰</span>
                                <div>
                                    <strong>Specijalne VIP Cene</strong>
                                    <p>Pristup ekskluzivnim VIP cenama na odabrane proizvode</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-icon">📦</span>
                                <div>
                                    <strong>Poboljšani Popusti na Pakete</strong>
                                    <p>Dodatni popusti uz već postojeće popuste na pakete</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-icon">⭐</span>
                                <div>
                                    <strong>Prioritetan Pristup</strong>
                                    <p>Budite prvi koji će pristupiti novim proizvodima i ograničenim ponudama</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <span class="benefit-icon">🎁</span>
                                <div>
                                    <strong>Ekskluzivne Ponude</strong>
                                    <p>Primajte promocije i specijalne ponude samo za VIP članove</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <p>Zainteresovani ste da postanete VIP član? Kontaktirajte nas za više informacija.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(vipInfoHtml);
        $('.wvp-vip-info-modal').fadeIn();
    }
    
    // Close VIP info modal
    $(document).on('click', '.wvp-vip-info-modal .modal-close, .wvp-vip-info-modal .modal-backdrop', function() {
        $('.wvp-vip-info-modal').fadeOut(function() {
            $(this).remove();
        });
    });
    
    function __(text, domain) {
        return text; // Simplified - in real implementation would use wp.i18n
    }
});
</script>

<style>
.wvp-account-vip-status {
    margin-bottom: 30px;
}

.wvp-account-vip-status h3 {
    margin-bottom: 20px;
    color: #1d2327;
    font-size: 20px;
}

.vip-status-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.status-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.status-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.status-icon.vip-active {
    background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    color: #fff;
    animation: vipGlow 2s ease-in-out infinite alternate;
}

.status-icon.vip-inactive {
    background: #6c757d;
    color: #fff;
}

.status-title {
    margin: 0 0 5px 0;
    color: #1d2327;
    font-size: 18px;
}

.status-subtitle {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

.membership-details,
.upgrade-benefits,
.vip-benefits,
.vip-savings-summary,
.vip-code-section,
.upgrade-actions {
    padding: 20px;
}

.membership-details {
    border-bottom: 1px solid #f1f1f1;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: #646970;
    font-weight: 500;
}

.detail-value {
    color: #1d2327;
    font-weight: 600;
}

.detail-value.vip-code {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.vip-benefits h5,
.upgrade-benefits h5,
.vip-savings-summary h5,
.vip-code-section h5 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 16px;
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefits-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.benefits-list li:last-child {
    border-bottom: none;
}

.benefit-icon {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

.upgrade-benefits .benefits-list li {
    align-items: flex-start;
}

.upgrade-benefits .benefits-list li div strong {
    display: block;
    margin-bottom: 4px;
    color: #1d2327;
}

.upgrade-benefits .benefits-list li div p {
    margin: 0;
    font-size: 14px;
    color: #646970;
    line-height: 1.4;
}

.savings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.savings-item {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-radius: 6px;
    border: 1px solid #28a745;
}

.savings-amount {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #155724;
    margin-bottom: 5px;
}

.savings-label {
    font-size: 12px;
    color: #155724;
    text-transform: uppercase;
    font-weight: 600;
}

.vip-code-section {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.vip-code-form .form-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.vip-code-form input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.activate-code-btn {
    padding: 10px 20px;
    background: #007cba;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.activate-code-btn:hover:not(:disabled) {
    background: #005a87;
}

.activate-code-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-message {
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
}

.form-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #28a745;
}

.form-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #dc3545;
}

.upgrade-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
}

.learn-more-btn,
.contact-us-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.learn-more-btn {
    background: #007cba;
    color: #fff;
}

.learn-more-btn:hover {
    background: #005a87;
}

.contact-us-btn {
    background: #28a745;
    color: #fff;
}

.contact-us-btn:hover {
    background: #218838;
}

@keyframes vipGlow {
    0% {
        box-shadow: 0 0 5px rgba(212, 160, 23, 0.5);
    }
    100% {
        box-shadow: 0 0 20px rgba(212, 160, 23, 0.8);
    }
}

@media (max-width: 768px) {
    .status-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .savings-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .vip-code-form .form-row {
        flex-direction: column;
    }
    
    .upgrade-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .learn-more-btn,
    .contact-us-btn {
        width: 100%;
    }
}
</style>