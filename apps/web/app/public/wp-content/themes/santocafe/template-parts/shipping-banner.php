<?php
/**
 * Shipping progress banner.
 * Rendered in header.php (inside .js-banner-slot) and re-rendered by the
 * sc_update_cart AJAX handler so it stays in sync without a page reload.
 */
defined('ABSPATH') || exit;

$gap   = function_exists( 'sc_get_shipping_gap' ) ? sc_get_shipping_gap() : 0;
$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$has   = $count > 0;

if ( $has && $gap > 0 ) : ?>
    <div class="shipping-banner js-shipping-banner">
        <div class="container">
            <p class="shipping-banner__text">
                Te faltan <strong><?php echo esc_html( sc_format_clp( $gap ) ); ?></strong>
                para envío gratis dentro de Chile.
            </p>
            <button class="shipping-banner__close js-close-banner" aria-label="Cerrar aviso">✕</button>
        </div>
    </div>
<?php elseif ( $has && $gap === 0 ) : ?>
    <div class="shipping-banner shipping-banner--success js-shipping-banner">
        <div class="container">
            <p class="shipping-banner__text">¡Tu pedido tiene <strong>envío gratis</strong>! 🎉</p>
            <button class="shipping-banner__close js-close-banner" aria-label="Cerrar aviso">✕</button>
        </div>
    </div>
<?php endif;
