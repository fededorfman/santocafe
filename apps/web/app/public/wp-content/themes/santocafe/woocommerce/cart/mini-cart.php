<?php
/**
 * Mini-cart — Santo Café override (cart drawer contents).
 * Uses the same item controls as the cart page (molienda pills + qty-picker
 * + SVG remove), driven by the global sc_update_cart AJAX handler.
 * Action buttons (Seguir comprando / Finalizar compra) live in the drawer
 * footer (header.php), not here.
 *
 * @see woocommerce/templates/cart/mini-cart.php
 */
defined( 'ABSPATH' ) || exit;

$sc_moliendas = [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ];

do_action( 'woocommerce_before_mini_cart' );
?>

<?php if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>

    <?php
    $sc_gap = function_exists( 'sc_get_shipping_gap' ) ? sc_get_shipping_gap() : 0;
    $sc_pct = function_exists( 'sc_get_shipping_progress' ) ? sc_get_shipping_progress() : 100;
    ?>
    <div class="mini-cart__ship<?php echo 0 === $sc_gap ? ' is-complete' : ''; ?>">
        <p class="mini-cart__ship-text">
            <?php if ( $sc_gap > 0 ) : ?>
                ¡Lleva <strong><?php echo esc_html( sc_format_clp( $sc_gap ) ); ?></strong> más para obtener envío gratis!
            <?php else : ?>
                Tiene envío gratis en Región Metropolitana
            <?php endif; ?>
        </p>
        <div class="mini-cart__ship-bar" role="progressbar"
             aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $sc_pct ); ?>">
            <span class="mini-cart__ship-fill" style="width: <?php echo esc_attr( $sc_pct ); ?>%;"></span>
        </div>
    </div>

    <ul class="woocommerce-mini-cart cart_list product_list_widget">
        <?php
        do_action( 'woocommerce_before_mini_cart_contents' );

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( ! ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) ) {
                continue;
            }

            $sc_item_pais      = sc_get_product_meta( $product_id, 'pais' );
            $product_name      = ( $sc_item_pais ? $sc_item_pais . ' - ' : '' ) . get_the_title( $product_id ); // país - nombre (sin "- 250g")
            $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
            $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

            $peso     = $cart_item['variation']['attribute_pa_peso'] ?? '';
            $molienda = $cart_item['molienda'] ?? 'Grano';
            $qty      = (int) $cart_item['quantity'];
            $line     = sc_format_clp( (int) ( (float) $_product->get_price() * $qty ) );

            // Discount vs the "compare-at" price (250g → regular; 1kg → 4×250g).
            $sc_wp   = sc_product_weight_prices( $product_id );
            $sc_unit = ( '1kg' === $peso )
                ? sc_weight_pricing( $sc_wp['p1kg'], $sc_wp['r1kg'], $sc_wp['p250'] )
                : sc_weight_pricing( $sc_wp['p250'], $sc_wp['r250'] );
            $line_disc    = $sc_unit['discount'];
            $line_was_fmt = sc_format_clp( (int) round( $sc_unit['compare'] * $qty ) );
            ?>
            <li class="woocommerce-mini-cart-item mini_cart_item" data-key="<?php echo esc_attr( $cart_item_key ); ?>">

                <div class="mini-cart-item__top">
                    <div class="mini-cart-item__media">
                        <?php if ( $product_permalink ) : ?>
                        <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore ?></a>
                        <?php else : echo $thumbnail; // phpcs:ignore ?>
                        <?php endif; ?>
                    </div>

                    <div class="mini-cart-item__head">
                        <?php if ( $product_permalink ) : ?>
                        <a class="mini-cart-item__name" href="<?php echo esc_url( $product_permalink ); ?>">
                            <?php echo esc_html( $product_name ); ?>
                        </a>
                        <?php else : ?>
                        <span class="mini-cart-item__name"><?php echo esc_html( $product_name ); ?></span>
                        <?php endif; ?>
                        <?php if ( $peso ) : ?>
                        <span class="mini-cart-item__meta"><?php echo esc_html( $peso ); ?></span>
                        <?php endif; ?>
                    </div>

                    <button class="mini-cart-item__remove js-cart-remove"
                            data-key="<?php echo esc_attr( $cart_item_key ); ?>"
                            aria-label="Eliminar producto">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                        </svg>
                    </button>
                </div>

                <div class="pill-selector mini-cart-item__molienda">
                    <?php foreach ( $sc_moliendas as $m ) : ?>
                    <button type="button"
                            class="pill-selector__option js-cart-molienda <?php echo $m === $molienda ? 'is-selected' : ''; ?>"
                            data-key="<?php echo esc_attr( $cart_item_key ); ?>"
                            data-molienda="<?php echo esc_attr( $m ); ?>">
                        <?php echo esc_html( $m ); ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="mini-cart-item__bottom">
                    <div class="qty-picker qty-picker--sm">
                        <button class="qty-picker__btn js-cart-qty" data-action="minus"
                                data-key="<?php echo esc_attr( $cart_item_key ); ?>" type="button"
                                aria-label="Reducir cantidad" <?php echo $qty <= 1 ? 'disabled' : ''; ?>>−</button>
                        <input class="qty-picker__input" type="number"
                               value="<?php echo esc_attr( $qty ); ?>" min="1" max="20"
                               readonly aria-label="Cantidad">
                        <button class="qty-picker__btn js-cart-qty" data-action="plus"
                                data-key="<?php echo esc_attr( $cart_item_key ); ?>" type="button"
                                aria-label="Aumentar cantidad">+</button>
                    </div>
                    <span class="mini-cart-item__line">
                        <?php if ( $line_disc > 0 ) : ?>
                        <span class="mini-cart-item__line-meta">
                            <span class="mini-cart-item__line-was"><?php echo esc_html( $line_was_fmt ); ?></span>
                            <span class="mini-cart-item__line-disc">-<?php echo esc_html( $line_disc ); ?>%</span>
                        </span>
                        <?php endif; ?>
                        <span class="mini-cart-item__line-now"><?php echo esc_html( $line ); ?></span>
                    </span>
                </div>

            </li>
            <?php
        }

        do_action( 'woocommerce_mini_cart_contents' );
        ?>
    </ul>

    <div class="woocommerce-mini-cart__total total">
        <span>Subtotal</span>
        <span><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></span>
    </div>

    <?php $sc_free = function_exists( 'sc_get_shipping_gap' ) && sc_get_shipping_gap() === 0; ?>
    <div class="mini-cart__shipping">
        <span>Envío</span>
        <span>
            <?php if ( $sc_free ) : ?>
                <strong class="cart-summary__free">Gratis en Región Metropolitana</strong>
            <?php else : ?>
                <span class="cart-summary__muted">Se calcula en el pago</span>
            <?php endif; ?>
        </span>
    </div>
    <p class="mini-cart__note">IVA incluido. Envío solo a Región Metropolitana de Santiago.</p>

<?php else : ?>

    <?php get_template_part( 'template-parts/cart/cart-empty' ); ?>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' );
