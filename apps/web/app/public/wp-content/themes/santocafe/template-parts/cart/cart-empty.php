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

    <div class="cart-empty__image" aria-hidden="true">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/cart-empty.jpg' ); ?>"
             alt="" width="160" height="160" loading="lazy">
    </div>

    <h2 class="cart-empty__title">No te quedes sin café.</h2>
    <p class="cart-empty__text">
        El café está triste porque tu carrito está vacío.<br>
        Descubrí nuestros orígenes de especialidad.
    </p>
    <a href="<?php echo esc_url( $catalog_url ); ?>" class="btn btn--primary btn--lg js-cart-drawer-close">
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
