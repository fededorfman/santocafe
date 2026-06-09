<?php
/**
 * Cart Page — Santo Café custom template.
 * Overrides: woocommerce/templates/cart/cart.php
 *
 * Rendered by the [woocommerce_cart] shortcode inside the_content(),
 * so it must NOT call get_header()/get_footer() — page.php provides those.
 * All cart mutations happen via AJAX (sc_update_cart).
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="cart-page">
    <h1 class="cart-page__title">Tu Carrito</h1>

    <div class="cart-layout">
        <div class="cart-layout__items js-cart-items">
            <?php get_template_part( 'template-parts/cart/cart-items' ); ?>
        </div>
        <aside class="cart-layout__summary js-cart-totals">
            <?php get_template_part( 'template-parts/cart/cart-totals' ); ?>
        </aside>
    </div>
</div>
