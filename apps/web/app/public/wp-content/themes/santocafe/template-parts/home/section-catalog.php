<?php
defined('ABSPATH') || exit;

if ( ! function_exists( 'wc_get_template_part' ) ) {
    return; // WooCommerce not active
}

$products = new WP_Query( [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
] );
?>

<section class="catalog-section" id="catalogo" aria-label="Catálogo de productos">
    <div class="container">

        <header class="catalog-section__header">
            <span class="catalog-section__pill">Café de especialidad en Chile</span>
            <h2 class="catalog-section__title">
                Nuestros <span class="text-dorado">Cafés</span>
            </h2>
            <p class="catalog-section__subtitle">Orígenes de especialidad.</p>
        </header>

        <?php if ( $products->have_posts() ) : ?>

        <div class="catalog-section__grid">
            <?php
            while ( $products->have_posts() ) {
                $products->the_post();
                wc_get_template_part( 'content', 'product' );
            }
            wp_reset_postdata();
            ?>
        </div>

        <?php else : ?>
        <p class="catalog-section__empty">
            Los productos estarán disponibles pronto. ¡Volvé en breve!
        </p>
        <?php endif; ?>

    </div>
</section>
