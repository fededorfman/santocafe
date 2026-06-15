<?php
/**
 * Thank you / Order received — Santo Café override.
 *
 * Replica el look de la vista de pedido en "Mi cuenta" (header con pill de
 * estado + tabla de detalles), sumando un hero de confirmación y, para
 * invitados, una invitación a crear cuenta para seguir el pedido.
 *
 * @see woocommerce/templates/checkout/thankyou.php
 * @var WC_Order $order
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="sc-order sc-thankyou woocommerce-order">

<?php if ( $order ) :

    do_action( 'woocommerce_before_thankyou', $order->get_id() );

    if ( $order->has_status( 'failed' ) ) : ?>

        <div class="sc-thankyou__hero sc-thankyou__hero--failed">
            <div class="sc-thankyou__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <h1 class="sc-thankyou__title">No pudimos procesar el pago</h1>
            <p class="sc-thankyou__lead">El banco o medio de pago rechazó la transacción. Podés intentar el pago nuevamente.</p>
            <p class="sc-thankyou__actions">
                <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn btn--primary">Reintentar el pago</a>
            </p>
        </div>

    <?php else : ?>

        <div class="sc-thankyou__hero">
            <div class="sc-thankyou__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <h1 class="sc-thankyou__title">¡Gracias por tu compra!</h1>
            <p class="sc-thankyou__lead">
                Recibimos tu pedido y te enviamos la confirmación
                <?php if ( $order->get_billing_email() ) : ?>
                    a <strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>.
                <?php else : ?>
                    por email.
                <?php endif; ?>
            </p>
        </div>

        <div class="sc-order-head">
            <span class="sc-order-status sc-order-head__status sc-order-status--<?php echo esc_attr( $order->get_status() ); ?>">
                <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
            </span>
            <span class="sc-order-head__kicker">Pedido</span>
            <h2 class="sc-order-head__number">#<?php echo esc_html( $order->get_order_number() ); ?></h2>
            <p class="sc-order-head__date">
                Realizado el <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
            </p>
        </div>

        <?php
        // Invitación a crear cuenta — solo para quien compró como invitado.
        if ( ! is_user_logged_in() ) :
            $sc_register_url = wc_get_page_permalink( 'myaccount' );
            if ( $order->get_billing_email() ) {
                $sc_register_url = add_query_arg( 'reg_email', rawurlencode( $order->get_billing_email() ), $sc_register_url );
            }
            ?>
            <aside class="sc-account-invite">
                <div class="sc-account-invite__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="sc-account-invite__body">
                    <h3 class="sc-account-invite__title">Creá tu cuenta y seguí tu pedido</h3>
                    <p class="sc-account-invite__text">
                        Registrate con el mismo email de tu compra y este pedido aparecerá en tu
                        historial, con su estado y seguimiento. Además agilizás tus próximas compras.
                    </p>
                </div>
                <a class="btn btn--primary sc-account-invite__cta" href="<?php echo esc_url( $sc_register_url ); ?>">
                    Crear cuenta
                </a>
            </aside>
        <?php endif; ?>

        <div class="sc-order-body">
            <?php
            do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
            do_action( 'woocommerce_thankyou', $order->get_id() );
            ?>
        </div>

    <?php endif; ?>

<?php else : ?>

    <?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

<?php endif; ?>

</div>
