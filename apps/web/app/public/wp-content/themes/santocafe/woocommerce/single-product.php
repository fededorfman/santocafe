<?php
/**
 * Single Product Page — Santo Café custom template.
 * Overrides: woocommerce/templates/single-product.php
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();

    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) continue;

    // ---- Meta fields ----
    $id         = $product->get_id();
    $sca        = sc_get_product_meta( $id, 'sca_score' );
    $pais       = sc_get_product_meta( $id, 'pais' );
    $region     = sc_get_product_meta( $id, 'region' );
    $altitud    = sc_get_product_meta( $id, 'altitud' );
    $proceso    = sc_get_product_meta( $id, 'proceso' );
    $variedad   = sc_get_product_meta( $id, 'variedad' );
    $productor  = sc_get_product_meta( $id, 'productor' );
    $notas      = sc_get_product_meta( $id, 'notas_cata' );
    $intensidad = (int) sc_get_product_meta( $id, 'intensidad' );
    $acidez     = (int) sc_get_product_meta( $id, 'acidez' );
    $cuerpo     = (int) sc_get_product_meta( $id, 'cuerpo' );

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

    $price_250     = $var_250 ? (float) $var_250['display_price'] : (float) $product->get_price();
    $price_1kg     = $var_1kg  ? (float) $var_1kg['display_price']  : round( $price_250 * 3.8 );
    $var_250_id    = $var_250 ? $var_250['variation_id'] : $id;
    $var_1kg_id    = $var_1kg  ? $var_1kg['variation_id']  : $id;
    $price_250_fmt = sc_format_clp( (int) $price_250 );
    $price_1kg_fmt = sc_format_clp( (int) $price_1kg );
    $per_cup_250   = sc_format_clp( (int) round( $price_250 / 30 ) );
    $per_cup_1kg   = sc_format_clp( (int) round( $price_1kg / 120 ) );

    // Discount (250g vs regular; 1kg vs the higher of regular or 4×250g)
    $reg_250 = $var_250 ? (float) $var_250['display_regular_price'] : (float) $product->get_regular_price();
    $reg_1kg = $var_1kg  ? (float) $var_1kg['display_regular_price']  : $reg_250 * 3.8;
    $pr_250  = sc_weight_pricing( $price_250, $reg_250 );
    $pr_1kg  = sc_weight_pricing( $price_1kg, $reg_1kg, $price_250 );

    // ---- Molienda options ----
    $molienda_opts = [
        [
            'value'    => 'Grano',
            'label'    => 'En Grano',
            'sublabel' => 'Sin moler',
            'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="5" ry="7"/><path d="M12 5C9 3 6.5 5 7 8"/><path d="M12 19c3 2 5.5 0 5-3"/></svg>',
        ],
        [
            'value'    => 'Espresso',
            'label'    => 'Espresso',
            'sublabel' => 'Muy fina',
            'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21h10"/><path d="M9 21V9a3 3 0 016 0v12"/><path d="M6 9h12"/><path d="M8 13h8"/><circle cx="12" cy="5" r="1.5"/></svg>',
        ],
        [
            'value'    => 'Italiana',
            'label'    => 'Italiana',
            'sublabel' => 'Media-fina',
            'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6l1 7H8L9 3z"/><path d="M8 10v4a4 4 0 008 0v-4"/><path d="M12 18v3"/><path d="M9 21h6"/></svg>',
        ],
        [
            'value'    => 'Filtro',
            'label'    => 'Filtro',
            'sublabel' => 'Media-gruesa',
            'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l-4 9v6l-4 0v-6L6 3z"/><line x1="6" y1="3" x2="18" y2="3"/></svg>',
        ],
    ];

    // ---- Related products ----
    $related_ids = wc_get_related_products( $id, 3 );
    ?>

<main class="site-main product-detail-page" id="main">
    <?php do_action( 'woocommerce_before_single_product' ); ?>
    <div class="container">

        <!-- ============ Main grid: Image + Info ============ -->
        <div class="product-detail__grid">

            <!-- Left: Gallery -->
            <div class="product-detail__gallery">
                <div class="product-detail__image-wrap">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php echo get_the_post_thumbnail( $id, 'woocommerce_single', [
                            'class' => 'product-detail__image',
                            'alt'   => get_the_title(),
                        ] ); ?>
                    <?php else : ?>
                        <div class="product-detail__image-placeholder"></div>
                    <?php endif; ?>
                    <button class="product-detail__zoom-btn js-zoom-btn" aria-label="Ampliar imagen">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            <line x1="11" y1="8" x2="11" y2="14"/>
                            <line x1="8" y1="11" x2="14" y2="11"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Right: Info -->
            <div class="product-detail__info">

                <!-- Badges + Title -->
                <header class="product-detail__header">
                    <div class="product-detail__badges">
                        <?php if ( $sca ) : ?>
                        <span class="sca-badge sca-badge--gold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M9 12.4 7.5 22l4.5-2.8L16.5 22 15 12.4"/></svg>
                            SCA <?php echo esc_html( $sca ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $pais ) : ?>
                        <span class="country-badge"><?php echo esc_html( $pais ); ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="product-detail__title"><?php the_title(); ?></h1>
                </header>

                <!-- Price zone -->
                <div class="product-detail__price-zone">
                    <span class="product-detail__price js-detail-price">
                        <?php echo esc_html( $price_250_fmt ); ?>
                    </span>
                    <span class="product-detail__price-was js-detail-original"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>><?php echo esc_html( $pr_250['compare_fmt'] ); ?></span>
                    <span class="product-detail__discount js-detail-discount"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>>-<?php echo esc_html( $pr_250['discount'] ); ?>%</span>
                    <span class="product-detail__per-cup js-per-cup"
                          data-per-cup-250="<?php echo esc_attr( $per_cup_250 ); ?>"
                          data-per-cup-1kg="<?php echo esc_attr( $per_cup_1kg ); ?>">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17 8h1a4 4 0 010 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z"/>
                            <line x1="6" y1="2" x2="6" y2="4"/>
                            <line x1="10" y1="2" x2="10" y2="4"/>
                            <line x1="14" y1="2" x2="14" y2="4"/>
                        </svg>
                        <?php echo esc_html( $per_cup_250 ); ?>/taza
                    </span>
                </div>

                <!-- Notas de cata -->
                <?php if ( $notas ) : ?>
                <div class="product-detail__tasting">
                    <span class="product-detail__tasting-label">Notas de cata</span>
                    <p class="product-detail__tasting-value"><?php echo esc_html( $notas ); ?></p>
                </div>
                <?php endif; ?>

                <!-- Spec + profile cards (3 columns) -->
                <?php if ( $altitud || $proceso || $intensidad || $acidez || $cuerpo ) : ?>
                <div class="product-detail__specs">
                    <?php if ( $altitud ) : ?>
                    <div class="detail-spec">
                        <span class="detail-spec__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19 12 6l8 13"/><path d="M9 14l3-2 2 2"/></svg>
                        </span>
                        <span class="detail-spec__label">Altitud</span>
                        <span class="detail-spec__value"><?php echo esc_html( $altitud ); ?>m</span>
                    </div>
                    <?php endif; ?>
                    <?php if ( $proceso ) : ?>
                    <div class="detail-spec">
                        <span class="detail-spec__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.5 4 5 6.5 5 9a5 5 0 0 1-10 0c0-2.5 1.5-5 5-9z"/></svg>
                        </span>
                        <span class="detail-spec__label">Proceso</span>
                        <span class="detail-spec__value"><?php echo esc_html( $proceso ); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ( $intensidad || $acidez || $cuerpo ) : ?>
                    <div class="detail-spec detail-spec--profile">
                        <?php if ( $intensidad ) sc_render_profile_bar( 'Intensidad', $intensidad ); ?>
                        <?php if ( $acidez )     sc_render_profile_bar( 'Acidez',     $acidez ); ?>
                        <?php if ( $cuerpo )     sc_render_profile_bar( 'Cuerpo',     $cuerpo ); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- FORMATO selector -->
                <div class="product-detail__section">
                    <span class="product-detail__section-label">Formato</span>
                    <div class="pill-selector product-detail__format">
                        <button class="pill-selector__option is-selected"
                                data-variation-id="<?php echo esc_attr( $var_250_id ); ?>"
                                data-price="<?php echo esc_attr( $price_250_fmt ); ?>"
                                data-original="<?php echo esc_attr( $pr_250['compare_fmt'] ); ?>"
                                data-discount="<?php echo esc_attr( $pr_250['discount'] ); ?>"
                                data-raw-price="<?php echo esc_attr( (int) $price_250 ); ?>"
                                data-original-raw="<?php echo esc_attr( $pr_250['compare'] ); ?>"
                                data-per-cup="<?php echo esc_attr( $per_cup_250 ); ?>"
                                data-peso="250g"
                                type="button">
                            <span class="format-weight">250g</span>
                            <span class="format-cups">~30 tazas</span>
                        </button>
                        <?php if ( $var_1kg ) : ?>
                        <button class="pill-selector__option"
                                data-variation-id="<?php echo esc_attr( $var_1kg_id ); ?>"
                                data-price="<?php echo esc_attr( $price_1kg_fmt ); ?>"
                                data-original="<?php echo esc_attr( $pr_1kg['compare_fmt'] ); ?>"
                                data-discount="<?php echo esc_attr( $pr_1kg['discount'] ); ?>"
                                data-raw-price="<?php echo esc_attr( (int) $price_1kg ); ?>"
                                data-original-raw="<?php echo esc_attr( $pr_1kg['compare'] ); ?>"
                                data-per-cup="<?php echo esc_attr( $per_cup_1kg ); ?>"
                                data-peso="1kg"
                                type="button">
                            <span class="format-weight">1kg</span>
                            <span class="format-cups">~120 tazas</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MOLIENDA selector -->
                <div class="product-detail__section">
                    <span class="product-detail__section-label">Molienda</span>
                    <div class="molienda-selector">
                        <?php foreach ( $molienda_opts as $i => $opt ) : ?>
                        <button class="molienda-option <?php echo $i === 0 ? 'is-selected' : ''; ?>"
                                data-value="<?php echo esc_attr( $opt['value'] ); ?>"
                                type="button"
                                aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                            <span class="molienda-option__icon" aria-hidden="true">
                                <?php echo $opt['icon']; // SVG safe — hardcoded in PHP ?>
                            </span>
                            <span class="molienda-option__label"><?php echo esc_html( $opt['label'] ); ?></span>
                            <span class="molienda-option__sub"><?php echo esc_html( $opt['sublabel'] ); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CANTIDAD selector -->
                <div class="product-detail__section product-detail__section--inline">
                    <span class="product-detail__section-label">Cantidad</span>
                    <div class="qty-picker">
                        <button class="qty-picker__btn js-qty-btn" data-action="minus"
                                type="button" aria-label="Reducir cantidad">−</button>
                        <input class="qty-picker__input" type="number"
                               id="quantity" name="quantity"
                               value="1" min="1" max="20"
                               readonly aria-label="Cantidad">
                        <button class="qty-picker__btn js-qty-btn" data-action="plus"
                                type="button" aria-label="Aumentar cantidad">+</button>
                    </div>
                </div>

                <!-- Guarantees (2×2 grid) -->
                <div class="product-detail__guarantees">
                    <?php
                    $guarantees = [
                        ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>', 'text' => 'Envío gratis desde $50.000'],
                        ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', 'text' => 'Entrega 24-48 horas'],
                        ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>', 'text' => 'Pago 100% seguro'],
                        ['icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 00-4-4H4"/></svg>', 'text' => '30 días devolución'],
                    ];
                    foreach ( $guarantees as $g ) : ?>
                    <div class="guarantee-item">
                        <span class="guarantee-item__icon" aria-hidden="true"><?php echo $g['icon']; ?></span>
                        <span class="guarantee-item__text"><?php echo esc_html( $g['text'] ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add-to-cart form -->
                <form class="product-detail__cart-form" method="post"
                      action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>">
                    <input type="hidden" name="add-to-cart"       value="<?php echo esc_attr( $id ); ?>">
                    <input type="hidden" name="variation_id"      class="js-variation-id" value="<?php echo esc_attr( $var_250_id ); ?>">
                    <input type="hidden" name="attribute_pa_peso" class="js-peso-input"    value="250g">
                    <input type="hidden" name="molienda"          class="js-molienda-input" value="Grano">
                    <input type="hidden" name="quantity"          class="js-qty-input"     value="1">
                    <button type="submit" class="btn btn--primary btn--full btn--lg product-detail__cta">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 01-8 0"/>
                        </svg>
                        Agregar al carrito —
                        <span class="js-cta-price"><?php echo esc_html( $price_250_fmt ); ?></span>
                        <span class="product-detail__cta-was js-cta-original"<?php echo $pr_250['discount'] ? '' : ' hidden'; ?>><?php echo esc_html( $pr_250['compare_fmt'] ); ?></span>
                    </button>
                </form>

            </div>
            <!-- /info -->
        </div>
        <!-- /main grid -->

        <!-- ============ Tabs ============ -->
        <div class="product-detail__tabs-wrap">

            <div class="tabs" role="tablist">
                <button class="tab-btn is-active" role="tab" aria-selected="true"
                        aria-controls="tab-origen" data-tab="origen">
                    Ficha del origen
                </button>
                <?php if ( $product->get_description() ) : ?>
                <button class="tab-btn" role="tab" aria-selected="false"
                        aria-controls="tab-descripcion" data-tab="descripcion">
                    Descripción
                </button>
                <?php endif; ?>
                <button class="tab-btn" role="tab" aria-selected="false"
                        aria-controls="tab-info" data-tab="info">
                    Información
                </button>
            </div>

            <!-- Tab: Ficha del origen -->
            <div class="tab-panel is-active" id="tab-origen" role="tabpanel">
                <div class="origin-sheet">
                    <div class="origin-sheet__table-wrap">
                        <table class="origin-sheet__table">
                            <tbody>
                            <?php
                            $rows = [
                                'País'      => $pais,
                                'Región'    => $region,
                                'Productor' => $productor,
                                'Altitud'   => $altitud ? $altitud . ' msnm' : '',
                                'Variedad'  => $variedad,
                                'Proceso'   => $proceso,
                            ];
                            foreach ( $rows as $label => $value ) :
                                if ( ! $value ) continue; ?>
                            <tr>
                                <th><?php echo esc_html( $label ); ?></th>
                                <td><?php echo esc_html( $value ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ( $notas ) : ?>
                    <div class="origin-sheet__notes">
                        <p class="origin-sheet__notes-label">Notas de cata</p>
                        <p class="origin-sheet__notes-value"><?php echo esc_html( $notas ); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab: Descripción -->
            <?php if ( $product->get_description() ) : ?>
            <div class="tab-panel" id="tab-descripcion" role="tabpanel">
                <div class="product-detail__description">
                    <?php echo wp_kses_post( $product->get_description() ); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab: Información -->
            <div class="tab-panel" id="tab-info" role="tabpanel">
                <table class="product-info-table">
                    <tbody>
                    <?php
                    $info = [
                        'País de origen' => $pais,
                        'Puntaje SCA'    => $sca,
                        'Altitud'        => $altitud ? $altitud . ' msnm' : '',
                        'Proceso'        => $proceso,
                        'Variedad'       => $variedad,
                        'Notas de cata'  => $notas,
                        'Intensidad'     => $intensidad ? $intensidad . '/5' : '',
                        'Acidez'         => $acidez    ? $acidez    . '/5' : '',
                        'Cuerpo'         => $cuerpo    ? $cuerpo    . '/5' : '',
                        'Presentaciones' => '250g, 1kg',
                        'Moliendas'      => 'Grano, Espresso, Italiana, Filtro',
                    ];
                    foreach ( $info as $label => $value ) :
                        if ( ! $value ) continue; ?>
                    <tr>
                        <th><?php echo esc_html( $label ); ?></th>
                        <td><?php echo esc_html( $value ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <!-- /tabs -->

        <!-- ============ Related products ============ -->
        <?php if ( ! empty( $related_ids ) ) : ?>
        <section class="related-products" aria-label="Productos relacionados">
            <h2 class="related-products__title">También te puede interesar</h2>
            <div class="related-products__grid">
                <?php
                $related_query = new WP_Query( [
                    'post_type'           => 'product',
                    'post__in'            => $related_ids,
                    'posts_per_page'      => 3,
                    'orderby'             => 'rand',
                    'no_found_rows'       => true,
                    'ignore_sticky_posts' => true,
                ] );
                while ( $related_query->have_posts() ) {
                    $related_query->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
                wp_reset_postdata();
                ?>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /.container -->
    <?php do_action( 'woocommerce_after_single_product' ); ?>
</main>

    <?php
endwhile;

get_footer();
