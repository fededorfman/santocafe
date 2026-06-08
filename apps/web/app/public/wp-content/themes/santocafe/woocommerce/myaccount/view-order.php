<?php
/**
 * View Order — Santo Café override.
 * Clean header (number, date, status pill) + optional order updates,
 * then the order details table (rendered via woocommerce_view_order).
 *
 * @see woocommerce/templates/myaccount/view-order.php
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>

<div class="sc-order-head">
    <div class="sc-order-head__main">
        <span class="sc-order-head__kicker">Pedido</span>
        <h2 class="sc-order-head__number">#<?php echo esc_html( $order->get_order_number() ); ?></h2>
        <p class="sc-order-head__date">
            Realizado el <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
        </p>
    </div>
    <span class="sc-order-status sc-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
        <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
    </span>
</div>

<?php if ( $notes ) : ?>
    <section class="sc-order-updates">
        <h3 class="sc-order-updates__title">Seguimiento del pedido</h3>
        <ol class="sc-order-updates__list">
            <?php foreach ( $notes as $note ) : ?>
            <li class="sc-order-update">
                <span class="sc-order-update__date">
                    <?php echo esc_html( date_i18n( 'j \d\e F \d\e Y, H:i', strtotime( $note->comment_date ) ) ); ?>
                </span>
                <div class="sc-order-update__text">
                    <?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </section>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
