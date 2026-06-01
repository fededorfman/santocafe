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
