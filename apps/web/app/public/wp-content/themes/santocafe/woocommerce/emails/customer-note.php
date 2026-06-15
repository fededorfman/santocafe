<?php
/**
 * Customer note email — Santo Café override.
 *
 * Aviso cuando se agrega una nota al pedido. Conserva los hooks dinámicos y la
 * variable `$customer_note`; adapta el copy a la voz de marca (español de Chile).
 *
 * @see woocommerce/templates/emails/customer-note.php
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
<p>Tenemos una novedad sobre tu pedido <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>:</p>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="sc-note">
<?php
$safe_note = wc_wptexturize_order_note( $customer_note );
echo wpautop( make_clickable( $safe_note ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</td></tr></table>

<p style="margin-top:16px;">Como recordatorio, este es el detalle de tu pedido:</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
