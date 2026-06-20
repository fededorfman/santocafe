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
        'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap',
        [],
        null
    );

    // CSS principal
    wp_enqueue_style('santocafe-main', $uri . '/assets/css/main.css', ['santocafe-fonts'], $ver);

    // JS principal (en footer). Nota: NO encolamos wc-cart-fragments — el tema
    // gestiona el mini-cart con su propio AJAX y el server lo renderiza en cada
    // carga; wc-cart-fragments cacheaba el mini-cart en sessionStorage y pisaba
    // el HTML del servidor (mostraba el estado vacío viejo).
    wp_enqueue_script('santocafe-main', $uri . '/assets/js/main.js', ['jquery'], $ver, true);

    // Variables PHP → JS
    wp_localize_script('santocafe-main', 'SC', [
        'ajaxUrl'         => admin_url('admin-ajax.php'),
        'nonce'           => wp_create_nonce('sc_nonce'),
        'freeShippingMin' => (int) get_option('sc_shipping_free_min', 50000),
        'currency'        => get_woocommerce_currency_symbol(),
        'cartUrl'         => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/carrito/'),
        'checkoutUrl'     => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/finalizar-compra/'),
        // Aviso de ajuste de stock (se muestra una vez como toast desde el JS).
        'stockNotice'     => function_exists('sc_pull_stock_notice') ? sc_pull_stock_notice() : '',
    ]);
});

// ============================================================
// WooCommerce: quitar wrappers por defecto que reemplazaremos
// ============================================================
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

add_action('woocommerce_before_main_content', function () {
    echo '<main class="wc-main" id="main">';
});
add_action('woocommerce_after_main_content', function () {
    echo '</main>';
});

// ============================================================
// Rewrite: /guias/ → categoría "guias" (sin prefijo /category/)
// ============================================================
add_action( 'init', function (): void {
    add_rewrite_rule( '^guias/?$', 'index.php?category_name=guias', 'top' );
    add_rewrite_rule( '^guias/page/([0-9]{1,})/?$', 'index.php?category_name=guias&paged=$matches[1]', 'top' );
}, 1 );

// Redirigir /category/guias/ → /guias/ (canonical limpio)
add_action( 'template_redirect', function (): void {
    if ( is_category( 'guias' ) ) {
        $paged    = get_query_var( 'paged', 1 );
        $redirect = $paged > 1
            ? home_url( "/guias/page/{$paged}/" )
            : home_url( '/guias/' );
        if ( home_url( add_query_arg( [] ) ) !== $redirect ) {
            wp_redirect( $redirect, 301 );
            exit;
        }
    }
} );

// ============================================================
// Cargar módulos del tema
// ============================================================
$modules = [
    'inc/theme-helpers.php',
    'inc/woocommerce.php',
    'inc/product-meta.php',
    'inc/ajax-handlers.php',
    'inc/stock.php',
    'inc/seo.php',
    'inc/llms.php',
    'inc/security.php',
    'inc/analytics.php',
    'inc/contact.php',
    'inc/emails.php',
    'inc/scheduled-emails.php',
];

foreach ($modules as $module) {
    $path = get_template_directory() . '/' . $module;
    if (file_exists($path)) {
        require_once $path;
    }
}
