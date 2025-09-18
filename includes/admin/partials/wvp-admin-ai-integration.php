<?php
if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Handle form submission for AI settings - General tab
if (isset($_POST['wvp_ai_general_save'])) {
    check_admin_referer('wvp_ai_general_save');

    // OpenAI settings
    $openai_api_key = isset($_POST['openai_api_key']) ? sanitize_text_field($_POST['openai_api_key']) : '';
    update_option('wvp_health_quiz_openai_api_key', $openai_api_key);

    $openai_enabled = isset($_POST['openai_enabled']) ? 1 : 0;
    update_option('wvp_health_quiz_openai_enabled', $openai_enabled);

    $package_logic = isset($_POST['package_logic']) ? sanitize_text_field($_POST['package_logic']) : 'most_symptoms';
    update_option('wvp_health_quiz_package_logic', $package_logic);

    echo '<div class="updated"><p>Osnovna podešavanja AI integracije su sačuvana.</p></div>';
}

// Handle form submission for AI settings - Monitoring tab
if (isset($_POST['wvp_ai_monitoring_save'])) {
    check_admin_referer('wvp_ai_monitoring_save');

    // AI Usage Limits and Monitoring
    $daily_limit = isset($_POST['ai_daily_limit']) ? intval($_POST['ai_daily_limit']) : 100;
    update_option('wvp_ai_daily_limit', $daily_limit);

    $monthly_limit = isset($_POST['ai_monthly_limit']) ? intval($_POST['ai_monthly_limit']) : 1000;
    update_option('wvp_ai_monthly_limit', $monthly_limit);

    $cost_per_request = isset($_POST['ai_cost_per_request']) ? floatval($_POST['ai_cost_per_request']) : 0.02;
    update_option('wvp_ai_cost_per_request', $cost_per_request);

    $budget_limit = isset($_POST['ai_budget_limit']) ? floatval($_POST['ai_budget_limit']) : 50.00;
    update_option('wvp_ai_budget_limit', $budget_limit);

    $enable_notifications = isset($_POST['ai_enable_notifications']) ? 1 : 0;
    update_option('wvp_ai_enable_notifications', $enable_notifications);

    $notification_email = isset($_POST['ai_notification_email']) ? sanitize_email($_POST['ai_notification_email']) : get_option('admin_email');
    update_option('wvp_ai_notification_email', $notification_email);

    $auto_disable_threshold = isset($_POST['ai_auto_disable_threshold']) ? intval($_POST['ai_auto_disable_threshold']) : 90;
    update_option('wvp_ai_auto_disable_threshold', $auto_disable_threshold);

    echo '<div class="updated"><p>Monitoring podešavanja su sačuvana.</p></div>';
}

// Handle form submission for AI settings - Packages tab
if (isset($_POST['wvp_ai_products_save'])) {
    check_admin_referer('wvp_ai_products_save');

    // Save package discount settings
    update_option('wvp_ai_discount_2_products', intval($_POST['discount_2_products'] ?? 10));
    update_option('wvp_ai_discount_3_products', intval($_POST['discount_3_products'] ?? 12));
    update_option('wvp_ai_discount_4_products', intval($_POST['discount_4_products'] ?? 16));
    update_option('wvp_ai_discount_6_products', intval($_POST['discount_6_products'] ?? 20));
    update_option('wvp_ai_vip_additional_discount', intval($_POST['vip_additional_discount'] ?? 10));

    // Save allowed products for AI recommendations
    $allowed_products = isset($_POST['allowed_products']) ? array_map('intval', $_POST['allowed_products']) : array();
    update_option('wvp_ai_allowed_products', $allowed_products);

    // Save product characteristics
    if (isset($_POST['product_characteristics']) && is_array($_POST['product_characteristics'])) {
        foreach ($_POST['product_characteristics'] as $product_id => $characteristics) {
            $product_id = intval($product_id);
            $characteristics = sanitize_textarea_field($characteristics);
            update_option('wvp_ai_product_characteristics_' . $product_id, $characteristics);
        }
    }

    echo '<div class="updated"><p>Proizvodi i podešavanja za AI preporuke su sačuvani.</p></div>';
}

// Handle form submission for AI settings - Advanced tab
if (isset($_POST['wvp_ai_advanced_save'])) {
    check_admin_referer('wvp_ai_advanced_save');

    // Advanced AI settings
    $ai_system_prompt = isset($_POST['ai_system_prompt']) ? sanitize_textarea_field($_POST['ai_system_prompt']) : '';
    update_option('wvp_ai_system_prompt', $ai_system_prompt);

    $ai_restrictions = isset($_POST['ai_restrictions']) ? sanitize_textarea_field($_POST['ai_restrictions']) : '';
    update_option('wvp_ai_restrictions', $ai_restrictions);

    $ai_recommendation_prompt = isset($_POST['ai_recommendation_prompt']) ? sanitize_textarea_field($_POST['ai_recommendation_prompt']) : '';
    update_option('wvp_ai_recommendation_prompt', $ai_recommendation_prompt);

    $ai_temperature = isset($_POST['ai_temperature']) ? floatval($_POST['ai_temperature']) : 0.7;
    update_option('wvp_ai_temperature', $ai_temperature);

    $ai_max_tokens = isset($_POST['ai_max_tokens']) ? intval($_POST['ai_max_tokens']) : 1000;
    update_option('wvp_ai_max_tokens', $ai_max_tokens);

    $ai_model = isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : 'gpt-4';
    update_option('wvp_ai_model', $ai_model);

    $ai_timeout = isset($_POST['ai_timeout']) ? intval($_POST['ai_timeout']) : 30;
    update_option('wvp_ai_timeout', $ai_timeout);

    $ai_debug_mode = isset($_POST['ai_debug_mode']) ? 1 : 0;
    update_option('wvp_ai_debug_mode', $ai_debug_mode);

    $ai_dosage_priorities = isset($_POST['ai_dosage_priorities']) ? sanitize_textarea_field($_POST['ai_dosage_priorities']) : '';
    update_option('wvp_ai_dosage_priorities', $ai_dosage_priorities);

    echo '<div class="updated"><p>Napredne opcije su sačuvane.</p></div>';
}

// Handle reset usage statistics
if (isset($_POST['wvp_ai_reset_stats'])) {
    check_admin_referer('wvp_ai_reset_stats');

    // Reset usage counters
    update_option('wvp_ai_daily_usage', 0);
    update_option('wvp_ai_monthly_usage', 0);
    update_option('wvp_ai_total_cost', 0);
    update_option('wvp_ai_last_reset', current_time('mysql'));

    echo '<div class="updated"><p>Statistike potrošnje AI su resetovane.</p></div>';
}

// Get current AI settings
$openai_api_key = get_option('wvp_health_quiz_openai_api_key', '');
$openai_enabled = intval(get_option('wvp_health_quiz_openai_enabled', 0));
$package_logic = get_option('wvp_health_quiz_package_logic', 'most_symptoms');
$selected_packages = get_option('wvp_health_quiz_allowed_packages', array());

// Get AI usage limits and monitoring settings
$daily_limit = intval(get_option('wvp_ai_daily_limit', 100));
$monthly_limit = intval(get_option('wvp_ai_monthly_limit', 1000));
$cost_per_request = floatval(get_option('wvp_ai_cost_per_request', 0.02));
$budget_limit = floatval(get_option('wvp_ai_budget_limit', 50.00));
$enable_notifications = intval(get_option('wvp_ai_enable_notifications', 1));
$notification_email = get_option('wvp_ai_notification_email', get_option('admin_email'));
$auto_disable_threshold = intval(get_option('wvp_ai_auto_disable_threshold', 90));

// Get current usage statistics
$daily_usage = intval(get_option('wvp_ai_daily_usage', 0));
$monthly_usage = intval(get_option('wvp_ai_monthly_usage', 0));
$total_cost = floatval(get_option('wvp_ai_total_cost', 0));
$last_reset = get_option('wvp_ai_last_reset', current_time('mysql'));
?>

<div class="wrap">
    <h1><?php _e('🤖 AI Integracija', 'woocommerce-vip-paketi'); ?></h1>

    <div class="notice notice-info">
        <p><strong><?php _e('Napredna AI analiza zdravstvenih anketa', 'woocommerce-vip-paketi'); ?></strong></p>
        <p><?php _e('Konfigurišite OpenAI integraciju za pametan sistem preporuka na osnovu odgovora korisnika u zdravstvenoj anketi.', 'woocommerce-vip-paketi'); ?></p>
        <p><em><?php _e('Potreban je valid OpenAI API ključ sa pristupom GPT-4 modelu za optimalne rezultate.', 'woocommerce-vip-paketi'); ?></em></p>
    </div>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=wvp-ai-integration&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            ⚙️ <?php _e('Osnovna podešavanja', 'woocommerce-vip-paketi'); ?>
        </a>
        <a href="?page=wvp-ai-integration&tab=monitoring" class="nav-tab <?php echo $active_tab == 'monitoring' ? 'nav-tab-active' : ''; ?>">
            📊 <?php _e('Praćenje potrošnje', 'woocommerce-vip-paketi'); ?>
        </a>
        <a href="?page=wvp-ai-integration&tab=products" class="nav-tab <?php echo $active_tab == 'products' ? 'nav-tab-active' : ''; ?>">
            🛍️ <?php _e('Proizvodi', 'woocommerce-vip-paketi'); ?>
        </a>
        <a href="?page=wvp-ai-integration&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
            🔧 <?php _e('Napredne opcije', 'woocommerce-vip-paketi'); ?>
        </a>
    </nav>

    <?php if ($active_tab == 'general'): ?>
    <div class="wvp-tab-content">
        <form method="post">
            <?php wp_nonce_field('wvp_ai_general_save'); ?>
            <h2><?php _e('⚙️ Osnovna podešavanja AI integracije', 'woocommerce-vip-paketi'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><?php _e('OpenAI API Key', 'woocommerce-vip-paketi'); ?></th>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text" placeholder="sk-..." />
                            <button type="button" id="test-api-key" class="button button-secondary">🔍 Testiraj API</button>
                        </div>
                        <div id="api-test-result" style="margin-top: 10px;"></div>
                        <p class="description"><?php _e('Unesite vaš OpenAI API ključ za AI analizu odgovora', 'woocommerce-vip-paketi'); ?></p>
                        <p class="description"><strong>Napomena:</strong> Potreban je OpenAI API ključ sa pristupom GPT-4 modelu.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Status AI integracije', 'woocommerce-vip-paketi'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="openai_enabled" value="1" <?php checked($openai_enabled); ?> />
                            <?php _e('Omogući OpenAI analizu odgovora', 'woocommerce-vip-paketi'); ?>
                        </label>
                        <p class="description"><?php _e('Kada je omogućeno, AI će analizirati odgovore korisnika i davati personalizovane preporuke.', 'woocommerce-vip-paketi'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Logika za preporuke paketa', 'woocommerce-vip-paketi'); ?></th>
                    <td>
                        <select name="package_logic" class="regular-text">
                            <option value="most_symptoms" <?php selected($package_logic, 'most_symptoms'); ?>><?php _e('Najviše simptoma', 'woocommerce-vip-paketi'); ?></option>
                            <option value="severity_based" <?php selected($package_logic, 'severity_based'); ?>><?php _e('Na osnovu težine', 'woocommerce-vip-paketi'); ?></option>
                            <option value="balanced" <?php selected($package_logic, 'balanced'); ?>><?php _e('Balansiran pristup', 'woocommerce-vip-paketi'); ?></option>
                        </select>
                        <p class="description"><?php _e('Odaberite logiku koju AI koristi za preporučivanje paketa:', 'woocommerce-vip-paketi'); ?></p>
                        <ul class="description" style="margin-top: 5px;">
                            <li><strong>Najviše simptoma:</strong> Preporučuje pakete na osnovu najvećeg broja potvrđenih problema</li>
                            <li><strong>Na osnovu težine:</strong> Prioritizuje ozbiljnost i intenzitet simptoma</li>
                            <li><strong>Balansiran pristup:</strong> Kombinuje broj i težinu simptoma za optimalne preporuke</li>
                        </ul>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php submit_button(__('Sačuvaj osnovna podešavanja', 'woocommerce-vip-paketi'), 'primary', 'wvp_ai_general_save'); ?>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($active_tab == 'monitoring'): ?>
    <div class="wvp-tab-content">
        <form method="post">
            <?php wp_nonce_field('wvp_ai_monitoring_save'); ?>
            <h2><?php _e('📊 Praćenje potrošnje AI servisa', 'woocommerce-vip-paketi'); ?></h2>

            <!-- AI Usage Limits -->
            <div class="wvp-ai-limits">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Dnevni limit zahteva', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <input type="number" name="ai_daily_limit" value="<?php echo esc_attr($daily_limit); ?>" min="1" max="10000" class="small-text" />
                                <p class="description"><?php _e('Maksimalan broj AI zahteva po danu.', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Mesečni limit zahteva', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <input type="number" name="ai_monthly_limit" value="<?php echo esc_attr($monthly_limit); ?>" min="1" max="100000" class="small-text" />
                                <p class="description"><?php _e('Maksimalan broj AI zahteva po mesecu.', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Budžet limit ($)', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <input type="number" name="ai_budget_limit" value="<?php echo esc_attr($budget_limit); ?>" min="0.01" max="10000" step="0.01" class="small-text" />
                                <p class="description"><?php _e('Maksimalna mesečna potrošnja za AI servise u dolarima.', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Cena po zahtevu ($)', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <input type="number" name="ai_cost_per_request" value="<?php echo esc_attr($cost_per_request); ?>" min="0.001" max="1" step="0.001" class="small-text" />
                                <p class="description"><?php _e('Procenjena cena po AI zahtevu (za kalkulaciju budžeta).', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Email notifikacije', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ai_enable_notifications" value="1" <?php checked($enable_notifications); ?> />
                                    <?php _e('Pošalji email kada se dostigne 90% limita', 'woocommerce-vip-paketi'); ?>
                                </label><br><br>
                                <input type="email" name="ai_notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text" placeholder="admin@example.com" />
                                <p class="description"><?php _e('Email adresa za notifikacije o dosezanju limita.', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php _e('Automatsko isključivanje (%)', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <input type="number" name="ai_auto_disable_threshold" value="<?php echo esc_attr($auto_disable_threshold); ?>" min="50" max="100" class="small-text" />
                                <p class="description"><?php _e('Automatski isključi AI kada se dostigne određeni procenat limita.', 'woocommerce-vip-paketi'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Current Usage Statistics -->
                <h3><?php _e('📈 Trenutna statistika korišćenja', 'woocommerce-vip-paketi'); ?></h3>

                <div class="wvp-consumption-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                    <!-- Daily Usage -->
                    <div class="wvp-usage-card">
                        <h4><?php _e('📅 Dnevno korišćenje', 'woocommerce-vip-paketi'); ?></h4>
                        <div class="wvp-usage-stats">
                            <span class="wvp-daily-used"><?php echo $daily_usage; ?></span> / <?php echo $daily_limit; ?> zahteva
                        </div>
                        <div class="usage-bar">
                            <div class="wvp-daily-progress wvp-progress-fill" style="width: <?php echo min(($daily_usage / $daily_limit) * 100, 100); ?>%;"></div>
                        </div>
                    </div>

                    <!-- Monthly Usage -->
                    <div class="wvp-usage-card">
                        <h4><?php _e('📊 Mesečno korišćenje', 'woocommerce-vip-paketi'); ?></h4>
                        <div class="wvp-usage-stats">
                            <span class="wvp-monthly-used"><?php echo $monthly_usage; ?></span> / <?php echo $monthly_limit; ?> zahteva
                        </div>
                        <div class="usage-bar">
                            <div class="wvp-monthly-progress wvp-progress-fill" style="width: <?php echo min(($monthly_usage / $monthly_limit) * 100, 100); ?>%;"></div>
                        </div>
                    </div>

                    <!-- Budget Usage -->
                    <div class="wvp-usage-card">
                        <h4><?php _e('💰 Budžet', 'woocommerce-vip-paketi'); ?></h4>
                        <div class="wvp-usage-stats">
                            $<span class="wvp-budget-used"><?php echo number_format($total_cost, 2); ?></span> / $<?php echo number_format($budget_limit, 2); ?>
                        </div>
                        <div class="usage-bar">
                            <div class="wvp-budget-progress wvp-progress-fill" style="width: <?php echo min(($total_cost / $budget_limit) * 100, 100); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="wvp-last-update-info">
                    <?php _e('Poslednje ažuriranje:', 'woocommerce-vip-paketi'); ?> <?php echo date('d.m.Y H:i:s'); ?>
                </div>
            </div>
            <?php submit_button(__('Sačuvaj monitoring podešavanja', 'woocommerce-vip-paketi'), 'primary', 'wvp_ai_monitoring_save'); ?>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($active_tab == 'products'): ?>
    <div class="wvp-tab-content">
        <form method="post">
            <?php wp_nonce_field('wvp_ai_products_save'); ?>
            <h2><?php _e('🛍️ Upravljanje proizvodima za AI preporuke', 'woocommerce-vip-paketi'); ?></h2>

            <!-- Package Discount Settings -->
            <div class="postbox" style="margin-bottom: 20px;">
                <div class="postbox-header">
                    <h3>💰 Podešavanja popusta za pakete</h3>
                </div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th scope="row"><?php _e('Popusti za broj proizvoda', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    <div class="discount-setting">
                                        <label><strong>2 proizvoda:</strong></label>
                                        <input type="number" name="discount_2_products" value="<?php echo esc_attr(get_option('wvp_ai_discount_2_products', '10')); ?>" min="0" max="50" step="1" /> %
                                    </div>
                                    <div class="discount-setting">
                                        <label><strong>3 proizvoda:</strong></label>
                                        <input type="number" name="discount_3_products" value="<?php echo esc_attr(get_option('wvp_ai_discount_3_products', '12')); ?>" min="0" max="50" step="1" /> %
                                    </div>
                                    <div class="discount-setting">
                                        <label><strong>4 proizvoda:</strong></label>
                                        <input type="number" name="discount_4_products" value="<?php echo esc_attr(get_option('wvp_ai_discount_4_products', '16')); ?>" min="0" max="50" step="1" /> %
                                    </div>
                                    <div class="discount-setting">
                                        <label><strong>6 proizvoda:</strong></label>
                                        <input type="number" name="discount_6_products" value="<?php echo esc_attr(get_option('wvp_ai_discount_6_products', '20')); ?>" min="0" max="50" step="1" /> %
                                    </div>
                                    <div class="discount-setting">
                                        <label><strong>VIP dodatni popust:</strong></label>
                                        <input type="number" name="vip_additional_discount" value="<?php echo esc_attr(get_option('wvp_ai_vip_additional_discount', '10')); ?>" min="0" max="20" step="1" /> %
                                        <p class="description" style="margin-top: 5px;">Dodatni popust za VIP korisnike</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Products Selection -->
            <div class="postbox">
                <div class="postbox-header">
                    <h3>🛍️ Odabir proizvoda za AI preporuke</h3>
                </div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th scope="row"><?php _e('Dozvoljeni proizvodi za AI preporuke', 'woocommerce-vip-paketi'); ?></th>
                            <td>
                                <?php
                                // Get selected products from options
                                $selected_products = get_option('wvp_ai_allowed_products', array());

                                // Get all WooCommerce products
                                $products = wc_get_products(array(
                                    'limit' => -1,
                                    'status' => 'publish',
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));

                                if (!empty($products)): ?>
                                    <div class="wvp-products-selection">
                                        <p class="description"><?php _e('Odaberite koje proizvode AI može da preporučuje korisnicima. Za svaki proizvod možete dodati karakteristike koje će AI koristiti za donošenje odluka:', 'woocommerce-vip-paketi'); ?></p>

                                        <div class="wvp-products-grid" style="margin: 15px 0;">
                                            <?php foreach ($products as $product):
                                                $product_id = $product->get_id();
                                                $is_selected = in_array($product_id, $selected_products);
                                                $characteristics = get_option('wvp_ai_product_characteristics_' . $product_id, '');
                                            ?>
                                            <div class="wvp-product-card" style="border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9; margin-bottom: 15px; padding: 15px;">
                                                <div style="display: flex; align-items: flex-start; gap: 15px;">
                                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                        <input type="checkbox" name="allowed_products[]" value="<?php echo esc_attr($product_id); ?>" <?php checked($is_selected); ?> class="product-checkbox" data-product-id="<?php echo esc_attr($product_id); ?>" />
                                                        <strong style="color: #1d2327; font-size: 16px;"><?php echo esc_html($product->get_name()); ?></strong>
                                                    </label>
                                                    <div style="flex: 1;">
                                                        <p style="margin: 0 0 10px 0; color: #666; line-height: 1.4;">
                                                            <?php echo esc_html(wp_trim_words($product->get_short_description() ?: $product->get_description(), 20)); ?>
                                                        </p>
                                                        <small style="color: #999;">
                                                            <strong>Cena:</strong> <?php echo $product->get_price_html(); ?> |
                                                            <strong>SKU:</strong> <?php echo $product->get_sku() ?: 'N/A'; ?>
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="product-characteristics" style="margin-top: 15px; <?php echo $is_selected ? '' : 'display: none;'; ?>">
                                                    <label for="characteristics_<?php echo $product_id; ?>" style="font-weight: 600; color: #1d2327;">
                                                        📝 Karakteristike za AI (detaljno opišite proizvod, namenu, koristi):
                                                    </label>
                                                    <textarea
                                                        id="characteristics_<?php echo $product_id; ?>"
                                                        name="product_characteristics[<?php echo $product_id; ?>]"
                                                        rows="4"
                                                        class="large-text"
                                                        placeholder="Detaljno opišite proizvod, njegove koristi, za koga je namenjen, kada se preporučuje..."
                                                        style="width: 100%; margin-top: 8px;"
                                                    ><?php echo esc_textarea($characteristics); ?></textarea>
                                                    <p class="description" style="margin-top: 5px;">
                                                        AI će koristiti ove informacije da odluči kada da preporuči ovaj proizvod. Budite precizni i detaljni.
                                                    </p>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="notice notice-warning inline">
                                        <p><?php _e('Trenutno nema dostupnih WooCommerce proizvoda.', 'woocommerce-vip-paketi'); ?>
                                           <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-secondary" style="margin-left: 10px;"><?php _e('Dodaj proizvode', 'woocommerce-vip-paketi'); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php submit_button(__('Sačuvaj proizvode i podešavanja', 'woocommerce-vip-paketi'), 'primary', 'wvp_ai_products_save'); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Show/hide characteristics section based on checkbox
        $('.product-checkbox').on('change', function() {
            const productId = $(this).data('product-id');
            const characteristicsDiv = $(this).closest('.wvp-product-card').find('.product-characteristics');

            if ($(this).is(':checked')) {
                characteristicsDiv.slideDown();
            } else {
                characteristicsDiv.slideUp();
            }
        });
    });
    </script>
    <?php endif; ?>

    <?php if ($active_tab == 'advanced'): ?>
    <div class="wvp-tab-content">
        <form method="post">
            <?php wp_nonce_field('wvp_ai_advanced_save'); ?>
            <h2><?php _e('🔧 Napredne opcije i AI kontrole', 'woocommerce-vip-paketi'); ?></h2>

            <div class="notice notice-warning">
                <p><strong><?php _e('Upozorenje:', 'woocommerce-vip-paketi'); ?></strong> <?php _e('Napredne opcije su namenjene za iskusne korisnike. Menjanje ovih podešavanja može uticati na performanse AI sistema.', 'woocommerce-vip-paketi'); ?></p>
            </div>

            <!-- Documentation Section -->
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #2c3e50;">📋 Sistem dokumentacija - Poslednje promene</h3>

                <div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 8px; padding: 20px; margin: 15px 0;">
                    <h4 style="color: #2c5530; margin-top: 0;">✅ Kompletno redesajnirana logika za /analiza-zdravstvenog-stanja/izvestaj/</h4>
                    <p><strong>Datum:</strong> <?php echo date('d.m.Y H:i'); ?></p>

                    <h5 style="color: #2c5530;">🔧 Šta je promenjeno:</h5>
                    <ul style="color: #2c5530;">
                        <li><strong>Nova AI logika:</strong> Zamenjena kompleksna OpenAI integracija sa lokalnom PHP logikom za brže rezultate</li>
                        <li><strong>Progress linija:</strong> Vizuelni indikator zdravstvenog stanja sa bojama (zelena=dobro, žuta=umereno, crvena=loše)</li>
                        <li><strong>AI Health Score:</strong> Kalkulacija skora na osnovu broja "DA" odgovora i intenziteta problema</li>
                        <li><strong>Personalizovani sadržaj:</strong> "Poštovana [Ime]" + lista specifičnih zdravstvenih problema</li>
                        <li><strong>Prirodni saveti:</strong> Automatski generisani saveti za navike i način života na osnovu godina i problema</li>
                        <li><strong>Uklonjena duplikacija:</strong> Stara wvp_save_quiz funkcija obrisana - sada samo wvp_save_answers sprečava duplikovanje rezultata</li>
                    </ul>

                    <h5 style="color: #2c5530;">📊 Nova formula za Health Score:</h5>
                    <code style="background: #f8f9fa; padding: 8px; border-radius: 4px; display: block; margin: 10px 0;">
                        Score = 100 - (problem_ratio * 50) - (intensity_ratio * 30)<br>
                        • problem_ratio = (broj "DA" odgovora) / (ukupan broj pitanja)<br>
                        • intensity_ratio = (ukupan intenzitet) / (maksimalan mogućan intenzitet)
                    </code>

                    <h5 style="color: #2c5530;">🌿 Logika prirodnih saveta:</h5>
                    <ul style="color: #2c5530;">
                        <li><strong>Po godinama:</strong> &lt;30 (preventiva), 30-50 (balans), &gt;50 (održavanje vitalnosti)</li>
                        <li><strong>Po problemima:</strong> 5+ problema (opšti saveti), 2-4 (fokusirani), &lt;2 (održavanje)</li>
                        <li><strong>Uvek uključeni:</strong> Voda, svež vazduh, pozitivno razmišljanje, ograničavanje ekrana</li>
                    </ul>
                </div>

                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin: 15px 0;">
                    <h5 style="color: #856404; margin-top: 0;">🔍 Tehničke promene u kodu:</h5>
                    <ul style="color: #856404;">
                        <li><strong>wvp_generate_step_3():</strong> Kompletno prepisana funkcija (shortcodes.php:7372-7461)</li>
                        <li><strong>wvp_calculate_health_analysis():</strong> Nova funkcija za analizu podataka</li>
                        <li><strong>wvp_generate_natural_advice():</strong> Nova funkcija za generisanje saveta</li>
                        <li><strong>data-handler.php:</strong> Uklonjena wvp_save_quiz funkcija (linija 12-66)</li>
                        <li><strong>URL parametar:</strong> ?id=[public_analysis_id] za pristup rezultatima</li>
                    </ul>
                </div>
            </div>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Sistemski prompt za AI', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <textarea name="ai_system_prompt" rows="4" class="large-text code"><?php echo esc_textarea(get_option('wvp_ai_system_prompt', 'Ti si stručnjak za prirodno zdravlje i wellness koji se specijalizuje za zelene sokove, detoksikaciju, probiotike i holistički pristup zdravlju. Analiziraš zdravstvene upitnike i preporučuješ prirodne proizvode i pakete zasnovane na biljnim sastojcima, vitaminima, mineralima i suplementima za poboljšanje vitalnosti.')); ?></textarea>
                            <p class="description"><?php _e('Osnovni prompt koji definiše ulogu AI-a. Određuje kako AI interpretira svoju ulogu i pristup analizi.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Zabrane i ograničenja', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <textarea name="ai_restrictions" rows="6" class="large-text code"><?php echo esc_textarea(get_option('wvp_ai_restrictions', "STRIKTNE ZABRANE:\n1. NIKAD ne davaš medicinske dijagnoze\n2. NIKAD ne preporučuješ lekove ili farmaceutske proizvode\n3. NIKAD ne zamenjuješ lekarski pregled\n4. UVEK naglašavaš da su tvoje preporuke za prirodno zdravlje i wellness\n5. NIKAD ne garantuješ lečenje bolesti\n6. FOKUSIRAŠ se isključivo na prirodne proizvode i suplemente\n7. UVEK preporučuješ konsultaciju sa lekarom za ozbiljne zdravstvene probleme")); ?></textarea>
                            <p class="description"><?php _e('Ključne zabrane koje AI mora da poštuje. Ove instrukcije osiguravaju da AI ne daje medicinske savete.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Napredni prompt za preporuke', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <textarea name="ai_recommendation_prompt" rows="8" class="large-text code"><?php echo esc_textarea(get_option('wvp_ai_recommendation_prompt', "PRIORITETI U PREPORUKAMA:\n- Za digestivne probleme: zeleni sokovi + probiotici\n- Za nizak imunitet: vitamin C + adaptogeni + probiotici\n- Za umor/nizak energiju: zeleni sokovi + B vitamini + adaptogeni\n- Za stres: adaptogeni + magnezijum + omega masne kiseline\n- Za anti-aging: antioksidanti + kolagen + omega masne kiseline\n\nKORISTI PRIRODAN PRISTUP:\n1. Fokus na detoksikaciju i alkalizaciju\n2. Jačanje prirodnog imuniteta\n3. Poboljšanje energije prirodnim putem\n4. Podrška digestivnom sistemu\n5. Balansiranje hormona prirodno")); ?></textarea>
                            <p class="description"><?php _e('Specifične instrukcije za AI kako da formira preporuke na osnovu različitih zdravstvenih problema.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Temperatura AI modela', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="number" name="ai_temperature" value="<?php echo esc_attr(get_option('wvp_ai_temperature', '0.7')); ?>"
                                   min="0" max="1" step="0.1" class="small-text" />
                            <p class="description"><?php _e('Kontroliše kreativnost AI-a (0.0 = konzistentno, 1.0 = kreativno). Preporučeno: 0.7', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Maksimalni broj tokena', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="number" name="ai_max_tokens" value="<?php echo esc_attr(get_option('wvp_ai_max_tokens', '1000')); ?>"
                                   min="100" max="4000" class="small-text" />
                            <p class="description"><?php _e('Maksimalna dužina AI odgovora. Veći broj = detaljniji odgovori, ali veći troškovi.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('AI model', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <select name="ai_model" class="regular-text">
                                <option value="gpt-4" <?php selected(get_option('wvp_ai_model', 'gpt-4'), 'gpt-4'); ?>>GPT-4 (Preporučeno)</option>
                                <option value="gpt-4-turbo" <?php selected(get_option('wvp_ai_model', 'gpt-4'), 'gpt-4-turbo'); ?>>GPT-4 Turbo (Brži)</option>
                                <option value="gpt-3.5-turbo" <?php selected(get_option('wvp_ai_model', 'gpt-4'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (Jeftiniji)</option>
                            </select>
                            <p class="description"><?php _e('OpenAI model koji se koristi za analizu. GPT-4 daje najbolje rezultate.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Timeout za AI zahteve (sekunde)', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <input type="number" name="ai_timeout" value="<?php echo esc_attr(get_option('wvp_ai_timeout', '30')); ?>"
                                   min="10" max="120" class="small-text" />
                            <p class="description"><?php _e('Maksimalno vreme čekanja na AI odgovor pre prekida zahteva.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Prioriteti doziranja', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <textarea name="ai_dosage_priorities" rows="5" class="large-text code"><?php echo esc_textarea(get_option('wvp_ai_dosage_priorities', "DOZNI PRIORITETI:\n- Visok intenzitet simptoma: x1.5 preporučena doza\n- Godine 50+: +0.5 kutija mesečno\n- Kombinacija problema: saberi sve doze\n- Maksimum: 8 kutija po paketu\n- Minimum: 1 kutija po proizvodu")); ?></textarea>
                            <p class="description"><?php _e('Specifične instrukcije za AI kako da računa doziranje na osnovu intenziteta simptoma i godina korisnika.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Debug režim', 'woocommerce-vip-paketi'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_debug_mode" value="1" <?php checked(get_option('wvp_ai_debug_mode', 0)); ?> />
                                <?php _e('Omogući detaljno logovanje AI zahteva i odgovora', 'woocommerce-vip-paketi'); ?>
                            </label>
                            <p class="description"><?php _e('Korisno za testiranje i rešavanje problema. Može povećati veličinu log fajlova.', 'woocommerce-vip-paketi'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button(__('Sačuvaj napredne opcije', 'woocommerce-vip-paketi'), 'primary', 'wvp_ai_advanced_save'); ?>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
/* Tab Content */
.wvp-tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 0 0 3px 3px;
}

.wvp-tab-content h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1d2327;
    font-size: 1.3em;
    border-bottom: 2px solid #f0f0f1;
    padding-bottom: 10px;
}

/* Usage Cards Enhancement for Tabs */
.wvp-usage-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 16px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}

.wvp-usage-card:hover {
    border-color: #007cba;
    box-shadow: 0 2px 5px rgba(0,0,0,0.07);
}

.wvp-usage-card h4 {
    margin: 0 0 12px 0;
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
}

.wvp-usage-stats {
    font-size: 16px;
    color: #1d2327;
    margin-bottom: 8px;
    font-weight: 500;
}

/* Progress Bar in Tab Context */
.usage-bar {
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.usage-bar .wvp-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 4px;
}

.usage-bar .wvp-progress-fill.success {
    background: linear-gradient(90deg, #00a32a, #00ba37);
}

.usage-bar .wvp-progress-fill.warning {
    background: linear-gradient(90deg, #dba617, #ffb900);
}

.usage-bar .wvp-progress-fill.danger {
    background: linear-gradient(90deg, #dc3545, #c82333);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test OpenAI API Key
    $('#test-api-key').on('click', function() {
        var button = $(this);
        var apiKey = $('#openai_api_key').val();
        var resultDiv = $('#api-test-result');

        if (!apiKey) {
            resultDiv.html('<div class="notice notice-error inline"><p>Molimo unesite API ključ pre testiranja.</p></div>');
            return;
        }

        button.prop('disabled', true).text('🔄 Testiram...');
        resultDiv.html('<div class="notice notice-info inline"><p>Testiram konekciju sa OpenAI...</p></div>');

        $.post(ajaxurl, {
            action: 'wvp_test_openai_api',
            api_key: apiKey,
            nonce: '<?php echo wp_create_nonce('wvp_test_openai_api'); ?>'
        }, function(response) {
            if (response.success) {
                resultDiv.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            } else {
                resultDiv.html('<div class="notice notice-error inline"><p>❌ ' + response.data + '</p></div>');
            }
        }).fail(function() {
            resultDiv.html('<div class="notice notice-error inline"><p>❌ Greška pri komunikaciji sa serverom.</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('🔍 Testiraj API');
        });
    });
});
</script>

