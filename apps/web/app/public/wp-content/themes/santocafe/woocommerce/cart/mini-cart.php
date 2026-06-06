<?php
/**
 * Mini-cart — Santo Café override.
 * Renders the cart drawer contents (list + subtotal). Action buttons live
 * in the drawer footer (header.php), not here.
 *
 * @see woocommerce/templates/cart/mini-cart.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' );
?>

<?php if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>

    <ul class="woocommerce-mini-cart cart_list product_list_widget">
        <?php
        do_action( 'woocommerce_before_mini_cart_contents' );

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

                // Parent product name (avoids "Camino Inca - 250g" duplication)
                $product_name      = get_the_title( $product_id );
                $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
                $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                ?>
                <li class="woocommerce-mini-cart-item mini_cart_item">

                    <div class="mini-cart-item__media">
                        <?php if ( $product_permalink ) : ?>
                        <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore ?></a>
                        <?php else : echo $thumbnail; // phpcs:ignore ?>
                        <?php endif; ?>
                    </div>

                    <div class="mini-cart-item__info">
                        <?php if ( $product_permalink ) : ?>
                        <a class="mini-cart-item__name" href="<?php echo esc_url( $product_permalink ); ?>">
                            <?php echo esc_html( $product_name ); ?>
                        </a>
                        <?php else : ?>
                        <span class="mini-cart-item__name"><?php echo esc_html( $product_name ); ?></span>
                        <?php endif; ?>

                        <?php echo wc_get_formatted_cart_item_data( $cart_item ); // peso + molienda // phpcs:ignore ?>

                        <span class="mini-cart-item__qty">
                            <?php echo wp_kses_post( sprintf( '%s &times; %s', $cart_item['quantity'], $product_price ) ); ?>
                        </span>
                    </div>

                    <?php
                    echo apply_filters( // phpcs:ignore
                        'woocommerce_cart_item_remove_link',
                        sprintf(
                            '<a role="button" href="%s" class="remove remove_from_cart_button mini-cart-item__remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                            esc_attr( sprintf( __( 'Quitar %s del carrito', 'santocafe' ), wp_strip_all_tags( $product_name ) ) ),
                            esc_attr( $product_id ),
                            esc_attr( $cart_item_key ),
                            esc_attr( $_product->get_sku() )
                        ),
                        $cart_item_key
                    );
                    ?>
                </li>
                <?php
            }
        }

        do_action( 'woocommerce_mini_cart_contents' );
        ?>
    </ul>

    <p class="woocommerce-mini-cart__total total">
        <?php do_action( 'woocommerce_widget_shopping_cart_total' ); ?>
    </p>

<?php else : ?>

    <p class="woocommerce-mini-cart__empty-message">Tu carrito está vacío.</p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' );
