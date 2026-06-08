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

    wp_send_json_success( [
        'fragments' => sc_get_cart_fragments(),
        'count'     => $cart->get_cart_contents_count(),
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

/**
 * Convert the site's own absolute URLs to root-relative ones.
 *
 * AJAX fragments are rendered with absolute home_url() URLs. When the site is
 * viewed through a proxy with a different host (e.g. Local's Live Link tunnel,
 * served over https from a tunnel hostname), those absolute http://santocafe.local
 * URLs can't be resolved by the visitor's device and product images break
 * (showing the dark placeholder). Root-relative URLs inherit the current
 * request's scheme + host, so they work on the tunnel, on the local host, and
 * in production alike. External URLs are left untouched.
 */
function sc_relativize_site_urls( string $html ): string {
    $host = wp_parse_url( home_url(), PHP_URL_HOST );
    if ( ! $host ) {
        return $html;
    }

    return (string) preg_replace(
        '#https?://' . preg_quote( $host, '#' ) . '(?::\d+)?#i',
        '',
        $html
    );
}

/**
 * Build the full set of cart fragments (badge, mini-cart drawer, banner,
 * cart-page items + totals). All wrapper-included → applied with replaceWith.
 * Shared by sc_add_to_cart and sc_update_cart so drawer and cart page stay
 * in sync regardless of where the change originated.
 */
function sc_get_cart_fragments(): array {
    // badge + div.widget_shopping_cart_content + .js-banner-slot (all wrapped)
    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', [] );

    // Cart page wrappers (no-op on pages without them)
    $fragments['.js-cart-items'] =
        '<div class="cart-layout__items js-cart-items">'
        . sc_render_part( 'template-parts/cart/cart-items' )
        . '</div>';

    $fragments['.js-cart-totals'] =
        '<aside class="cart-layout__summary js-cart-totals">'
        . sc_render_part( 'template-parts/cart/cart-totals' )
        . '</aside>';

    // Make the site's own URLs root-relative so AJAX-rendered images/links
    // survive being viewed through a different host (Live Link tunnel, etc.).
    return array_map( 'sc_relativize_site_urls', $fragments );
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
        'fragments' => sc_get_cart_fragments(),
        'count'     => WC()->cart->get_cart_contents_count(),
    ] );
}

add_action( 'wp_ajax_sc_add_to_cart',        'sc_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_sc_add_to_cart', 'sc_ajax_add_to_cart' );

// ============================================================
// Change password — inline (no page reload) for "Detalles de la cuenta".
// Returns per-field errors so the form can mark them without refreshing.
// ============================================================
function sc_ajax_change_password(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'field' => 'password_current', 'message' => 'Tu sesión expiró. Volvé a iniciar sesión.' ] );
    }

    $user    = wp_get_current_user();
    $current = (string) ( $_POST['current'] ?? '' );
    $new     = (string) ( $_POST['new'] ?? '' );

    if ( '' === $current ) {
        wp_send_json_error( [ 'field' => 'password_current', 'message' => 'Este campo es obligatorio.' ] );
    }
    if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
        wp_send_json_error( [ 'field' => 'password_current', 'message' => 'La contraseña actual es incorrecta.' ] );
    }
    if ( strlen( $new ) < 8 || ! preg_match( '/[A-Za-z]/', $new ) || ! preg_match( '/[0-9]/', $new ) ) {
        wp_send_json_error( [ 'field' => 'password_1', 'message' => 'Mínimo 8 caracteres, con al menos una letra y un número.' ] );
    }

    wp_set_password( $new, $user->ID );

    // wp_set_password invalidates the session — re-authenticate so the user
    // stays logged in after changing their own password.
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );

    wp_send_json_success( [ 'message' => 'Tu contraseña se actualizó correctamente.' ] );
}

add_action( 'wp_ajax_sc_change_password', 'sc_ajax_change_password' );
