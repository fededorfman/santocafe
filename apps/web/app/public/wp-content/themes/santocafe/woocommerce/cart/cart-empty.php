<?php
/**
 * Empty Cart Page — Santo Café custom template.
 * Overrides: woocommerce/templates/cart/cart-empty.php
 *
 * Rendered by the [woocommerce_cart] shortcode inside the_content().
 * Does NOT call get_header()/get_footer(). Provides both wrappers
 * (.js-cart-items and .js-cart-totals) so the AJAX flow can repopulate
 * them when items are added back without a reload.
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="cart-page cart-page--empty">
    <h1 class="cart-page__title">Tu Carrito</h1>

    <div class="cart-layout">
        <div class="cart-layout__items js-cart-items">
            <?php get_template_part( 'template-parts/cart/cart-empty' ); ?>
        </div>
        <aside class="cart-layout__summary js-cart-totals">
            <div class="cart-summary cart-summary--empty"></div>
        </aside>
    </div>
</div>
