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

// Relax password rules: drop the "strong password" requirement (strength meter).
add_action( 'wp_enqueue_scripts', function (): void {
    wp_dequeue_script( 'wc-password-strength-meter' );
}, 99 );

// Register validation: name + surname required, password >= 8 with a letter and a number.
add_action( 'woocommerce_register_post', function ( $username, $email, $errors ): void {
    if ( empty( trim( $_POST['first_name'] ?? '' ) ) ) {
        $errors->add( 'first_name_required', 'Ingresá tu nombre.' );
    }
    if ( empty( trim( $_POST['last_name'] ?? '' ) ) ) {
        $errors->add( 'last_name_required', 'Ingresá tu apellido.' );
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

// Notices: don't print them above the login/register forms — the templates
// print them inside the relevant form instead.
add_action( 'init', function (): void {
    remove_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10 );
} );

// My Account: drop "Escritorio" (dashboard) from the menu and make Pedidos the landing.
add_filter( 'woocommerce_account_menu_items', function ( array $items ): array {
    unset( $items['dashboard'] );
    unset( $items['downloads'] );
    return $items;
} );

// NOTE: account endpoint slugs (pedidos / direcciones / editar) are stored as
// WooCommerce options in the DB (Ajustes → Avanzado → Endpoints de la cuenta),
// because WC caches the query vars before the theme loads. They were set via:
//   wp option update woocommerce_myaccount_orders_endpoint pedidos
//   wp option update woocommerce_myaccount_edit_address_endpoint direcciones
//   wp option update woocommerce_myaccount_edit_account_endpoint editar
//   wp rewrite flush

add_action( 'template_redirect', function (): void {
    if ( function_exists( 'is_account_page' ) && is_account_page()
        && is_user_logged_in() && ! is_wc_endpoint_url() ) {
        wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
        exit;
    }
} );

// ============================================================
// Molienda — persist as cart item data (not a WC variation)
// ============================================================

// 1. Save molienda when item is added to cart
add_filter( 'woocommerce_add_cart_item_data', function ( array $data, int $product_id, int $variation_id ): array {
    if ( ! empty( $_POST['molienda'] ) ) {
        $data['molienda'] = sanitize_text_field( wp_unslash( $_POST['molienda'] ) );
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
// Body classes
// ============================================================
add_filter( 'body_class', function ( array $classes ): array {
    if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
        $classes[] = 'is-woocommerce';
    }
    return $classes;
} );
