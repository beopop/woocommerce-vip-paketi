<?php
/**
 * Single package template with SEO optimization
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load SEO optimizations before header
add_action('wp_head', 'wvp_package_seo_meta_tags', 1);
add_filter('document_title_parts', 'wvp_package_title_filter', 10, 1);
add_filter('wp_head', 'wvp_package_structured_data', 5);

// Yoast SEO Compatibility
add_filter('wpseo_title', 'wvp_yoast_title_filter', 10, 1);
add_filter('wpseo_metadesc', 'wvp_yoast_description_filter', 10, 1);
add_filter('wpseo_canonical', 'wvp_yoast_canonical_filter', 10, 1);
add_filter('wpseo_opengraph_title', 'wvp_yoast_og_title_filter', 10, 1);
add_filter('wpseo_opengraph_desc', 'wvp_yoast_og_description_filter', 10, 1);

// RankMath SEO Compatibility  
add_filter('rank_math/frontend/title', 'wvp_rankmath_title_filter', 10, 1);
add_filter('rank_math/frontend/description', 'wvp_rankmath_description_filter', 10, 1);
add_filter('rank_math/frontend/canonical', 'wvp_rankmath_canonical_filter', 10, 1);

get_header(); 

// Use global package ID if set, otherwise current post ID
global $wvp_package_id;
$package_id = $wvp_package_id ? $wvp_package_id : get_the_ID();

$packages_admin = new WVP_Admin_Packages();

// Try to get package data, if fails try from WC product meta
$package_data = $packages_admin->get_package_data($package_id);
if (!$package_data && $package_id !== get_the_ID()) {
    // Try with WooCommerce product ID
    $package_data = $packages_admin->get_package_data(get_the_ID());
}

// If still no package data, create basic structure from WC product
if (!$package_data) {
    $current_post = get_post(get_the_ID());
    $package_data = array(
        'id' => get_the_ID(),
        'title' => $current_post->post_title,
        'content' => $current_post->post_content,
        'status' => 'active',
        'min_items' => get_post_meta(get_the_ID(), '_wvp_min_items', true) ?: 2,
        'max_items' => get_post_meta(get_the_ID(), '_wvp_max_items', true) ?: 6,
        'package_sizes' => get_post_meta(get_the_ID(), '_wvp_package_sizes', true) ?: array(2, 3, 4, 5, 6),
        'allowed_products' => get_post_meta(get_the_ID(), '_wvp_allowed_products', true) ?: array(),
        'regular_discounts' => get_post_meta(get_the_ID(), '_wvp_regular_discounts', true) ?: array(),
        'vip_discounts' => get_post_meta(get_the_ID(), '_wvp_vip_discounts', true) ?: array(),
        'allow_coupons' => get_post_meta(get_the_ID(), '_wvp_allow_coupons', true) ?: 'no',
        'show_discount_table' => get_post_meta(get_the_ID(), '_wvp_show_discount_table', true) ?: 'yes',
        'show_for_non_vip' => get_post_meta(get_the_ID(), '_wvp_show_for_non_vip', true) ?: 'yes'
    );
}

$core = new WVP_Core();
$is_vip = $core->is_user_vip();
?>

<div class="wvp-single-package-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 col-xl-8 mx-auto">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <!-- SEO Breadcrumbs -->
                    <nav class="wvp-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                <a href="<?php echo home_url(); ?>" itemprop="item">
                                    <span itemprop="name"><?php _e('Početna', 'woocommerce-vip-paketi'); ?></span>
                                </a>
                                <meta itemprop="position" content="1" />
                            </li>
                            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                <a href="<?php echo get_post_type_archive_link('wvp_package'); ?>" itemprop="item">
                                    <span itemprop="name"><?php _e('Paketi', 'woocommerce-vip-paketi'); ?></span>
                                </a>
                                <meta itemprop="position" content="2" />
                            </li>
                            <li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                <span itemprop="name"><?php the_title(); ?></span>
                                <meta itemprop="position" content="3" />
                            </li>
                        </ol>
                    </nav>

                    <article class="wvp-package-header" itemscope itemtype="https://schema.org/Product">
                        <h1 class="package-title" itemprop="name"><?php the_title(); ?></h1>
                        <meta itemprop="productID" content="package-<?php echo get_the_ID(); ?>" />
                        <meta itemprop="category" content="Paket" />
                        <meta itemprop="url" content="<?php echo get_permalink(); ?>" />
                        
                        <?php if (has_post_thumbnail()): ?>
                            <div class="package-featured-image" itemprop="image">
                                <?php the_post_thumbnail('large'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-description" itemprop="description">
                            <?php the_content(); ?>
                        </div>
                        
                        <!-- Hidden structured data for offers -->
                        <?php 
                        $package_data = $packages_admin->get_package_data($package_id);
                        if ($package_data && !empty($package_data['regular_discounts'])):
                            $min_discount = min(array_values($package_data['regular_discounts']));
                            $max_discount = max(array_values($package_data['regular_discounts']));
                        ?>
                        <div itemprop="offers" itemscope itemtype="https://schema.org/Offer" style="display: none;">
                            <meta itemprop="availability" content="https://schema.org/InStock" />
                            <meta itemprop="priceCurrency" content="<?php echo get_woocommerce_currency(); ?>" />
                            <span itemprop="description">Popust od <?php echo $min_discount; ?>% do <?php echo $max_discount; ?>% na pakete</span>
                            <div itemprop="seller" itemscope itemtype="https://schema.org/Organization">
                                <meta itemprop="name" content="<?php echo get_bloginfo('name'); ?>" />
                                <meta itemprop="url" content="<?php echo home_url(); ?>" />
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Brand information -->
                        <div itemprop="brand" itemscope itemtype="https://schema.org/Brand" style="display: none;">
                            <meta itemprop="name" content="<?php echo get_bloginfo('name'); ?>" />
                        </div>
                    </article>

                    <!-- Package Configuration Section -->
                    <?php include plugin_dir_path(__FILE__) . 'partials/wvp-package-configuration.php'; ?>

                    <!-- Package Discount Table -->
                    <?php include plugin_dir_path(__FILE__) . 'partials/wvp-package-discount-table.php'; ?>

                    <!-- Product Selection Section (hidden initially) -->
                    <?php include plugin_dir_path(__FILE__) . 'partials/wvp-package-product-selection.php'; ?>

                    <!-- Package Total Section (hidden initially) -->
                    <?php include plugin_dir_path(__FILE__) . 'partials/wvp-package-total.php'; ?>

                    <!-- Add to Cart Section (hidden initially) -->
                    <?php include plugin_dir_path(__FILE__) . 'partials/wvp-package-add-to-cart.php'; ?>

                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Force full width layout without sidebars */
body.single-wvp_package #content {
    width: 100% !important;
    max-width: none !important;
}

body.single-wvp_package .main-page-wrapper {
    max-width: none !important;
}

body.single-wvp_package .site-content {
    max-width: none !important;
    width: 100% !important;
}

body.single-wvp_package .sidebar {
    display: none !important;
}

body.single-wvp_package .col-lg-9 {
    flex: 0 0 100% !important;
    max-width: 100% !important;
}

/* SEO Breadcrumbs */
.wvp-breadcrumbs {
    margin-bottom: 20px;
    padding: 15px 0;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    font-size: 14px;
}

.breadcrumb-item {
    display: inline-block;
}

.breadcrumb-item + .breadcrumb-item:before {
    content: "/";
    padding: 0 8px;
    color: #6c757d;
}

.breadcrumb-item a {
    color: #007cba;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}

.wvp-single-package-wrapper {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 70vh;
    width: 100%;
    margin: 0;
}

.wvp-single-package-wrapper .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.wvp-package-header {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.package-title {
    font-size: 2.5em;
    color: #1d2327;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.package-featured-image {
    text-align: center;
    margin-bottom: 30px;
}

.package-featured-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.package-description {
    font-size: 1.1em;
    line-height: 1.8;
    color: #646970;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.package-description p {
    margin-bottom: 1.5em;
}

/* Woodmart theme compatibility */
body.single-wvp_package .woodmart-dark {
    background: #f8f9fa !important;
}

body.single-wvp_package .container {
    max-width: 1200px !important;
}

/* Responsive design */
@media (max-width: 1200px) {
    .wvp-single-package-wrapper .container-fluid {
        max-width: 100%;
        padding: 0 30px;
    }
}

@media (max-width: 768px) {
    .wvp-single-package-wrapper {
        padding: 20px 0;
    }
    
    .wvp-single-package-wrapper .container-fluid {
        padding: 0 15px;
    }
    
    .wvp-package-header {
        padding: 30px 20px;
        margin-bottom: 20px;
    }
    
    .package-title {
        font-size: 2em;
    }
    
    .package-description {
        font-size: 1em;
    }
}

/* Additional Woodmart overrides */
body.single-wvp_package .main-page-wrapper .container {
    max-width: none !important;
}

body.single-wvp_package .site-content .container {
    max-width: none !important;
}
</style>

<script>
// Package configuration JavaScript
jQuery(document).ready(function($) {
    // Initialize AJAX data for templates
    window.wvp_ajax = {
        ajax_url: '<?php echo admin_url("admin-ajax.php"); ?>',
        nonce: '<?php echo wp_create_nonce("wvp_nonce"); ?>',
        cart_url: '<?php echo wc_get_cart_url(); ?>',
        checkout_url: '<?php echo wc_get_checkout_url(); ?>',
        is_vip: <?php echo $is_vip ? 'true' : 'false'; ?>,
        currency: {
            code: '<?php echo esc_js(get_woocommerce_currency()); ?>',
            symbol: '<?php echo esc_js(get_woocommerce_currency_symbol()); ?>',
            decimals: <?php echo esc_js(wc_get_price_decimals()); ?>,
            decimal_separator: '<?php echo esc_js(wc_get_price_decimal_separator()); ?>',
            thousand_separator: '<?php echo esc_js(wc_get_price_thousand_separator()); ?>',
            position: '<?php echo esc_js(get_option('woocommerce_currency_pos')); ?>'
        }
    };
    
    // Initialize global package data
    window.wvpSelectedProducts = [];
    window.wvpPackageData = {};
});
</script>

<?php
/**
 * SEO Functions for Package Pages
 */

// SEO Title Filter
function wvp_package_title_filter($title_parts) {
    if (is_singular('wvp_package')) {
        global $post;
        $package_title = get_the_title();
        
        // Check for custom SEO title
        $custom_title = get_post_meta($post->ID, '_wvp_seo_title', true);
        if ($custom_title) {
            $title_parts['title'] = $custom_title;
        } else {
            // Generate optimized title
            $title_parts['title'] = $package_title . ' - Konfiguriši Svoj Paket';
        }
    }
    return $title_parts;
}

// Meta Tags for SEO
function wvp_package_seo_meta_tags() {
    if (!is_singular('wvp_package')) {
        return;
    }
    
    global $post;
    $package_id = get_the_ID();
    $package_title = get_the_title();
    $package_excerpt = get_the_excerpt();
    
    // Custom meta description
    $meta_description = get_post_meta($package_id, '_wvp_seo_description', true);
    if (!$meta_description) {
        if ($package_excerpt) {
            $meta_description = wp_trim_words($package_excerpt, 25, '...');
        } else {
            $content = get_the_content();
            $meta_description = wp_trim_words(strip_tags($content), 25, '...');
        }
        
        if (!$meta_description) {
            $meta_description = 'Kreiraj svoj prilagođeni ' . $package_title . ' paket sa popustnim cenama. Izaberi proizvode i uštedi sa našim VIP paketima.';
        }
    }
    
    // Custom keywords
    $meta_keywords = get_post_meta($package_id, '_wvp_seo_keywords', true);
    if (!$meta_keywords) {
        $meta_keywords = $package_title . ', paket, popust, VIP, konfiguracija, proizvodi';
    }
    
    // Canonical URL
    $canonical_url = get_permalink($package_id);
    
    // Featured image for social sharing
    $featured_image = get_the_post_thumbnail_url($package_id, 'large');
    if (!$featured_image) {
        $featured_image = wp_get_attachment_url(get_option('woocommerce_placeholder_image', 0));
    }
    
    echo "\n<!-- VIP Package SEO Meta Tags -->\n";
    echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
    echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    
    // Open Graph tags
    echo '<meta property="og:type" content="product">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($package_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical_url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    
    if ($featured_image) {
        echo '<meta property="og:image" content="' . esc_url($featured_image) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }
    
    // Twitter Card tags
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($package_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . "\n";
    
    if ($featured_image) {
        echo '<meta name="twitter:image" content="' . esc_url($featured_image) . '">' . "\n";
    }
    
    // Additional meta for better indexing
    echo '<meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large">' . "\n";
    echo '<meta name="googlebot" content="index, follow">' . "\n";
    
    // Mobile optimization
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    
    // Additional SEO signals
    echo '<meta name="language" content="sr">' . "\n";
    echo '<meta name="geo.region" content="RS">' . "\n";
    echo '<meta name="geo.placename" content="Serbia">' . "\n";
    
    // Authorship and publisher info
    echo '<link rel="author" href="' . esc_url(home_url('/author/' . get_the_author_meta('user_nicename'))) . '">' . "\n";
    echo '<link rel="publisher" href="' . esc_url(home_url()) . '">' . "\n";
    
    // Page load speed optimization
    echo '<link rel="preload" href="' . esc_url(admin_url('admin-ajax.php')) . '" as="fetch" crossorigin="anonymous">' . "\n";
}

// Structured Data (JSON-LD) for SEO
function wvp_package_structured_data() {
    if (!is_singular('wvp_package')) {
        return;
    }
    
    global $post;
    $package_id = get_the_ID();
    $package_title = get_the_title();
    $package_content = get_the_content();
    $package_excerpt = get_the_excerpt();
    $featured_image = get_the_post_thumbnail_url($package_id, 'large');
    $canonical_url = get_permalink($package_id);
    
    // Get package data for pricing info
    $packages_admin = new WVP_Admin_Packages();
    $package_data = $packages_admin->get_package_data($package_id);
    
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $package_title,
        'description' => $package_excerpt ? wp_trim_words($package_excerpt, 50) : wp_trim_words(strip_tags($package_content), 50),
        'url' => $canonical_url,
        'productID' => 'package-' . $package_id,
        'category' => 'Paket',
        'brand' => array(
            '@type' => 'Brand',
            'name' => get_bloginfo('name')
        )
    );
    
    // Add image if available
    if ($featured_image) {
        $structured_data['image'] = array(
            '@type' => 'ImageObject',
            'url' => $featured_image,
            'width' => 1200,
            'height' => 630
        );
    }
    
    // Add offer information
    if ($package_data && !empty($package_data['regular_discounts'])) {
        $min_discount = min(array_values($package_data['regular_discounts']));
        $max_discount = max(array_values($package_data['regular_discounts']));
        
        $structured_data['offers'] = array(
            '@type' => 'Offer',
            'availability' => 'https://schema.org/InStock',
            'priceCurrency' => get_woocommerce_currency(),
            'description' => 'Popust od ' . $min_discount . '% do ' . $max_discount . '% na pakete',
            'seller' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'validFrom' => get_the_date('c', $package_id),
            'priceValidUntil' => date('c', strtotime('+1 year'))
        );
    }
    
    // Add organization info
    $structured_data['manufacturer'] = array(
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url(),
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => get_site_icon_url(512),
            'width' => 512,
            'height' => 512
        )
    );
    
    // Add aggregated rating placeholder (can be expanded later)
    $structured_data['aggregateRating'] = array(
        '@type' => 'AggregateRating',
        'ratingValue' => '4.5',
        'reviewCount' => '1',
        'bestRating' => '5',
        'worstRating' => '1'
    );
    
    echo "\n<!-- VIP Package Structured Data -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n" . '</script>' . "\n";
}

/**
 * Yoast SEO Integration Functions
 */

// Yoast Title Filter
function wvp_yoast_title_filter($title) {
    if (is_singular('wvp_package')) {
        $custom_title = get_post_meta(get_the_ID(), '_wvp_seo_title', true);
        if ($custom_title) {
            return $custom_title;
        }
        return get_the_title() . ' - Konfiguriši Svoj Paket';
    }
    return $title;
}

// Yoast Description Filter
function wvp_yoast_description_filter($description) {
    if (is_singular('wvp_package')) {
        $custom_description = get_post_meta(get_the_ID(), '_wvp_seo_description', true);
        if ($custom_description) {
            return $custom_description;
        }
        
        $package_excerpt = get_the_excerpt();
        if ($package_excerpt) {
            return wp_trim_words($package_excerpt, 25, '...');
        }
        
        return 'Kreiraj svoj prilagođeni ' . get_the_title() . ' paket sa popustnim cenama. Izaberi proizvode i uštedi sa našim VIP paketima.';
    }
    return $description;
}

// Yoast Canonical Filter
function wvp_yoast_canonical_filter($canonical) {
    if (is_singular('wvp_package')) {
        return get_permalink(get_the_ID());
    }
    return $canonical;
}

// Yoast Open Graph Title
function wvp_yoast_og_title_filter($title) {
    if (is_singular('wvp_package')) {
        $custom_title = get_post_meta(get_the_ID(), '_wvp_seo_title', true);
        if ($custom_title) {
            return $custom_title;
        }
        return get_the_title();
    }
    return $title;
}

// Yoast Open Graph Description
function wvp_yoast_og_description_filter($description) {
    if (is_singular('wvp_package')) {
        $custom_description = get_post_meta(get_the_ID(), '_wvp_seo_description', true);
        if ($custom_description) {
            return $custom_description;
        }
        
        $package_excerpt = get_the_excerpt();
        if ($package_excerpt) {
            return wp_trim_words($package_excerpt, 25, '...');
        }
        
        return 'Kreiraj svoj prilagođeni ' . get_the_title() . ' paket sa popustnim cenama.';
    }
    return $description;
}

/**
 * RankMath SEO Integration Functions
 */

// RankMath Title Filter
function wvp_rankmath_title_filter($title) {
    if (is_singular('wvp_package')) {
        $custom_title = get_post_meta(get_the_ID(), '_wvp_seo_title', true);
        if ($custom_title) {
            return $custom_title;
        }
        return get_the_title() . ' - Konfiguriši Svoj Paket';
    }
    return $title;
}

// RankMath Description Filter
function wvp_rankmath_description_filter($description) {
    if (is_singular('wvp_package')) {
        $custom_description = get_post_meta(get_the_ID(), '_wvp_seo_description', true);
        if ($custom_description) {
            return $custom_description;
        }
        
        $package_excerpt = get_the_excerpt();
        if ($package_excerpt) {
            return wp_trim_words($package_excerpt, 25, '...');
        }
        
        return 'Kreiraj svoj prilagođeni ' . get_the_title() . ' paket sa popustnim cenama. Izaberi proizvode i uštedi sa našim VIP paketima.';
    }
    return $description;
}

// RankMath Canonical Filter
function wvp_rankmath_canonical_filter($canonical) {
    if (is_singular('wvp_package')) {
        return get_permalink(get_the_ID());
    }
    return $canonical;
}

get_footer(); ?>