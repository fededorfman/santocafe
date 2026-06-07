<?php
/**
 * Quick View Modal content.
 * Called by sc_ajax_product_quick_view() with global $product set.
 */
defined('ABSPATH') || exit;

global $product;
if ( ! $product instanceof WC_Product ) return;

$id         = $product->get_id();
$sca        = sc_get_product_meta( $id, 'sca_score' );
$pais       = sc_get_product_meta( $id, 'pais' );
$region     = sc_get_product_meta( $id, 'region' );
$notas      = sc_get_product_meta( $id, 'notas_cata' );
$altitud    = sc_get_product_meta( $id, 'altitud' );
$proceso    = sc_get_product_meta( $id, 'proceso' );
$variedad   = sc_get_product_meta( $id, 'variedad' );
$intensidad = (int) sc_get_product_meta( $id, 'intensidad' );
$acidez     = (int) sc_get_product_meta( $id, 'acidez' );
$cuerpo     = (int) sc_get_product_meta( $id, 'cuerpo' );

// Variations
$var_250 = null;
$var_1kg  = null;
if ( $product->is_type( 'variable' ) ) {
    foreach ( $product->get_available_variations() as $var ) {
        $slug = $var['attributes']['attribute_pa_peso'] ?? '';
        if ( '250g' === $slug ) $var_250 = $var;
        if ( '1kg'  === $slug ) $var_1kg  = $var;
    }
}

$price_250     = $var_250 ? (float) $var_250['display_price']  : (float) $product->get_price();
$price_1kg     = $var_1kg  ? (float) $var_1kg['display_price']  : $price_250 * 3.8;
$var_250_id    = $var_250 ? $var_250['variation_id']   : $id;
$var_1kg_id    = $var_1kg  ? $var_1kg['variation_id']   : $id;
$price_250_fmt = sc_format_clp( (int) $price_250 );
$price_1kg_fmt = sc_format_clp( (int) $price_1kg );

$reg_250 = $var_250 ? (float) $var_250['display_regular_price'] : (float) $product->get_regular_price();
$reg_1kg = $var_1kg  ? (float) $var_1kg['display_regular_price']  : $reg_250 * 3.8;
$pr_250  = sc_weight_pricing( $price_250, $reg_250 );
$pr_1kg  = sc_weight_pricing( $price_1kg, $reg_1kg, $price_250 );

$add_250_url = add_query_arg( [
    'add-to-cart'       => $id,
    'variation_id'      => $var_250_id,
    'quantity'          => 1,
    'attribute_pa_peso' => '250g',
], trailingslashit( home_url() ) );

$add_1kg_url = add_query_arg( [
    'add-to-cart'       => $id,
    'variation_id'      => $var_1kg_id,
    'quantity'          => 1,
    'attribute_pa_peso' => '1kg',
], trailingslashit( home_url() ) );
?>

<!-- Image zone -->
<div class="product-modal__image-zone">

    <?php if ( $sca ) : ?>
    <div class="product-modal__sca">
        <span class="sca-badge sca-badge--gold">SCA <?php echo esc_html( $sca ); ?></span>
    </div>
    <?php endif; ?>

    <button class="product-modal__close js-modal-close" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>

    <?php if ( has_post_thumbnail( $id ) ) : ?>
        <?php echo get_the_post_thumbnail( $id, 'medium', [ 'class' => 'product-modal__image', 'alt' => get_the_title( $id ) ] ); ?>
    <?php else : ?>
        <div class="product-modal__image-placeholder"></div>
    <?php endif; ?>

</div>

<!-- Info zone -->
<div class="product-modal__info">

    <div>
        <h2 class="product-modal__title"><?php echo esc_html( get_the_title( $id ) ); ?></h2>
        <?php if ( $pais ) : ?>
        <p class="product-modal__origin">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <?php echo esc_html( $pais ); ?>
            <?php if ( $region ) : ?>
                <span>·</span>
                <?php echo esc_html( $region ); ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if ( $notas ) : ?>
    <p class="product-modal__notes"><?php echo esc_html( $notas ); ?></p>
    <?php endif; ?>

    <!-- 3 spec cards -->
    <?php if ( $altitud || $proceso || $notas ) : ?>
    <div class="product-modal__specs">
        <?php if ( $altitud ) : ?>
        <div class="modal-spec-card">
            <span class="modal-spec-card__label">Altitud</span>
            <span class="modal-spec-card__value"><?php echo esc_html( $altitud ); ?>m</span>
        </div>
        <?php endif; ?>
        <?php if ( $proceso ) : ?>
        <div class="modal-spec-card">
            <span class="modal-spec-card__label">Proceso</span>
            <span class="modal-spec-card__value"><?php echo esc_html( $proceso ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ( $variedad ) : ?>
        <div class="modal-spec-card">
            <span class="modal-spec-card__label">Variedad</span>
            <span class="modal-spec-card__value"><?php echo esc_html( $variedad ); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Profile bars -->
    <?php if ( $intensidad || $acidez || $cuerpo ) : ?>
    <div class="product-modal__profiles">
        <?php if ( $intensidad ) sc_render_profile_bar( 'Intensidad', $intensidad ); ?>
        <?php if ( $acidez )     sc_render_profile_bar( 'Acidez',     $acidez ); ?>
        <?php if ( $cuerpo )     sc_render_profile_bar( 'Cuerpo',     $cuerpo ); ?>
    </div>
    <?php endif; ?>

    <!-- Format selector -->
    <div class="product-modal__format pill-selector" data-product-id="<?php echo esc_attr( $id ); ?>">
        <button class="pill-selector__option is-selected"
                data-variation-id="<?php echo esc_attr( $var_250_id ); ?>"
                data-price="<?php echo esc_attr( $price_250_fmt ); ?>"
                data-original="<?php echo esc_attr( $pr_250['compare_fmt'] ); ?>"
                data-discount="<?php echo esc_attr( $pr_250['discount'] ); ?>"
                data-peso="250g"
                data-add-url="<?php echo esc_url( $add_250_url ); ?>"
                type="button">
            <?php echo esc_html( $price_250_fmt ); ?> / 250g
        </button>
        <?php if ( $var_1kg ) : ?>
        <button class="pill-selector__option"
                data-variation-id="<?php echo esc_attr( $var_1kg_id ); ?>"
                data-price="<?php echo esc_attr( $price_1kg_fmt ); ?>"
                data-original="<?php echo esc_attr( $pr_1kg['compare_fmt'] ); ?>"
                data-discount="<?php echo esc_attr( $pr_1kg['discount'] ); ?>"
                data-peso="1kg"
                data-add-url="<?php echo esc_url( $add_1kg_url ); ?>"
                type="button">
            <?php echo esc_html( $price_1kg_fmt ); ?> / 1kg
        </button>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="product-modal__actions">
        <span class="product-modal__pricing">
            <span class="product-modal__price js-modal-price"><?php echo esc_html( $price_250_fmt ); ?></span>
            <span class="product-modal__price-meta">
                <span class="product-modal__price-was js-modal-original"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>><?php echo esc_html( $pr_250['compare_fmt'] ); ?></span>
                <span class="product-modal__discount js-modal-discount"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>>-<?php echo esc_html( $pr_250['discount'] ); ?>%</span>
            </span>
        </span>
        <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" class="btn btn--outline">
            Ver ficha
        </a>
        <a href="<?php echo esc_url( $add_250_url ); ?>"
           class="btn btn--primary js-modal-add">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            Añadir al carrito
        </a>
    </div>

</div>
