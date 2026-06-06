<?php
defined('ABSPATH') || exit;

// ============================================================
// Theme Setup
// ============================================================
add_action('after_setup_theme', function () {
    load_theme_textdomain('santocafe', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ]);

    register_nav_menus([
        'primary' => __('Menú Principal', 'santocafe'),
        'footer'  => __('Menú Footer', 'santocafe'),
    ]);
});

// ============================================================
// Enqueue de estilos y scripts
// ============================================================
add_action('wp_enqueue_scripts', function () {
    $ver = wp_get_theme()->get('Version');
    $uri = get_template_directory_uri();

    // Google Fonts
    wp_enqueue_style(
        'santocafe-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap',
        [],
        null
    );

    // CSS principal
    wp_enqueue_style('santocafe-main', $uri . '/assets/css/main.css', ['santocafe-fonts'], $ver);

    // WooCommerce mini-cart fragments (refresh sin recargar + remove AJAX nativo)
    wp_enqueue_script('wc-cart-fragments');

    // JS principal (en footer)
    wp_enqueue_script('santocafe-main', $uri . '/assets/js/main.js', ['jquery', 'wc-cart-fragments'], $ver, true);

    // Variables PHP → JS
    wp_localize_script('santocafe-main', 'SC', [
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('sc_nonce'),
        'freeShippingMin' => (int) get_option('sc_shipping_free_min', 50000),
        'currency'        => get_woocommerce_currency_symbol(),
        'cartUrl'         => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/carrito/'),
        'checkoutUrl'     => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/finalizar-compra/'),
    ]);
});

// ============================================================
// WooCommerce: quitar wrappers por defecto que reemplazaremos
// ============================================================
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

add_action('woocommerce_before_main_content', function () {
    echo '<main class="wc-main">';
});
add_action('woocommerce_after_main_content', function () {
    echo '</main>';
});

// ============================================================
// Cargar módulos del tema
// ============================================================
$modules = [
    'inc/theme-helpers.php',
    'inc/woocommerce.php',
    'inc/product-meta.php',
    'inc/ajax-handlers.php',
];

foreach ($modules as $module) {
    $path = get_template_directory() . '/' . $module;
    if (file_exists($path)) {
        require_once $path;
    }
}
