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
// Cart fragment — keep cart badge in sync after AJAX add-to-cart
// ============================================================
add_filter( 'woocommerce_add_to_cart_fragments', function ( array $fragments ): array {
    $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    ob_start();
    ?>
    <span class="cart-icon__badge js-cart-count <?php echo $count ? '' : 'is-empty'; ?>">
        <?php echo esc_html( $count ); ?>
    </span>
    <?php
    $fragments['.cart-icon__badge'] = ob_get_clean();

    return $fragments;
} );

// ============================================================
// Body classes
// ============================================================
add_filter( 'body_class', function ( array $classes ): array {
    if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
        $classes[] = 'is-woocommerce';
    }
    return $classes;
} );
