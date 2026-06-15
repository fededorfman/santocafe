<?php
/**
 * Orders — Santo Café override.
 * Same orders table as default; custom calm empty state linking to /#catalogo.
 *
 * @see woocommerce/templates/myaccount/orders.php
 * @version 9.5.0
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<?php if ( $has_orders ) : ?>

    <?php // Card-per-order list: adapts to narrow widths without cramping. ?>
    <div class="sc-orders-list">
        <?php foreach ( $customer_orders->orders as $customer_order ) :
            $order      = wc_get_order( $customer_order );
            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actions    = wc_get_account_orders_actions( $order );
        ?>
            <div class="sc-order-card sc-order-card--status-<?php echo esc_attr( $order->get_status() ); ?>">
                <div class="sc-order-card__info">
                    <a class="sc-order-card__number" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                        #<?php echo esc_html( $order->get_order_number() ); ?>
                    </a>
                    <span class="sc-order-status sc-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
                        <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                    </span>
                    <time class="sc-order-card__date" datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>
                    <span class="sc-order-card__total">
                        <?php
                        /* translators: 1: order total 2: item count */
                        echo wp_kses_post( sprintf( _n( '%1$s · %2$s artículo', '%1$s · %2$s artículos', $item_count, 'santocafe' ), $order->get_formatted_order_total(), $item_count ) );
                        ?>
                    </span>
                </div>

                <?php if ( ! empty( $actions ) ) : ?>
                <div class="sc-order-card__actions">
                    <?php foreach ( $actions as $key => $action ) : ?>
                        <a href="<?php echo esc_url( $action['url'] ); ?>" class="btn btn--sm <?php echo 'view' === $key ? 'btn--primary' : 'btn--outline'; ?> <?php echo esc_attr( sanitize_html_class( $key ) ); ?>">
                            <?php echo esc_html( $action['name'] ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

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
