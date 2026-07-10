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

$sc_ab_variant = function_exists( 'sc_ab_get_variant' ) ? sc_ab_get_variant() : 'control';
$sc_grid_class = 'catalog-section__grid' . ( 'compact' === $sc_ab_variant ? ' catalog-section__grid--compact' : '' );
?>

<section class="catalog-section" id="catalogo" aria-label="Catálogo de productos">
    <div class="container">

        <header class="catalog-section__header">
            <h2 class="catalog-section__title">
                Nuestros <span class="text-dorado">Cafés</span>
            </h2>
            <p class="catalog-section__subtitle">Orígenes de especialidad.</p>
        </header>

        <?php if ( $products->have_posts() ) : ?>

        <div class="<?php echo esc_attr( $sc_grid_class ); ?>">
            <?php
            while ( $products->have_posts() ) {
                $products->the_post();
                if ( 'compact' === $sc_ab_variant ) {
                    get_template_part( 'template-parts/product/card-compact' );
                } else {
                    wc_get_template_part( 'content', 'product' );
                }
            }
            wp_reset_postdata();
            ?>
        </div>

        <?php else : ?>
        <p class="catalog-section__empty">
            Los productos estarán disponibles pronto. ¡Vuelve en breve!
        </p>
        <?php endif; ?>

    </div>
</section>
