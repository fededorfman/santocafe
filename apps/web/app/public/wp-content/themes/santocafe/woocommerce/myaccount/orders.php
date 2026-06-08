<?php
/**
 * Orders — Santo Café override.
 * Same orders table as default; custom calm empty state linking to /#catalogo.
 *
 * @see woocommerce/templates/myaccount/orders.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<?php if ( $has_orders ) : ?>

    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ( $customer_orders->orders as $customer_order ) :
                $order      = wc_get_order( $customer_order );
                $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            ?>
                <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order->get_status() ); ?> order">
                    <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                        <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
                            <?php if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) : ?>
                                <?php do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); ?>

                            <?php elseif ( 'order-number' === $column_id ) : ?>
                                <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                    <?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?>
                                </a>

                            <?php elseif ( 'order-date' === $column_id ) : ?>
                                <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>

                            <?php elseif ( 'order-status' === $column_id ) : ?>
                                <span class="sc-order-status sc-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
                                    <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                                </span>

                            <?php elseif ( 'order-total' === $column_id ) : ?>
                                <?php
                                /* translators: 1: order total 2: item count */
                                echo wp_kses_post( sprintf( _n( '%1$s por %2$s artículo', '%1$s por %2$s artículos', $item_count, 'santocafe' ), $order->get_formatted_order_total(), $item_count ) );
                                ?>

                            <?php elseif ( 'order-actions' === $column_id ) : ?>
                                <?php
                                $actions = wc_get_account_orders_actions( $order );
                                if ( ! empty( $actions ) ) {
                                    foreach ( $actions as $key => $action ) {
                                        echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

    <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if ( 1 !== $current_page ) : ?>
                <a class="woocommerce-button button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">Anterior</a>
            <?php endif; ?>
            <?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
                <a class="woocommerce-button button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php else : ?>

    <div class="sc-orders-empty">
        <span class="sc-orders-empty__kicker">Tus pedidos</span>
        <h3 class="sc-orders-empty__title">Todavía no hiciste ningún pedido</h3>
        <p class="sc-orders-empty__text">Cuando hagas tu primer pedido vas a poder seguirlo desde acá.</p>
        <a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>" class="btn btn--primary">
            Explorar los productos
        </a>
    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
