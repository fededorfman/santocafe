<?php
/**
 * Customer refunded order email — Santo Café override.
 *
 * Reembolso total o parcial. Conserva los hooks y la variable `$partial_refund`
 * de WooCommerce; adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-refunded-order.php
 * @package santocafe
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	printf( 'Hola %s,', esc_html( $order->get_billing_first_name() ) );
} else {
	echo 'Hola,';
}
?>
</p>
<p>
<?php if ( $partial_refund ) : ?>
	Procesamos un reembolso parcial de tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>. Abajo dejamos el detalle para tu referencia.
<?php else : ?>
	Procesamos el reembolso de tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>. Abajo dejamos el detalle para tu referencia.
<?php endif; ?>
</p>
<p style="font-size:14px;color:#8a7d6b;">El plazo en que verás el monto acreditado depende de tu banco o medio de pago.</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
