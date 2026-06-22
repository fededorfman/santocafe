<?php
defined('ABSPATH') || exit;

/**
 * WooCommerce configuration for Santo Café.
 * Theme-level WC customizations: currency, styles, cart fragments.
 */

// ============================================================
// Currency — CLP, format $XX.XXX with no decimals
// ============================================================
add_filter( 'woocommerce_currency', fn() => 'CLP' );

add_filter( 'wc_price_args', function ( array $args ): array {
    $args['decimals']           = 0;
    $args['decimal_separator']  = '';
    $args['thousand_separator'] = '.';
    return $args;
} );

// ============================================================
// Country — Chile as default store location
// ============================================================
add_filter( 'woocommerce_get_base_location', function ( string $location ): string {
    return 'CL';
} );

// ============================================================
// Etiqueta de la nota del pedido: "Note:" (es_CL → "Aviso:") => "Notas:".
// Se hace por gettext para que sea coherente en todos lados (detalle del
// pedido, "pedido recibido", emails) sin editar templates ni el core.
// ============================================================
add_filter( 'gettext', function ( $translation, $text, $domain ) {
    if ( 'woocommerce' === $domain && 'Note:' === $text ) {
        return 'Notas:';
    }
    return $translation;
}, 20, 3 );

// ============================================================
// Styles — remove WC layout/responsive CSS (we provide our own)
// ============================================================
add_filter( 'woocommerce_enqueue_styles', function ( array $styles ): array {
    unset( $styles['woocommerce-layout'] );
    unset( $styles['woocommerce-smallscreen'] );
    return $styles;
} );

// ============================================================
// Cart fragments — keep badge, mini-cart drawer and shipping banner
// in sync after any cart change (custom add + native mini-cart remove).
// ============================================================
add_filter( 'woocommerce_add_to_cart_fragments', function ( array $fragments ): array {
    $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    // Header badge
    ob_start();
    ?>
    <span class="cart-icon__badge js-cart-count <?php echo $count ? '' : 'is-empty'; ?>">
        <?php echo esc_html( $count ); ?>
    </span>
    <?php
    $fragments['.cart-icon__badge'] = ob_get_clean();

    // Mini-cart drawer contents (list + subtotal) — uses the overridden mini-cart.php
    ob_start();
    ?>
    <div class="widget_shopping_cart_content">
        <?php woocommerce_mini_cart(); ?>
    </div>
    <?php
    $fragments['div.widget_shopping_cart_content'] = ob_get_clean();

    // Shipping banner
    ob_start();
    get_template_part( 'template-parts/shipping-banner' );
    $fragments['.js-banner-slot'] = '<div class="js-banner-slot">' . ob_get_clean() . '</div>';

    return $fragments;
} );

// ============================================================
// Suppress the native "X has been added to your cart" notice —
// the slide-in drawer is the add-to-cart feedback.
// ============================================================
add_filter( 'wc_add_to_cart_message_html', '__return_null' );

// ============================================================
// My Account — enable registration on the login page and show a
// password field (instead of emailing an auto-generated one).
// ============================================================
add_filter( 'option_woocommerce_enable_myaccount_registration', fn() => 'yes' );
add_filter( 'option_woocommerce_registration_generate_password', fn() => 'no' );

// Privacy notice on the register form — Spanish, short.
add_filter( 'option_woocommerce_registration_privacy_policy_text', fn() =>
    'Usamos tus datos para gestionar tu cuenta y tu experiencia en este sitio, según nuestra [privacy_policy].'
);

// El link de "Términos y Condiciones" del checkout (bloque) sale de la página de
// términos configurada en WooCommerce. Si no hay ninguna, el link no funciona.
// La apuntamos a "Condiciones de venta" buscándola por slug (sirve en cualquier
// entorno), respetando la que ya esté configurada en los ajustes de WC.
add_filter( 'woocommerce_terms_and_conditions_page_id', function ( $page_id ) {
    if ( (int) $page_id > 0 ) {
        return $page_id;
    }
    $page = get_page_by_path( 'condiciones-de-venta' );
    return $page ? (int) $page->ID : $page_id;
} );

// Páginas de checkout CLÁSICO (p. ej. "pagar pedido" / order-pay): texto del
// checkbox de términos y del aviso de privacidad, en español, con mayúsculas y
// links que funcionan. (El checkout por bloques usa sus propios textos, no estos
// filtros, así que esto no lo toca.)
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', function () {
    return sprintf(
        'He leído y acepto los <a href="%s" target="_blank" rel="noopener">Términos y Condiciones</a>',
        esc_url( sc_legal_page_url( 'condiciones-de-venta' ) )
    );
}, 20 );

add_filter( 'option_woocommerce_checkout_privacy_policy_text', function () {
    return sprintf(
        'Usamos tus datos para procesar tu pedido y mejorar tu experiencia en el sitio, como se describe en nuestra <a href="%s" target="_blank" rel="noopener">Política de Privacidad</a>.',
        esc_url( sc_legal_page_url( 'politica-de-privacidad' ) )
    );
} );

// URL de una página legal por slug (fallback al permalink por defecto).
function sc_legal_page_url( string $slug ): string {
    $page = get_page_by_path( $slug );
    return $page ? get_permalink( $page ) : home_url( "/{$slug}/" );
}

// Acciones del pedido (Pagar/Cancelar): WooCommerce las pone DENTRO del <tfoot>
// de la tabla de detalles, y como tbody y tfoot comparten ancho de columna, esos
// botones estiran la columna y aplastan la del producto. Las re-renderizamos en
// una fila propia debajo de la tabla (la fila nativa se oculta por CSS).
add_action( 'woocommerce_order_details_after_order_table', function ( $order ): void {
    if ( ! $order instanceof WC_Order ) {
        return;
    }
    $actions = array_filter(
        wc_get_account_orders_actions( $order ),
        static fn( $key ) => 'view' !== $key,
        ARRAY_FILTER_USE_KEY
    );
    if ( empty( $actions ) ) {
        return;
    }
    echo '<div class="sc-order-actions">';
    foreach ( $actions as $key => $action ) {
        printf(
            '<a href="%s" class="btn %s sc-order-actions__btn %s">%s</a>',
            esc_url( $action['url'] ),
            'pay' === $key ? 'btn--primary' : 'btn--outline',
            esc_attr( sanitize_html_class( $key ) ),
            esc_html( $action['name'] )
        );
    }
    echo '</div>';
} );

// Drop the password strength meter (relax password rules) and the cart-fragments
// script (its sessionStorage cache fought with the theme's own mini-cart AJAX).
add_action( 'wp_enqueue_scripts', function (): void {
    wp_dequeue_script( 'wc-password-strength-meter' );
    wp_dequeue_script( 'wc-cart-fragments' );
}, 99 );

// Register validation: name + surname required, password >= 8 with a letter and a number.
add_action( 'woocommerce_register_post', function ( $username, $email, $errors ): void {
    if ( empty( trim( $_POST['first_name'] ?? '' ) ) ) {
        $errors->add( 'first_name_required', 'Ingresa tu nombre.' );
    }
    if ( empty( trim( $_POST['last_name'] ?? '' ) ) ) {
        $errors->add( 'last_name_required', 'Ingresa tu apellido.' );
    }

    $password = (string) ( $_POST['password'] ?? '' );
    if ( strlen( $password ) < 8 || ! preg_match( '/[A-Za-z]/', $password ) || ! preg_match( '/[0-9]/', $password ) ) {
        $errors->add(
            'password_invalid',
            'La contraseña debe tener al menos 8 caracteres, con al menos una letra y un número.'
        );
    }
}, 10, 3 );

// Save first/last name on registration.
add_action( 'woocommerce_created_customer', function ( int $customer_id ): void {
    if ( ! empty( $_POST['first_name'] ) ) {
        update_user_meta( $customer_id, 'first_name', sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) );
    }
    if ( ! empty( $_POST['last_name'] ) ) {
        update_user_meta( $customer_id, 'last_name', sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) );
    }
} );

// Vincular pedidos hechos como invitado a la cuenta recién creada cuando coincide
// el email. Así un comprador que se registra después de comprar (desde la
// invitación de la página de "pedido recibido") ve ese pedido en su historial.
add_action( 'woocommerce_created_customer', function ( int $customer_id ): void {
    if ( function_exists( 'wc_update_new_customer_past_orders' ) ) {
        wc_update_new_customer_past_orders( $customer_id );
    }
}, 20 );

// Si el email recién registrado ya tenía pedidos (como invitado), precargar la
// dirección por defecto (envío + facturación) con la del último pedido, para que
// el cliente no tenga que volver a cargarla. Corre después de vincular pedidos.
add_action( 'woocommerce_created_customer', function ( int $customer_id ): void {
    if ( ! function_exists( 'wc_get_orders' ) ) {
        return;
    }

    $user = get_userdata( $customer_id );
    if ( ! $user || empty( $user->user_email ) ) {
        return;
    }

    $customer = new WC_Customer( $customer_id );

    // No pisar una dirección ya cargada (el registro no pide dirección, así que
    // normalmente está vacía; esto es solo defensa por las dudas).
    if ( '' !== $customer->get_billing_address_1() || '' !== $customer->get_shipping_address_1() ) {
        return;
    }

    // Último pedido asociado a ese email (incluye los hechos como invitado).
    $orders = wc_get_orders( [
        'billing_email' => $user->user_email,
        'limit'         => 1,
        'orderby'       => 'date',
        'order'         => 'DESC',
    ] );

    if ( empty( $orders ) ) {
        return;
    }

    $order  = $orders[0];
    $fields = [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' ];

    foreach ( [ 'billing', 'shipping' ] as $type ) {
        foreach ( $fields as $field ) {
            $getter = "get_{$type}_{$field}";
            $setter = "set_{$type}_{$field}";
            if ( is_callable( [ $order, $getter ] ) && is_callable( [ $customer, $setter ] ) ) {
                $value = (string) $order->$getter();
                if ( '' !== $value ) {
                    $customer->$setter( $value );
                }
            }
        }
    }

    // Email de facturación: el del pedido o, si no, el de la cuenta.
    $customer->set_billing_email( $order->get_billing_email() ?: $user->user_email );

    $customer->save();
}, 25 );

// ============================================================
// Fecha de nacimiento (Detalles de la cuenta): se guarda UNA sola vez.
// Una vez cargada, el form la muestra read-only y acá ignoramos cualquier
// intento posterior de cambiarla (anti-abuso del regalo de cumpleaños).
// ============================================================
function sc_valid_birthday( string $raw ): bool {
    $d = DateTime::createFromFormat( '!Y-m-d', $raw );
    if ( ! $d || $d->format( 'Y-m-d' ) !== $raw ) {
        return false;
    }
    return $d < new DateTime( 'today' ) && (int) $d->format( 'Y' ) >= 1900;
}

// Validación: si mandan una fecha (y todavía no hay una guardada), tiene que ser válida.
add_action( 'woocommerce_save_account_details_errors', function ( $errors, $user ): void {
    if ( get_user_meta( $user->ID, 'sc_birthday', true ) ) {
        return; // ya cargada → inmutable, no se valida
    }
    $raw = sanitize_text_field( wp_unslash( $_POST['sc_birthday'] ?? '' ) );
    if ( '' !== $raw && ! sc_valid_birthday( $raw ) ) {
        $errors->add( 'sc_birthday_invalid', 'La fecha de nacimiento no es válida.' );
    }
}, 10, 2 );

// Guardado: solo si no había una previa y la fecha es válida.
add_action( 'woocommerce_save_account_details', function ( $user_id ): void {
    if ( get_user_meta( $user_id, 'sc_birthday', true ) ) {
        return;
    }
    $raw = sanitize_text_field( wp_unslash( $_POST['sc_birthday'] ?? '' ) );
    if ( '' !== $raw && sc_valid_birthday( $raw ) ) {
        update_user_meta( $user_id, 'sc_birthday', $raw );
    }
} );

// Preferencia de correos promocionales (toggle en Mi cuenta).
// El nonce de la cuenta ya lo valida WooCommerce antes de este hook.
add_action( 'woocommerce_save_account_details', function ( $user_id ): void {
    if ( ! isset( $_POST['sc_email_promos_present'] ) ) {
        return; // el form no incluía el toggle
    }
    if ( ! empty( $_POST['sc_email_promos'] ) ) {
        delete_user_meta( $user_id, 'sc_email_optout' ); // suscripto
    } else {
        update_user_meta( $user_id, 'sc_email_optout', 1 ); // dado de baja
    }
} );

// Notices: don't print them above the login/register forms — the templates
// print them inside the relevant form instead.
add_action( 'init', function (): void {
    remove_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
} );

// Login: mensaje genérico para no revelar si el email existe (anti-enumeración).
// Se engancha en 'authenticate' (post wp_authenticate_*, prioridad > 20) porque
// los errores de enumeración los devuelve wp_signon, no el filtro de WooCommerce.
// Cubre tanto el login de la tienda como el de wp-admin. Los errores de campo
// vacío (empty_username/empty_password) se dejan pasar: son ayuda, no enumeración.
add_filter( 'authenticate', function ( $user ) {
    if ( is_wp_error( $user ) ) {
        $enum = [ 'invalid_username', 'invalid_email', 'incorrect_password' ];
        if ( array_intersect( (array) $user->get_error_codes(), $enum ) ) {
            return new WP_Error(
                'sc_login_failed',
                'Email o contraseña incorrectos. Revisa los datos e intenta de nuevo.'
            );
        }
    }
    return $user;
}, 30 );

// My Account: drop "Escritorio" (dashboard), Descargas y Cerrar sesión del menú
// (el logout vive ahora como botón al final de "Detalles de la cuenta").
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
    unset( $items['dashboard'] );
    unset( $items['downloads'] );
    unset( $items['customer-logout'] );
    return $items;
} );

// NOTE: account endpoint slugs (pedidos / direcciones / editar / recuperar-password)
// are stored as WooCommerce options in the DB (Ajustes → Avanzado → Endpoints de la
// cuenta), because WC caches the query vars before the theme loads. They were set via:
//   wp option update woocommerce_myaccount_orders_endpoint pedidos
//   wp option update woocommerce_myaccount_view_order_endpoint orden
//   wp option update woocommerce_myaccount_edit_address_endpoint direcciones
//   wp option update woocommerce_myaccount_edit_account_endpoint editar
//   wp option update woocommerce_myaccount_lost_password_endpoint recuperar-password
//   wp rewrite flush
//
// El "pedido recibido" del checkout es 'gracias' → /checkout/gracias/{id}.
// OJO — colisión de slugs: los endpoints de WooCommerce comparten un namespace
// global por slug, así que NO puede coincidir con 'orden' (view-order, /cuenta/orden)
// o rompe la vista de pedido de la cuenta (ERR_TOO_MANY_REDIRECTS). La CLAVE del
// query var sigue siendo 'order-received'. Se setea en DOS pasos (WC fija los
// endpoints al init, por eso el flush va aparte):
//   wp option update woocommerce_checkout_order_received_endpoint gracias
//   wp rewrite flush
//
// "Pagar pedido" (endpoint order-pay) es 'pagar-orden' → /checkout/pagar-orden/{id}.
// Mismo motivo (el slug se cachea al init, un filtro del tema llega tarde): se
// setea por DB + flush. No colisiona con otros slugs.
//   wp option update woocommerce_checkout_pay_endpoint pagar-orden
//   wp rewrite flush
//
// The product permalink base is also a DB option (Ajustes → Enlaces permanentes →
// Productos → base personalizada). Set to /producto via:
//   woocommerce_permalinks['product_base'] = '/producto'  +  rewrite flush
//
// Shipping (also DB state — Ajustes → Envío):
//   - Zona "Región Metropolitana de Santiago" (CL:CL-RM) con dos métodos:
//       · "Envío gratis"  (mínimo $50.000)
//       · "Tarifa plana"  ($3.990 — placeholder, ajustar al costo real)
//     Cuando el envío gratis aplica, el filtro woocommerce_package_rates de
//     abajo oculta la tarifa plana.
//   - woocommerce_ship_to_destination = 'shipping'  (la dirección de entrega
//     es el destino por defecto, no la de facturación).

add_action( 'template_redirect', function (): void {
    if ( function_exists( 'is_account_page' ) && is_account_page()
        && is_user_logged_in() && ! is_wc_endpoint_url() ) {
        wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
        exit;
    }
} );

// Tras la compra: llevar al detalle del pedido en "Mi cuenta" (/cuenta/orden/{id})
// en vez de la página genérica de "pedido recibido". Solo si el usuario está
// logueado y es el dueño del pedido — los invitados (guest checkout activado) no
// tienen vista de cuenta, así que se quedan en order-received, su única
// confirmación. Flow ya completó el pago antes de esta página y no engancha
// woocommerce_thankyou, así que el redirect no interfiere con el cobro.
add_action( 'template_redirect', function (): void {
    // is_order_received_page() exige estar en la PÁGINA de checkout (no solo
    // que exista el query var), así que nunca se dispara en /cuenta/orden ni
    // puede entrar en un loop de redirección.
    if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
        return;
    }

    global $wp;
    $order_id = absint( $wp->query_vars['order-received'] ?? 0 );
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order
        || ! is_user_logged_in()
        || (int) $order->get_customer_id() !== get_current_user_id() ) {
        return;
    }

    wp_safe_redirect( wc_get_endpoint_url( 'view-order', $order_id, wc_get_page_permalink( 'myaccount' ) ) );
    exit;
} );

// ============================================================
// Address fields — reorder + relabel (My Account + checkout)
// Order: Nombres, Apellidos, País, Región, Ciudad, Dirección,
//        Dirección 2, Teléfono. Labels in plain Spanish.
// Labels/priorities are safe to apply everywhere; email + postcode
// are only stripped on the My Account address screen (checkout still
// needs the email to send the order confirmation).
// ============================================================
function sc_address_fields_layout( array $fields, string $prefix ): array {
    $set = static function ( string $key, array $changes ) use ( &$fields, $prefix ): void {
        if ( isset( $fields[ $prefix . $key ] ) ) {
            $fields[ $prefix . $key ] = array_merge( $fields[ $prefix . $key ], $changes );
        }
    };

    $set( 'first_name', [ 'priority' => 10, 'label' => 'Nombres' ] );
    $set( 'last_name',  [ 'priority' => 20, 'label' => 'Apellidos' ] );
    $set( 'country',    [ 'priority' => 30, 'label' => 'País' ] );
    $set( 'state',      [ 'priority' => 40, 'label' => 'Región' ] );
    $set( 'city',       [ 'priority' => 50, 'label' => 'Ciudad' ] );
    $set( 'address_1',  [ 'priority' => 60, 'label' => 'Dirección' ] );
    $set( 'address_2',  [ 'priority' => 70, 'label' => 'Dirección (depto, oficina, etc.)', 'placeholder' => 'Depto, oficina, etc. (opcional)' ] );
    $set( 'phone',      [ 'priority' => 80, 'label' => 'Teléfono' ] );

    // My Account address form only: drop email + postcode.
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        unset( $fields[ $prefix . 'email' ], $fields[ $prefix . 'postcode' ] );
    }

    return $fields;
}
add_filter( 'woocommerce_billing_fields',  fn( array $f ): array => sc_address_fields_layout( $f, 'billing_' ) );
add_filter( 'woocommerce_shipping_fields', fn( array $f ): array => sc_address_fields_layout( $f, 'shipping_' ) );

// WooCommerce's address-i18n.js relabels + reorders country/state/city/address
// on the client from the per-country *locale* data. Mirror our labels/order
// there too, otherwise the script reverts País→"País/Región",
// Ciudad→"Comuna / Ciudad", and pushes the address fields above city/region.
add_filter( 'woocommerce_get_country_locale_default', function ( array $fields ): array {
    $set = static function ( string $key, array $changes ) use ( &$fields ): void {
        if ( isset( $fields[ $key ] ) ) {
            $fields[ $key ] = array_merge( $fields[ $key ], $changes );
        }
    };

    $set( 'first_name', [ 'priority' => 10 ] );
    $set( 'last_name',  [ 'priority' => 20 ] );
    $set( 'country',    [ 'priority' => 30, 'label' => 'País' ] );
    $set( 'state',      [ 'priority' => 40 ] ); // CL locale labels this "Región".
    $set( 'city',       [ 'priority' => 50, 'label' => 'Ciudad' ] );
    $set( 'address_1',  [ 'priority' => 60, 'label' => 'Dirección' ] );
    $set( 'address_2',  [ 'priority' => 70 ] );
    $set( 'phone',      [ 'priority' => 80 ] );

    return $fields;
} );

// ============================================================
// Shipping — when free shipping is available (cart ≥ mínimo), hide the
// flat-rate fallback so the customer only sees "Envío gratis".
// ============================================================
add_filter( 'woocommerce_package_rates', function ( array $rates ): array {
    $free = array_filter(
        $rates,
        static fn( $rate ): bool => 'free_shipping' === $rate->get_method_id()
    );
    return $free ? $free : $rates;
}, 100 );

// ============================================================
// Molienda — persist as cart item data (not a WC variation)
// ============================================================

// 1. Save molienda when item is added to cart
add_filter( 'woocommerce_add_cart_item_data', function ( array $data, int $product_id, int $variation_id ): array {
    if ( ! empty( $_POST['molienda'] ) ) {
        $molienda = sanitize_text_field( wp_unslash( $_POST['molienda'] ) );
        // Solo valores permitidos (un POST forjado no puede inyectar otra cosa)
        if ( in_array( $molienda, [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ], true ) ) {
            $data['molienda'] = $molienda;
        }
    }
    return $data;
}, 10, 3 );

// 2. Display molienda in cart and checkout review
add_filter( 'woocommerce_get_item_data', function ( array $item_data, array $cart_item ): array {
    if ( ! empty( $cart_item['molienda'] ) ) {
        $item_data[] = [
            'key'   => __( 'Molienda', 'santocafe' ),
            'value' => esc_html( $cart_item['molienda'] ),
        ];
    }
    return $item_data;
}, 10, 2 );

// 3. Save molienda as order line item meta (shows in admin + emails)
add_action( 'woocommerce_checkout_create_order_line_item', function ( \WC_Order_Item_Product $item, string $cart_item_key, array $values ): void {
    if ( ! empty( $values['molienda'] ) ) {
        $item->add_meta_data( __( 'Molienda', 'santocafe' ), esc_html( $values['molienda'] ), true );
    }
}, 10, 3 );

// ============================================================
// Session & cookie durations
// — Auth cookie: 1 week (no "Recordarme") / 3 months (con "Recordarme")
// — WooCommerce guest session (carrito): 30 días
// ============================================================
add_filter( 'auth_cookie_expiration', function ( int $seconds, int $user_id, bool $remember ): int {
    return $remember ? 3 * MONTH_IN_SECONDS : WEEK_IN_SECONDS;
}, 10, 3 );

add_filter( 'wc_session_expiration', fn() => 30 * DAY_IN_SECONDS );
add_filter( 'wc_session_expiring',   fn() => 30 * DAY_IN_SECONDS - HOUR_IN_SECONDS );

// ============================================================
// Body classes
// ============================================================
add_filter( 'body_class', function ( array $classes ): array {
    if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
        $classes[] = 'is-woocommerce';
    }
    return $classes;
} );

// ============================================================
// Aplicar cupón desde la URL (?sc_coupon=CODE) — lo usan los emails.
// Se guarda en la sesión y se aplica solo cuando el carrito tiene productos,
// así el botón del email puede llevar al inicio aunque no haya carrito todavía.
// ============================================================
// Aplica el cupón pendiente de la sesión cuando el carrito tiene productos.
function sc_apply_pending_coupon(): void {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }
    $wc = WC();
    if ( ! $wc->session || ! $wc->cart ) {
        return;
    }
    $code = $wc->session->get( 'sc_pending_coupon' );
    if ( ! $code || $wc->cart->is_empty() || $wc->cart->has_discount( $code ) ) {
        return;
    }
    // Un solo intento: si el código no es válido, no reintentar (evita spamear el aviso).
    $wc->cart->apply_coupon( $code );
    $wc->session->set( 'sc_pending_coupon', null );
}

add_action( 'wp_loaded', function () {
    if ( ! function_exists( 'WC' ) || ! WC()->session ) {
        return;
    }
    // 1) Capturar el código de la URL (?sc_coupon=CODE) y guardarlo en la sesión.
    if ( ! empty( $_GET['sc_coupon'] ) && ! is_admin() ) {
        $code = sanitize_text_field( wp_unslash( $_GET['sc_coupon'] ) );
        if ( '' !== $code ) {
            if ( ! WC()->session->has_session() ) {
                WC()->session->set_customer_session_cookie( true ); // persistir para invitados
            }
            WC()->session->set( 'sc_pending_coupon', $code );
        }
    }
    // 2) Aplicarlo si ya hay productos (front-end: carrito, checkout, etc.).
    if ( ! is_admin() ) {
        sc_apply_pending_coupon();
    }
}, 20 );

// También al agregar un producto (cubre el alta por AJAX).
add_action( 'woocommerce_add_to_cart', 'sc_apply_pending_coupon' );

// ============================================================
// Recomprar un pedido (?sc_reorder=ID&k=TOKEN): rellena el carrito con los
// mismos productos (peso + molienda) y manda al carrito. Lo usa el email de
// reposición ("Volver a pedir").
// ============================================================
function sc_reorder_token( $order_id ) {
    return wp_hash( 'sc_reorder_' . (int) $order_id );
}

function sc_reorder_url( $order_id ) {
    return add_query_arg(
        array( 'sc_reorder' => (int) $order_id, 'k' => sc_reorder_token( $order_id ) ),
        home_url( '/' )
    );
}

add_action( 'wp_loaded', function () {
    if ( empty( $_GET['sc_reorder'] ) || is_admin() || ! function_exists( 'WC' ) ) {
        return;
    }
    $oid = absint( $_GET['sc_reorder'] );
    $k   = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
    if ( ! $oid || ! hash_equals( sc_reorder_token( $oid ), $k ) ) {
        return;
    }
    $order = wc_get_order( $oid );
    if ( ! $order || ! WC()->cart ) {
        return;
    }
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product || ! $product->exists() ) {
            continue;
        }
        $variation_id = $item->get_variation_id();
        $variation    = array();
        if ( $variation_id ) {
            $vp = wc_get_product( $variation_id );
            if ( $vp ) {
                $variation = $vp->get_variation_attributes();
            }
        }
        $cart_item_data = array();
        $molienda       = $item->get_meta( 'Molienda' );
        if ( $molienda ) {
            $cart_item_data['molienda'] = $molienda;
        }
        WC()->cart->add_to_cart(
            $item->get_product_id(),
            max( 1, (int) $item->get_quantity() ),
            $variation_id,
            $variation,
            $cart_item_data
        );
    }
    // A la home con el drawer del carrito abierto (no usamos /cart).
    wp_safe_redirect( home_url( '/?sc_opencart=1' ) );
    exit;
}, 20 );

// ============================================================
// /cart y /shop no se usan: redirigir a la home (el carrito vive en el drawer).
// ============================================================
add_action( 'template_redirect', function () {
    if ( is_admin() || ! function_exists( 'is_cart' ) ) {
        return;
    }
    if ( is_cart() || is_shop() ) {
        wp_safe_redirect( home_url( '/' ), 302 );
        exit;
    }
} );

// ============================================================
// Estado de pedido "Entregado" (posterior a "Completado").
// Lo marca el admin al entregar; dispara el email de reseña a los N días.
// ============================================================
add_action( 'init', function () {
    register_post_status( 'wc-entregado', array(
        'label'                     => 'Entregado',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        /* translators: %s: cantidad de pedidos */
        'label_count'               => _n_noop( 'Entregado <span class="count">(%s)</span>', 'Entregado <span class="count">(%s)</span>' ),
    ) );
} );

// Agregar "Entregado" al selector de estados, justo después de "Completado".
add_filter( 'wc_order_statuses', function ( array $statuses ): array {
    $out = array();
    foreach ( $statuses as $key => $label ) {
        $out[ $key ] = $label;
        if ( 'wc-completed' === $key ) {
            $out['wc-entregado'] = 'Entregado';
        }
    }
    return $out;
} );

// Guardar la fecha de entrega la primera vez que el pedido pasa a "Entregado".
add_action( 'woocommerce_order_status_entregado', function ( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && ! $order->get_meta( '_sc_delivered_at' ) ) {
        $order->update_meta_data( '_sc_delivered_at', time() );
        $order->save();
    }
} );
