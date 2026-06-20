<?php
/**
 * Customer completed order email — Santo Café override.
 *
 * Pedido completado/enviado. Conserva los hooks dinámicos de WooCommerce y
 * adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-completed-order.php
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
<p>¡Buenas noticias! Tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> ya está completo y en camino. Esperamos que disfrutes cada taza tanto como nosotros disfrutamos prepararlo.</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

$sc_url = $order->get_view_order_url();
?>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:24px 0 8px;"><tr><td align="center">
	<!--[if mso]>
	<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo esc_url( $sc_url ); ?>" style="height:50px;v-text-anchor:middle;width:220px;" arcsize="60%" stroke="f" fillcolor="#dfb33e">
		<w:anchorlock/><center style="color:#1a1310;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">Ver mi pedido</center>
	</v:roundrect>
	<![endif]-->
	<a href="<?php echo esc_url( $sc_url ); ?>" class="sc-btn" style="mso-hide:all;">Ver mi pedido</a>
</td></tr></table>

<?php
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
