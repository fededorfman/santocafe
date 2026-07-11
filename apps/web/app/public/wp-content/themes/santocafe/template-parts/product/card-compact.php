<?php
/**
 * Santo Café — Tarjeta compacta de catálogo (variante "compact" del
 * test A/B). Ver docs/superpowers/specs/2026-07-10-ab-test-tarjeta-catalogo-design.md
 *
 * Usado por: template-parts/home/section-catalog.php, solo cuando
 * sc_ab_get_variant() devuelve 'compact'.
 */
defined('ABSPATH') || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
    return;
}

$id            = $product->get_id();
$card_photo_id = (int) sc_get_product_meta( $id, 'card_photo' );
$prices        = sc_product_weight_prices( $id );
$price_fmt     = sc_format_clp( (int) $prices['p250'] );

$pais        = sc_get_product_meta( $id, 'pais' );
$sc_flag_url = $pais ? sc_country_flag_url( (string) $pais ) : null;
?>

<article <?php wc_product_class( 'product-card-compact', $product ); ?>>
    <a href="<?php the_permalink(); ?>" class="product-card-compact__link"
       aria-label="<?php echo esc_attr( 'Ver ficha de ' . get_the_title() ); ?>">

        <div class="product-card-compact__image-zone">
            <?php
            if ( $card_photo_id ) {
                echo wp_get_attachment_image( $card_photo_id, 'woocommerce_single', false, [
                    'class' => 'product-card-compact__image',
                    'alt'   => get_the_title(),
                ] );
            } elseif ( has_post_thumbnail() ) {
                the_post_thumbnail( 'woocommerce_single', [
                    'class' => 'product-card-compact__image',
                    'alt'   => get_the_title(),
                ] );
            } else {
                echo '<div class="product-card-compact__image product-card-compact__image--placeholder"></div>';
            }
            ?>

            <?php if ( $pais ) : ?>
            <div class="product-card-compact__badges">
                <span class="sca-badge sca-badge--gold">
                    <?php if ( $sc_flag_url ) : ?>
                    <img src="<?php echo esc_url( $sc_flag_url ); ?>" alt="" width="18" height="12" aria-hidden="true">
                    <?php endif; ?>
                    <?php echo esc_html( $pais ); ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="product-card-compact__overlay">
                <span class="product-card-compact__name"><?php the_title(); ?></span>
                <span class="product-card-compact__price"><?php echo esc_html( $price_fmt ); ?></span>
            </div>

            <span class="btn btn--primary btn--sm product-card-compact__view" aria-hidden="true">Ver</span>
        </div>

    </a>
</article>
