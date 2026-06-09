<?php
/**
 * Cart totals panel. Rendered on initial load and re-rendered via AJAX.
 * Uses native WC functions so shipping and tax reflect store config.
 */
defined( 'ABSPATH' ) || exit;

$cart = function_exists( 'WC' ) ? WC()->cart : null;

if ( ! $cart || $cart->is_empty() ) {
    echo '<div class="cart-summary cart-summary--empty"></div>';
    return;
}
?>
<div class="cart-summary">

    <h2 class="cart-summary__title">Total del Carrito</h2>

    <details class="cart-summary__coupon">
        <summary>Agregar cupones</summary>
        <form class="cart-coupon-form" method="post"
              action="<?php echo esc_url( wc_get_cart_url() ); ?>">
            <input type="text" name="coupon_code" class="cart-coupon-form__input"
                   placeholder="Código de cupón" id="coupon_code">
            <button type="submit" class="btn btn--outline btn--sm"
                    name="apply_coupon" value="Aplicar">Aplicar</button>
        </form>
    </details>

    <?php $free_shipping = function_exists( 'sc_get_shipping_gap' ) && sc_get_shipping_gap() === 0; ?>

    <div class="cart-summary__rows">
        <div class="cart-summary__row">
            <span>Subtotal</span>
            <span><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <?php foreach ( $cart->get_coupons() as $code => $coupon ) : ?>
        <div class="cart-summary__row cart-summary__row--coupon">
            <span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
            <span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
        </div>
        <?php endforeach; ?>

        <div class="cart-summary__row cart-summary__row--shipping">
            <span>Envío</span>
            <span>
                <?php if ( $free_shipping ) : ?>
                    <strong class="cart-summary__free">Gratis</strong>
                <?php else : ?>
                    <span class="cart-summary__muted">Calculado en el pago</span>
                <?php endif; ?>
            </span>
        </div>

        <div class="cart-summary__row cart-summary__row--total">
            <span>Total</span>
            <span><?php wc_cart_totals_order_total_html(); ?></span>
        </div>
    </div>

    <p class="cart-summary__note">
        IVA incluido. Envío solo a Región Metropolitana de Santiago.
    </p>

    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
       class="btn btn--primary btn--full btn--lg cart-summary__checkout">
        Finalizar compra →
    </a>

</div>
