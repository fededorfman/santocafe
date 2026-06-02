<?php
defined('ABSPATH') || exit;

/**
 * Santo Café — AJAX handlers.
 */

// ============================================================
// Shipping progress — used by the header banner
// Returns how much the user needs to add to reach free shipping.
// ============================================================
function sc_ajax_shipping_progress(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    $min      = (int) get_option( 'sc_shipping_free_min', 50000 );
    $cart     = function_exists( 'WC' ) ? WC()->cart : null;
    $subtotal = $cart ? (int) $cart->get_subtotal() : 0;
    $gap      = max( 0, $min - $subtotal );

    wp_send_json_success( [
        'gap'      => $gap,
        'gap_fmt'  => sc_format_clp( $gap ),
        'min'      => $min,
        'subtotal' => $subtotal,
        'reached'  => $gap === 0,
    ] );
}

add_action( 'wp_ajax_sc_shipping_progress',        'sc_ajax_shipping_progress' );
add_action( 'wp_ajax_nopriv_sc_shipping_progress', 'sc_ajax_shipping_progress' );

// ============================================================
// Product Quick View — returns rendered modal HTML
// ============================================================
function sc_ajax_product_quick_view(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    $product_id = absint( $_POST['product_id'] ?? 0 );
    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => 'ID de producto inválido.' ] );
    }

    global $product;
    $product = wc_get_product( $product_id );

    if ( ! $product || ! $product->is_visible() ) {
        wp_send_json_error( [ 'message' => 'Producto no encontrado.' ] );
    }

    // Set up post data so get_the_title(), get_the_post_thumbnail(), etc. work.
    global $post;
    $post = get_post( $product_id );
    setup_postdata( $post );

    ob_start();
    get_template_part( 'template-parts/product/quick-view-modal' );
    $html = ob_get_clean();

    wp_reset_postdata();

    wp_send_json_success( [ 'html' => $html ] );
}

add_action( 'wp_ajax_sc_product_quick_view',        'sc_ajax_product_quick_view' );
add_action( 'wp_ajax_nopriv_sc_product_quick_view', 'sc_ajax_product_quick_view' );
