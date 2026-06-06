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

// ============================================================
// Cart update — qty / molienda / remove, returns fresh fragments
// ============================================================
function sc_ajax_update_cart(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    $cart = function_exists( 'WC' ) ? WC()->cart : null;
    if ( ! $cart ) {
        wp_send_json_error( [ 'message' => 'Carrito no disponible.' ] );
    }

    $act = sanitize_text_field( wp_unslash( $_POST['cart_action'] ?? '' ) );
    $key = sanitize_text_field( wp_unslash( $_POST['cart_key'] ?? '' ) );

    if ( ! $key || ! isset( $cart->cart_contents[ $key ] ) ) {
        wp_send_json_error( [ 'message' => 'Item no encontrado.' ] );
    }

    switch ( $act ) {
        case 'update_qty':
            $qty = max( 1, min( 20, absint( $_POST['qty'] ?? 1 ) ) );
            $cart->set_quantity( $key, $qty, false );
            break;

        case 'change_molienda':
            $m = sanitize_text_field( wp_unslash( $_POST['molienda'] ?? '' ) );
            if ( in_array( $m, [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ], true ) ) {
                $cart->cart_contents[ $key ]['molienda'] = $m;
                $cart->set_session();
            }
            break;

        case 'remove':
            $cart->remove_cart_item( $key );
            break;

        default:
            wp_send_json_error( [ 'message' => 'Acción inválida.' ] );
    }

    $cart->calculate_totals();

    // Fragments are inner HTML; the JS replaces the contents of each stable wrapper.
    wp_send_json_success( [
        'fragments' => [
            '.js-cart-items'  => sc_render_part( 'template-parts/cart/cart-items' ),
            '.js-cart-totals' => sc_render_part( 'template-parts/cart/cart-totals' ),
            '.js-banner-slot' => sc_render_part( 'template-parts/shipping-banner' ),
        ],
        'count' => $cart->get_cart_contents_count(),
    ] );
}

add_action( 'wp_ajax_sc_update_cart',        'sc_ajax_update_cart' );
add_action( 'wp_ajax_nopriv_sc_update_cart', 'sc_ajax_update_cart' );

/**
 * Render a template-part to a string.
 */
function sc_render_part( string $slug ): string {
    ob_start();
    get_template_part( $slug );
    return (string) ob_get_clean();
}

// ============================================================
// Add to cart — AJAX for variable products (peso variation + molienda)
// Returns the standard WC fragments (badge, mini-cart, banner).
// ============================================================
function sc_ajax_add_to_cart(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        wp_send_json_error( [ 'message' => 'Carrito no disponible.' ] );
    }

    $product_id   = absint( $_POST['product_id'] ?? 0 );
    $variation_id = absint( $_POST['variation_id'] ?? 0 );
    $qty          = max( 1, min( 20, absint( $_POST['quantity'] ?? 1 ) ) );
    $peso         = sanitize_text_field( wp_unslash( $_POST['peso'] ?? '' ) );
    $molienda     = sanitize_text_field( wp_unslash( $_POST['molienda'] ?? 'Grano' ) );

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => 'Producto inválido.' ] );
    }
    if ( ! in_array( $molienda, [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ], true ) ) {
        $molienda = 'Grano';
    }

    $variation = $peso ? [ 'attribute_pa_peso' => $peso ] : [];
    $item_data = [ 'molienda' => $molienda ];

    $added = WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation, $item_data );

    if ( ! $added ) {
        wp_send_json_error( [ 'message' => 'No se pudo agregar el producto.' ] );
    }

    wp_send_json_success( [
        'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
        'count'     => WC()->cart->get_cart_contents_count(),
    ] );
}

add_action( 'wp_ajax_sc_add_to_cart',        'sc_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_sc_add_to_cart', 'sc_ajax_add_to_cart' );
