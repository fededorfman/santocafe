<?php
/**
 * Santo Café — Product Card Template
 * Overrides: woocommerce/templates/content-product.php
 *
 * Used by: shop page loop, homepage catalog section.
 */
defined('ABSPATH') || exit;

global $product;

if ( ! $product || ! $product->is_visible() ) {
    return;
}

$id        = $product->get_id();
$sca       = sc_get_product_meta( $id, 'sca_score' );
$pais      = sc_get_product_meta( $id, 'pais' );
$notas     = sc_get_product_meta( $id, 'notas_cata' );
$intensidad = (int) sc_get_product_meta( $id, 'intensidad' );
$cart_url  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/carrito/' );

// ---- Variations (peso: 250g / 1kg) ----
$var_250 = null;
$var_1kg  = null;

if ( $product->is_type( 'variable' ) ) {
    foreach ( $product->get_available_variations() as $var ) {
        $slug = $var['attributes']['attribute_pa_peso'] ?? '';
        if ( '250g' === $slug ) $var_250 = $var;
        if ( '1kg'  === $slug ) $var_1kg  = $var;
    }
}

// Fallback: use product price if no variations found
$price_250     = $var_250 ? $var_250['display_price']      : (float) $product->get_price();
$price_1kg     = $var_1kg  ? $var_1kg['display_price']      : $price_250 * 3.8;
$var_250_id    = $var_250 ? $var_250['variation_id']       : $id;
$var_1kg_id    = $var_1kg  ? $var_1kg['variation_id']       : $id;

$price_250_fmt = sc_format_clp( (int) $price_250 );
$price_1kg_fmt = sc_format_clp( (int) $price_1kg );

// ---- Add-to-cart URLs ----
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

// ---- On sale ----
$on_sale = $product->is_on_sale();
?>

<article <?php wc_product_class( 'product-card', $product ); ?>>

    <!-- ============ Image zone ============ -->
    <div class="product-card__image-zone">

        <a href="<?php the_permalink(); ?>" class="product-card__image-link" tabindex="-1" aria-hidden="true">
            <?php
            if ( has_post_thumbnail() ) {
                the_post_thumbnail( 'woocommerce_single', [
                    'class' => 'product-card__image',
                    'alt'   => get_the_title(),
                ] );
            } else {
                echo '<div class="product-card__image product-card__image--placeholder"></div>';
            }
            ?>
        </a>

        <?php if ( $sca || $pais ) : ?>
        <div class="product-card__badges">
            <span class="sca-badge">
                <?php
                $badge_parts = [];
                if ( $pais )  $badge_parts[] = esc_html( $pais );
                if ( $sca )   $badge_parts[] = 'SCA ' . esc_html( $sca );
                echo implode( ' · ', $badge_parts );
                ?>
            </span>
        </div>
        <?php endif; ?>

        <button class="product-card__info-btn js-product-info"
                data-product-id="<?php echo esc_attr( $id ); ?>"
                aria-label="<?php echo esc_attr( 'Más información sobre ' . get_the_title() ); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="8" stroke-width="3"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
            </svg>
        </button>

        <?php if ( $on_sale ) : ?>
        <span class="product-card__offer-badge" aria-label="En oferta">Oferta</span>
        <?php endif; ?>

    </div>
    <!-- /Image zone -->

    <!-- ============ Content zone ============ -->
    <div class="product-card__content">

        <h3 class="product-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ( $notas ) : ?>
        <p class="product-card__notes"><?php echo esc_html( $notas ); ?></p>
        <?php endif; ?>

        <?php if ( $intensidad > 0 ) : ?>
        <div class="product-card__profile">
            <?php sc_render_profile_bar( 'INT.', $intensidad ); ?>
        </div>
        <?php endif; ?>

        <!-- Formato / precio selector -->
        <div class="product-card__format pill-selector" data-product-id="<?php echo esc_attr( $id ); ?>">
            <button class="pill-selector__option is-selected"
                    data-variation-id="<?php echo esc_attr( $var_250_id ); ?>"
                    data-price="<?php echo esc_attr( $price_250_fmt ); ?>"
                    data-peso="250g"
                    data-add-url="<?php echo esc_url( $add_250_url ); ?>"
                    type="button">
                <?php echo esc_html( $price_250_fmt ); ?> / 250g
            </button>
            <?php if ( $var_1kg ) : ?>
            <button class="pill-selector__option"
                    data-variation-id="<?php echo esc_attr( $var_1kg_id ); ?>"
                    data-price="<?php echo esc_attr( $price_1kg_fmt ); ?>"
                    data-peso="1kg"
                    data-add-url="<?php echo esc_url( $add_1kg_url ); ?>"
                    type="button">
                <?php echo esc_html( $price_1kg_fmt ); ?> / 1kg
            </button>
            <?php endif; ?>
        </div>

        <p class="product-card__molienda">
            Molienda: <strong>Grano</strong>
            <span aria-hidden="true"> · </span>
            <a href="<?php echo esc_url( $cart_url ); ?>" class="product-card__molienda-edit">
                editar en carrito
            </a>
        </p>

        <!-- Footer: precio + botón -->
        <div class="product-card__footer">
            <span class="product-card__price js-card-price">
                <?php echo esc_html( $price_250_fmt ); ?>
            </span>
            <a href="<?php echo esc_url( $add_250_url ); ?>"
               class="btn btn--primary btn--sm product-card__add js-card-add"
               aria-label="<?php echo esc_attr( 'Añadir ' . get_the_title() . ' al carrito' ); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
                Añadir
            </a>
        </div>

    </div>
    <!-- /Content zone -->

</article>
