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

    $notice = '';

    switch ( $act ) {
        case 'update_qty':
            $qty     = max( 1, min( 20, absint( $_POST['qty'] ?? 1 ) ) );
            $product = $cart->cart_contents[ $key ]['data'] ?? null;

            // Tope por stock (gramos): set_quantity no dispara la validación de WC,
            // así que la aplicamos a mano y limitamos al máximo disponible.
            if ( $product instanceof WC_Product && function_exists( 'sc_can_add_grams' )
                 && ! sc_can_add_grams( $product, $qty, $key ) ) {
                $avail = sc_available_grams( $product );
                $unit  = sc_product_unit_grams( $product );
                $other = sc_grams_in_cart_for( (int) $product->get_stock_managed_by_id(), $key );
                $max   = ( $unit > 0 && null !== $avail ) ? intdiv( max( 0, $avail - $other ), $unit ) : $qty;
                if ( $qty > $max && $max >= 1 ) {
                    $qty    = $max;
                    $notice = 'No hay más stock disponible de este café.';
                } elseif ( $max < 1 ) {
                    // No alcanza ni para una unidad más: dejamos la cantidad actual.
                    $qty    = (int) $cart->cart_contents[ $key ]['quantity'];
                    $notice = 'No hay más stock disponible de este café.';
                }
            }

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
        'notice'    => $notice,
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
 * Make the URLs inside src / srcset / href attributes root-relative.
 *
 * AJAX fragments are rendered with absolute URLs. When the site is viewed
 * through a proxy with a different host (e.g. Local's Live Link tunnel, served
 * over https from a tunnel hostname), WordPress can generate those URLs with a
 * host the visitor's device can't reach, so product images break (showing the
 * placeholder). Stripping the scheme+host from these attributes makes the URLs
 * root-relative, so they inherit the current request's host + scheme and work
 * on the tunnel, on the local host, and in production alike. Only the URL-
 * bearing attributes are touched (text, xmlns, itemtype, etc. are left alone).
 */
function sc_relativize_site_urls( string $html ): string {
    return (string) preg_replace_callback(
        '#\b(src|srcset|href)="([^"]*)"#i',
        static function ( array $m ): string {
            // Drop scheme + host from every URL in the attribute value
            // (srcset may hold several, comma/space separated).
            $value = preg_replace( '#https?://[^/\s,"\']+#i', '', $m[2] );
            return $m[1] . '="' . $value . '"';
        },
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

    // WC()->cart->add_to_cart() NO dispara woocommerce_add_to_cart_validation
    // (eso lo hacen el form handler y el AJAX nativo, no el carrito). Como acá
    // llamamos al carrito directo, corremos la validación a mano (incluye el
    // chequeo de stock por gramos). Si falla, devolvemos el motivo.
    $passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty, $variation_id, $variation, $item_data );
    if ( ! $passed ) {
        $errors = wc_get_notices( 'error' );
        wc_clear_notices();
        $message = ! empty( $errors )
            ? wp_strip_all_tags( (string) ( end( $errors )['notice'] ?? '' ) )
            : 'No se pudo agregar el producto.';
        wp_send_json_error( [ 'message' => $message ] );
    }

    $added = WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation, $item_data );

    if ( ! $added ) {
        // Si falló por validación (p. ej. sin stock), devolvemos ese mensaje.
        $errors = wc_get_notices( 'error' );
        wc_clear_notices();
        $message = ! empty( $errors )
            ? wp_strip_all_tags( (string) ( end( $errors )['notice'] ?? '' ) )
            : 'No se pudo agregar el producto.';
        wp_send_json_error( [ 'message' => $message ] );
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
        wp_send_json_error( [ 'field' => 'password_current', 'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.' ] );
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
    // Capturamos la nueva cookie en $_COOKIE para que, en este mismo request,
    // wp_create_nonce() genere un nonce atado a la sesión NUEVA. Sin esto, el
    // nonce viejo que tiene el JS queda inválido y un segundo cambio sin
    // refrescar devuelve 403 (check_ajax_referer falla).
    add_action( 'set_logged_in_cookie', function ( $logged_in_cookie ) {
        $_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
    } );
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );

    wp_send_json_success( [
        'message'    => 'Tu contraseña se actualizó correctamente.',
        'nonce'      => wp_create_nonce( 'sc_nonce' ),
        // El link de logout de la página quedó con un nonce viejo (la sesión
        // rotó); devolvemos uno fresco para que "Cerrar sesión" no pida
        // confirmación de WordPress.
        'logout_url' => wc_logout_url( home_url() ),
    ] );
}

add_action( 'wp_ajax_sc_change_password', 'sc_ajax_change_password' );

// ============================================================
// Copiar la dirección de envío sobre la de facturación (botón en "Direcciones").
// No toca el email de facturación (envío no lo tiene).
// ============================================================
function sc_ajax_copy_shipping_to_billing(): void {
    check_ajax_referer( 'sc_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.' ] );
    }

    $customer = new WC_Customer( get_current_user_id() );

    // Exigimos que haya una dirección de envío real cargada.
    if ( '' === $customer->get_shipping_address_1() && '' === $customer->get_shipping_city() ) {
        wp_send_json_error( [ 'message' => 'No hay una dirección de envío cargada para copiar.' ] );
    }

    $customer->set_billing_first_name( $customer->get_shipping_first_name() );
    $customer->set_billing_last_name( $customer->get_shipping_last_name() );
    $customer->set_billing_company( $customer->get_shipping_company() );
    $customer->set_billing_address_1( $customer->get_shipping_address_1() );
    $customer->set_billing_address_2( $customer->get_shipping_address_2() );
    $customer->set_billing_city( $customer->get_shipping_city() );
    $customer->set_billing_state( $customer->get_shipping_state() );
    $customer->set_billing_postcode( $customer->get_shipping_postcode() );
    $customer->set_billing_country( $customer->get_shipping_country() );

    // El teléfono de envío existe en WC modernas; lo copiamos si está disponible.
    if ( is_callable( [ $customer, 'get_shipping_phone' ] ) ) {
        $shipping_phone = $customer->get_shipping_phone();
        if ( '' !== $shipping_phone ) {
            $customer->set_billing_phone( $shipping_phone );
        }
    }

    $customer->save();

    wp_send_json_success( [ 'message' => 'Copiamos la dirección de envío a facturación.' ] );
}

add_action( 'wp_ajax_sc_copy_shipping_to_billing', 'sc_ajax_copy_shipping_to_billing' );
