<?php
/**
 * Customer invoice email — Santo Café override.
 *
 * Detalle / factura del pedido, con enlace de pago cuando corresponde
 * (pedido pendiente o fallido). Conserva los hooks y la lógica de
 * `needs_payment()`; adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-invoice.php
 * @package santocafe
 * @version 10.4.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;

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

<?php if ( $order->needs_payment() ) : ?>
	<?php if ( $order->has_status( OrderStatus::FAILED ) ) : ?>
		<p>Tu pago del pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> no se pudo completar. Abajo está el detalle y un botón para reintentar el pago.</p>
	<?php else : ?>
		<p>Generamos tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>. Abajo está el detalle y un botón para completar el pago cuando quieras.</p>
	<?php endif; ?>

	<?php $sc_pay_url = $order->get_checkout_payment_url(); ?>
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0 8px;"><tr><td align="center">
		<!--[if mso]>
		<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo esc_url( $sc_pay_url ); ?>" style="height:50px;v-text-anchor:middle;width:220px;" arcsize="60%" stroke="f" fillcolor="#dfb33e">
			<w:anchorlock/><center style="color:#1a1310;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">Pagar mi pedido</center>
		</v:roundrect>
		<![endif]-->
		<a href="<?php echo esc_url( $sc_pay_url ); ?>" class="sc-btn" style="mso-hide:all;">Pagar mi pedido</a>
	</td></tr></table>
<?php else : ?>
	<p>Este es el detalle de tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> realizado el <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>:</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
