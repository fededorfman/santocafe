<?php
/**
 * Pay for order form — Santo Café override.
 *
 * Misma estética que "ver pedido" / "gracias": header con pill de estado,
 * tabla de detalles (thumbnail + país + nombre + meta) y totales, caja de pago
 * y botón con los estilos del tema.
 *
 * @see woocommerce/templates/checkout/form-pay.php
 * @var WC_Order $order
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

$totals = $order->get_order_item_totals();
?>

<div class="sc-order sc-pay">

    <div class="sc-order-head">
        <span class="sc-order-status sc-order-head__status sc-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
            <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
        </span>
        <span class="sc-order-head__kicker">Pagar pedido</span>
        <h1 class="sc-order-head__number">#<?php echo esc_html( $order->get_order_number() ); ?></h1>
    </div>

    <form id="order_review" method="post" class="sc-pay__form">

        <table class="shop_table sc-pay-table">
            <thead>
                <tr>
                    <th class="product-name">Producto</th>
                    <th class="product-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $order->get_items() as $item_id => $item ) :
                    if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
                        continue;
                    }
                    $product = $item->get_product();
                    $thumb   = $product ? $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'sc-order-item__img' ) ) : '';

                    // País de origen (bandera + nombre), igual que en el detalle del pedido.
                    $sc_pais = '';
                    if ( $product ) {
                        $sc_pid  = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
                        $sc_pais = (string) sc_get_product_meta( $sc_pid, 'pais' );
                    }
                    $sc_flag = $sc_pais ? sc_country_flag_url( $sc_pais ) : null;
                    ?>
                    <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
                        <td class="product-name">
                            <div class="sc-order-item">
                                <?php if ( $thumb ) : ?>
                                    <div class="sc-order-item__media"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                                <?php endif; ?>
                                <div class="sc-order-item__info">
                                    <span class="sc-order-item__name"><?php
                                        if ( $sc_pais ) {
                                            echo '<span class="sc-order-item__pais">';
                                            if ( $sc_flag ) {
                                                echo '<img class="product-card__flag-inline" src="' . esc_url( $sc_flag ) . '" alt="" width="18" height="12" aria-hidden="true">';
                                            }
                                            echo esc_html( $sc_pais ) . '</span> - ';
                                        }
                                        echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
                                    ?></span>
                                    <span class="sc-order-item__qty">Cantidad: <strong><?php echo esc_html( $item->get_quantity() ); ?></strong></span>
                                    <div class="sc-order-item__meta">
                                        <?php
                                        do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
                                        wc_display_item_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput
                                        do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="product-total"><?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <?php foreach ( $totals as $total ) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
                        <td class="product-total"><?php echo wp_kses_post( $total['value'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tfoot>
        </table>

        <?php do_action( 'woocommerce_pay_order_before_payment' ); ?>

        <div id="payment" class="sc-pay__payment">
            <?php if ( $order->needs_payment() ) : ?>
                <ul class="wc_payment_methods payment_methods methods">
                    <?php
                    if ( ! empty( $available_gateways ) ) {
                        foreach ( $available_gateways as $gateway ) {
                            wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
                        }
                    } else {
                        echo '<li>';
                        wc_print_notice( esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ), 'notice' );
                        echo '</li>';
                    }
                    ?>
                </ul>
            <?php endif; ?>

            <div class="form-row sc-pay__actions">
                <input type="hidden" name="woocommerce_pay" value="1" />

                <?php wc_get_template( 'checkout/terms.php' ); ?>

                <?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

                <?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="btn btn--primary btn--lg sc-pay__submit" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

                <?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

                <?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
            </div>
        </div>
    </form>
</div>
