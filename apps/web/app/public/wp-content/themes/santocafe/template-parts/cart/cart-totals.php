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

        <?php if ( $cart->needs_shipping() && $cart->show_shipping() ) : ?>
        <div class="cart-summary__row cart-summary__row--shipping">
            <span>Envío</span>
            <span><?php wc_cart_totals_shipping_html(); ?></span>
        </div>
        <?php endif; ?>

        <?php foreach ( $cart->get_tax_totals() as $code => $tax ) : ?>
        <div class="cart-summary__row">
            <span><?php echo esc_html( $tax->label ); ?></span>
            <span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
        </div>
        <?php endforeach; ?>

        <div class="cart-summary__row cart-summary__row--total">
            <span>Total</span>
            <span><?php wc_cart_totals_order_total_html(); ?></span>
        </div>
    </div>

    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
       class="btn btn--primary btn--full btn--lg cart-summary__checkout">
        Finalizar compra →
    </a>

</div>
