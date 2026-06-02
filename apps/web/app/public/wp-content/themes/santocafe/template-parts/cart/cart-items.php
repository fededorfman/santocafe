<?php
/**
 * Cart items list. Rendered on initial load and re-rendered via AJAX.
 * Handles the empty state by delegating to the cart-empty partial.
 */
defined( 'ABSPATH' ) || exit;

$cart = function_exists( 'WC' ) ? WC()->cart : null;

if ( ! $cart || $cart->is_empty() ) {
    get_template_part( 'template-parts/cart/cart-empty' );
    return;
}

$moliendas = [ 'Grano', 'Espresso', 'Italiana', 'Filtro' ];
?>
<div class="cart-items">

    <div class="cart-items__head">
        <span>Producto</span>
        <span>Total</span>
    </div>

    <?php
    foreach ( $cart->get_cart() as $key => $item ) :
        $product   = $item['data'];
        if ( ! $product instanceof WC_Product ) continue;

        $pid       = $item['product_id'];
        $qty       = (int) $item['quantity'];
        $peso      = $item['variation']['attribute_pa_peso'] ?? '';
        $molienda  = $item['molienda'] ?? 'Grano';
        $name      = get_the_title( $pid ); // parent name (avoids "- 250g" duplication)
        $unit      = (float) $product->get_price();
        $unit_fmt  = sc_format_clp( (int) $unit );
        $line_fmt  = sc_format_clp( (int) ( $unit * $qty ) );
    ?>
    <div class="cart-item" data-key="<?php echo esc_attr( $key ); ?>">

        <div class="cart-item__media">
            <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
                <?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
            </a>
        </div>

        <div class="cart-item__info">
            <a class="cart-item__name" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
                <?php echo esc_html( $name );
                    if ( $peso ) echo ' &mdash; ' . esc_html( $peso ); ?>
            </a>
            <span class="cart-item__unit"><?php echo esc_html( $unit_fmt ); ?></span>
            <span class="cart-item__attrs">
                peso: <strong><?php echo esc_html( $peso ); ?></strong> /
                molienda: <strong><?php echo esc_html( $molienda ); ?></strong>
            </span>
        </div>

        <div class="cart-item__total"><?php echo esc_html( $line_fmt ); ?></div>

        <button class="cart-item__remove js-cart-remove"
                data-key="<?php echo esc_attr( $key ); ?>" aria-label="Eliminar producto">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
            </svg>
        </button>

        <!-- Second row: molienda + quantity (more breathing room) -->
        <div class="cart-item__controls">
            <div class="pill-selector cart-item__molienda">
                <?php foreach ( $moliendas as $m ) : ?>
                <button type="button"
                        class="pill-selector__option js-cart-molienda <?php echo $m === $molienda ? 'is-selected' : ''; ?>"
                        data-key="<?php echo esc_attr( $key ); ?>"
                        data-molienda="<?php echo esc_attr( $m ); ?>">
                    <?php echo esc_html( $m ); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="qty-picker cart-item__qty">
                <button class="qty-picker__btn js-cart-qty" data-action="minus"
                        data-key="<?php echo esc_attr( $key ); ?>" type="button"
                        aria-label="Reducir cantidad">−</button>
                <input class="qty-picker__input" type="number"
                       value="<?php echo esc_attr( $qty ); ?>" min="1" max="20"
                       readonly aria-label="Cantidad">
                <button class="qty-picker__btn js-cart-qty" data-action="plus"
                        data-key="<?php echo esc_attr( $key ); ?>" type="button"
                        aria-label="Aumentar cantidad">+</button>
            </div>
        </div>

    </div>
    <?php endforeach; ?>

</div>
