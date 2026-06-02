<?php
/**
 * Empty cart state — message, CTA and 4 suggested products.
 * Shared by woocommerce/cart/cart-empty.php (initial load) and
 * cart-items.php (when the cart becomes empty via AJAX).
 */
defined( 'ABSPATH' ) || exit;

$catalog_url = home_url( '/#catalogo' );
?>
<div class="cart-empty">

    <div class="cart-empty__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
        </svg>
    </div>

    <h2 class="cart-empty__title">Tu carrito está vacío</h2>
    <p class="cart-empty__text">
        Todavía no agregaste ningún café. Descubrí nuestros orígenes de especialidad.
    </p>
    <a href="<?php echo esc_url( $catalog_url ); ?>" class="btn btn--primary btn--lg">
        Ver nuestros cafés
    </a>

    <?php
    if ( function_exists( 'wc_get_template_part' ) ) :
        $suggested = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 4,
            'orderby'        => 'rand',
            'no_found_rows'  => true,
        ] );
        if ( $suggested->have_posts() ) : ?>
        <section class="cart-suggested" aria-label="Productos sugeridos">
            <div class="cart-suggested__head">
                <h3 class="cart-suggested__title">Te podría interesar</h3>
                <a href="<?php echo esc_url( $catalog_url ); ?>" class="cart-suggested__all">Ver todos →</a>
            </div>
            <div class="cart-suggested__grid">
                <?php
                while ( $suggested->have_posts() ) {
                    $suggested->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
                wp_reset_postdata();
                ?>
            </div>
        </section>
        <?php endif;
    endif;
    ?>

</div>
