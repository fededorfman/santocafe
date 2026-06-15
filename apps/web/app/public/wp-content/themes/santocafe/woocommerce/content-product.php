<?php
/**
 * Santo Café — Product Card Template
 * Overrides: woocommerce/templates/content-product.php
 *
 * Used by: shop page loop, homepage catalog section.
 * @version 9.4.0
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
$acidez     = (int) sc_get_product_meta( $id, 'acidez' );
$cuerpo     = (int) sc_get_product_meta( $id, 'cuerpo' );
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

// ---- Pricing + discount (250g vs regular; 1kg vs the higher of regular or 4×250g) ----
$reg_250 = $var_250 ? (float) $var_250['display_regular_price'] : (float) $product->get_regular_price();
$reg_1kg = $var_1kg  ? (float) $var_1kg['display_regular_price']  : $reg_250 * 3.8;

$pr_250 = sc_weight_pricing( $price_250, $reg_250 );
$pr_1kg = sc_weight_pricing( $price_1kg, $reg_1kg, $price_250 );

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

// ---- Country flag ----
$sc_flag_url = $pais ? sc_country_flag_url( (string) $pais ) : null;

// ---- Stock por peso (gramos) ----
$sc_stock   = sc_weight_stock_states( $id );
$sc_in_250  = $sc_stock['250g'] ?? true;
$sc_in_1kg  = $sc_stock['1kg'] ?? true;
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

        <?php if ( $sca ) : ?>
        <div class="product-card__badges">
            <span class="sca-badge sca-badge--gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="5"/>
                    <path d="M9 12.4 7.5 22l4.5-2.8L16.5 22 15 12.4"/>
                </svg>
                SCA <?php echo esc_html( $sca ); ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ( $on_sale ) : ?>
        <span class="product-card__offer-badge" aria-label="En oferta">Oferta</span>
        <?php endif; ?>

    </div>
    <!-- /Image zone -->

    <!-- ============ Content zone ============ -->
    <div class="product-card__content">

        <h3 class="product-card__title">
            <a href="<?php the_permalink(); ?>">
                <?php if ( $pais ) : ?>
                    <span class="product-card__pais">
                        <?php if ( $sc_flag_url ) : ?>
                        <img class="product-card__flag-inline" src="<?php echo esc_url( $sc_flag_url ); ?>"
                             alt="" width="18" height="12" aria-hidden="true">
                        <?php endif; ?>
                        <?php echo esc_html( $pais ); ?>
                    </span><?php echo esc_html( ' - ' . get_the_title() ); ?>
                <?php else : ?>
                    <?php the_title(); ?>
                <?php endif; ?>
            </a>
        </h3>

        <?php if ( $notas ) : ?>
        <p class="product-card__notes"><?php echo esc_html( $notas ); ?></p>
        <?php endif; ?>

        <?php if ( $intensidad > 0 || $acidez > 0 || $cuerpo > 0 ) : ?>
        <div class="product-card__profile">
            <?php if ( $intensidad > 0 ) sc_render_profile_bar( 'INTENSIDAD', $intensidad ); ?>
            <?php if ( $acidez > 0 )     sc_render_profile_bar( 'ACIDEZ',     $acidez ); ?>
            <?php if ( $cuerpo > 0 )     sc_render_profile_bar( 'CUERPO',     $cuerpo ); ?>
        </div>
        <?php endif; ?>

        <!-- Formato / precio selector (250g / 1kg) -->
        <div class="product-card__weights" data-product-id="<?php echo esc_attr( $id ); ?>">
            <button class="product-card__weight is-selected<?php echo $sc_in_250 ? '' : ' product-card__weight--out'; ?>"
                    data-variation-id="<?php echo esc_attr( $var_250_id ); ?>"
                    data-peso="250g"
                    data-instock="<?php echo $sc_in_250 ? '1' : '0'; ?>"
                    data-price="<?php echo esc_attr( $pr_250['price_fmt'] ); ?>"
                    data-original="<?php echo esc_attr( $pr_250['compare_fmt'] ); ?>"
                    data-discount="<?php echo esc_attr( $pr_250['discount'] ); ?>"
                    data-add-url="<?php echo esc_url( $add_250_url ); ?>"
                    type="button">
                <span class="product-card__weight-price"><?php echo esc_html( $pr_250['price_fmt'] ); ?></span>
                <span class="product-card__weight-unit">250g</span>
            </button>
            <?php if ( $var_1kg ) : ?>
            <button class="product-card__weight<?php echo $sc_in_1kg ? '' : ' product-card__weight--out'; ?>"
                    data-variation-id="<?php echo esc_attr( $var_1kg_id ); ?>"
                    data-peso="1kg"
                    data-instock="<?php echo $sc_in_1kg ? '1' : '0'; ?>"
                    data-price="<?php echo esc_attr( $pr_1kg['price_fmt'] ); ?>"
                    data-original="<?php echo esc_attr( $pr_1kg['compare_fmt'] ); ?>"
                    data-discount="<?php echo esc_attr( $pr_1kg['discount'] ); ?>"
                    data-add-url="<?php echo esc_url( $add_1kg_url ); ?>"
                    type="button">
                <?php if ( $pr_1kg['discount'] > 0 ) : ?>
                <span class="product-card__weight-badge" aria-hidden="true">
                    -<?php echo esc_html( $pr_1kg['discount'] ); ?>%
                </span>
                <?php endif; ?>
                <span class="product-card__weight-price"><?php echo esc_html( $pr_1kg['price_fmt'] ); ?></span>
                <span class="product-card__weight-unit">1kg</span>
            </button>
            <?php endif; ?>
        </div>

        <p class="product-card__molienda">
            Molienda: <strong>Grano</strong>
            <span aria-hidden="true"> · </span>
            <span class="product-card__molienda-note">se edita en el carrito</span>
        </p>

        <!-- Footer: precio (con descuento) + botón -->
        <div class="product-card__footer">
            <div class="product-card__pricing">
                <span class="product-card__price js-card-price"><?php echo esc_html( $pr_250['price_fmt'] ); ?></span>
                <span class="product-card__price-meta">
                    <span class="product-card__price-was js-card-original"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>><?php echo esc_html( $pr_250['compare_fmt'] ); ?></span>
                    <span class="product-card__discount js-card-discount"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>>-<?php echo esc_html( $pr_250['discount'] ); ?>%</span>
                </span>
            </div>
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
                <span class="js-card-add-label">Añadir</span>
            </a>
        </div>

    </div>
    <!-- /Content zone -->

</article>
